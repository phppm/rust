<?php
namespace rust\stream;
use Psr\Http\Message\StreamInterface;

/**
 * Class StreamUtil
 *
 * @package rust\stream
 */
final class StreamUtil {
    /**
     * @param string|resource|object $resource
     * @param array                  $options
     *
     * @return PumpStream|Stream|string
     */
    public static function streamFor($resource = '', array $options = []) {
        if (is_scalar($resource)) {
            $stream = fopen('php://temp', 'r+');
            if ($resource !== '') {
                fwrite($stream, $resource);
                fseek($stream, 0);
            }
            return new Stream($stream, $options);
        }
        $resourceType = gettype($resource);
        if ('resource' === $resourceType) {
            return new Stream($resource, $options);
        }
        if ('object' === $resourceType) {
            if ($resource instanceof StreamInterface) {
                return $resource;
            }
            if ($resource instanceof \Iterator) {
                return new PumpStream(function () use ($resource) {
                    if (!$resource->valid()) {
                        return FALSE;
                    }
                    $result = $resource->current();
                    $resource->next();
                    return $result;
                }, $options);
            }
            if (method_exists($resource, '__toString')) {
                return static::streamFor((string)$resource, $options);
            }
        }
        if ('NULL' === $resourceType) {
            return new Stream(fopen('php://temp', 'r+'), $options);
        }
        if (is_callable($resource)) {
            return new PumpStream($resource, $options);
        }
        throw new \InvalidArgumentException('Invalid resource type: ' . gettype($resource));
    }

    /**
     * @param StreamInterface $stream
     * @param int             $maxLen
     *
     * @return string
     */
    public static function copyToString(StreamInterface $stream, $maxLen = -1) {
        $buffer = '';
        if ($maxLen === -1) {
            while (!$stream->eof()) {
                $buf = $stream->read(1048576);
                if ($buf == NULL) {
                    break;
                }
                $buffer .= $buf;
            }
            return $buffer;
        }
        $len = 0;
        while (!$stream->eof() && $len < $maxLen) {
            $buf = $stream->read($maxLen - $len);
            if ($buf == NULL) {
                break;
            }
            $buffer .= $buf;
            $len = strlen($buffer);
        }
        return $buffer;
    }
}