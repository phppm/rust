<?php
namespace rust\stream;
use Psr\Http\Message\StreamInterface;

/**
 * Lazily reads or writes to a file that is opened only after an IO operation
 * take place on the stream.
 *
 * @author Michael Dowling <mtdowling@gmail.com> guzzlehttp/psr7
 */
class LazyOpenStream implements StreamInterface {
    /** @var string File to open */
    private $filename;
    /** @var string $mode */
    private $mode;
    /**
     * @var StreamInterface
     */
    private $stream;

    /**
     * Magic method used to create a new stream if streams are not added in
     * the constructor of a decorator (e.g., LazyOpenStream).
     *
     * @param string $name Name of the property (allows "stream" only).
     *
     * @return StreamInterface
     */
    public function __get($name) {
        if ($name == 'stream') {
            $this->stream = $this->createStream();
            return $this->stream;
        }
        throw new \UnexpectedValueException("$name not found on class");
    }

    public function __toString() {
        try {
            if ($this->isSeekable()) {
                $this->seek(0);
            }
            return $this->getContents();
        } catch (\Exception $e) {
            // Really, PHP? https://bugs.php.net/bug.php?id=53648
            trigger_error('StreamDecorator::__toString exception: ' . (string)$e, E_USER_ERROR);
            return '';
        }
    }

    public function getContents() {
        if (!$this->stream) {
            $this->stream = $this->createStream();
        }
        $buffer = '';
        while (!$this->eof()) {
            $buf = $this->read(1048576);
            // Using a loose equality here to match on '' and false.
            if ($buf == NULL) {
                break;
            }
            $buffer .= $buf;
        }
        return $buffer;
    }

    /**
     * Allow decorators to implement custom methods
     *
     * @param string $method Missing method name
     * @param array  $args   Method arguments
     *
     * @return mixed
     */
    public function __call($method, array $args) {
        $result = call_user_func_array([$this->stream, $method], $args);
        // Always return the wrapped object if the result is a return $this
        return $result === $this->stream ? $this : $result;
    }

    public function close() {
        $this->stream->close();
    }

    public function getMetadata($key = NULL) {
        return $this->stream->getMetadata($key);
    }

    public function detach() {
        return $this->stream->detach();
    }

    public function getSize() {
        return $this->stream->getSize();
    }

    public function eof() {
        return $this->stream->eof();
    }

    public function tell() {
        return $this->stream->tell();
    }

    public function isReadable() {
        return $this->stream->isReadable();
    }

    public function isWritable() {
        return $this->stream->isWritable();
    }

    public function isSeekable() {
        return $this->stream->isSeekable();
    }

    public function rewind() {
        $this->seek(0);
    }

    public function seek($offset, $whence = SEEK_SET) {
        $this->stream->seek($offset, $whence);
    }

    public function read($length) {
        return $this->stream->read($length);
    }

    public function write($string) {
        return $this->stream->write($string);
    }

    /**
     * @param string $filename File to lazily open
     * @param string $mode     fopen mode to use when opening the stream
     */
    public function __construct($filename, $mode) {
        $this->filename = $filename;
        $this->mode     = $mode;
    }

    /**
     * Creates the underlying stream lazily when required.
     *
     * @return Stream
     * @throws \Exception
     */
    protected function createStream() {
        $filename = $this->filename;
        $mode     = $this->mode;
        $ex       = NULL;
        set_error_handler(function () use ($filename, $mode, &$ex) {
            $ex = new \RuntimeException(sprintf('Unable to open %s using mode %s: %s', $filename, $mode, func_get_args()[1]));
        });
        $handle = fopen($filename, $mode);
        restore_error_handler();
        if ($ex instanceof \Exception) {
            throw $ex;
        }
        $this->stream = new Stream($handle);
        return $this->stream;
    }
}
