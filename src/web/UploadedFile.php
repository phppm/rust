<?php
namespace rust\web;
use InvalidArgumentException;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;
use RuntimeException;

class UploadedFile implements UploadedFileInterface {
    /**
     * @var int[]
     */
    private static $errors = [
        UPLOAD_ERR_OK,
        UPLOAD_ERR_INI_SIZE,
        UPLOAD_ERR_FORM_SIZE,
        UPLOAD_ERR_PARTIAL,
        UPLOAD_ERR_NO_FILE,
        UPLOAD_ERR_NO_TMP_DIR,
        UPLOAD_ERR_CANT_WRITE,
        UPLOAD_ERR_EXTENSION,
    ];
    /**
     * @var string
     */
    private $clientFilename;
    /**
     * @var string
     */
    private $clientMediaType;
    /**
     * @var int
     */
    private $error;
    /**
     * @var null|string
     */
    private $file;
    /**
     * @var bool
     */
    private $moved = FALSE;
    /**
     * @var int
     */
    private $size;
    /**
     * @var StreamInterface|null
     */
    private $stream;

    /**
     * @param StreamInterface|string|resource $streamOrFile
     * @param int                             $size
     * @param int                             $errorStatus
     * @param string|null                     $clientFilename
     * @param string|null                     $clientMediaType
     */
    public function __construct($streamOrFile, $size, $errorStatus, $clientFilename = NULL, $clientMediaType = NULL) {
        $this->setError($errorStatus);
        $this->setSize($size);
        $this->setClientFilename($clientFilename);
        $this->setClientMediaType($clientMediaType);
        if ($this->isOk()) {
            $this->setStreamOrFile($streamOrFile);
        }
    }

    /**
     * Depending on the value set file or stream variable
     *
     * @param mixed $streamOrFile
     *
     * @throws InvalidArgumentException
     */
    private function setStreamOrFile($streamOrFile) {
        if (is_string($streamOrFile)) {
            $this->file = $streamOrFile;
        } elseif (is_resource($streamOrFile)) {
            $this->stream = new Stream($streamOrFile);
        } elseif ($streamOrFile instanceof StreamInterface) {
            $this->stream = $streamOrFile;
        } else {
            throw new InvalidArgumentException('Invalid stream or file provided for UploadedFile');
        }
    }

    /**
     * @param int $error
     *
     * @throws InvalidArgumentException
     */
    private function setError($error) {
        if (FALSE === is_int($error)) {
            throw new InvalidArgumentException('Upload file error status must be an integer');
        }
        if (FALSE === in_array($error, UploadedFile::$errors)) {
            throw new InvalidArgumentException('Invalid error status for UploadedFile');
        }
        $this->error = $error;
    }

    /**
     * @param int $size
     *
     * @throws InvalidArgumentException
     */
    private function setSize($size) {
        if (FALSE === is_int($size)) {
            throw new InvalidArgumentException('Upload file size must be an integer');
        }
        $this->size = $size;
    }

    /**
     * @param mixed $param
     *
     * @return boolean
     */
    private function isStringOrNull($param) {
        return in_array(gettype($param), ['string', 'NULL']);
    }

    /**
     * @param mixed $param
     *
     * @return boolean
     */
    private function isStringNotEmpty($param) {
        return is_string($param) && FALSE === empty($param);
    }

    /**
     * @param string|null $clientFilename
     *
     * @throws InvalidArgumentException
     */
    private function setClientFilename($clientFilename) {
        if (FALSE === $this->isStringOrNull($clientFilename)) {
            throw new InvalidArgumentException('Upload file client filename must be a string or null');
        }
        $this->clientFilename = $clientFilename;
    }

    /**
     * @param string|null $clientMediaType
     *
     * @throws InvalidArgumentException
     */
    private function setClientMediaType($clientMediaType) {
        if (FALSE === $this->isStringOrNull($clientMediaType)) {
            throw new InvalidArgumentException('Upload file client media type must be a string or null');
        }
        $this->clientMediaType = $clientMediaType;
    }

    /**
     * Return true if there is no upload error
     *
     * @return boolean
     */
    private function isOk() {
        return $this->error === UPLOAD_ERR_OK;
    }

    /**
     * @return boolean
     */
    public function isMoved() {
        return $this->moved;
    }

    /**
     * @throws RuntimeException if is moved or not ok
     */
    private function validateActive() {
        if (FALSE === $this->isOk()) {
            throw new RuntimeException('Cannot retrieve stream due to upload error');
        }
        if ($this->isMoved()) {
            throw new RuntimeException('Cannot retrieve stream after it has already been moved');
        }
    }

    /**
     * {@inheritdoc}
     * @throws RuntimeException if the upload was not successful.
     */
    public function getStream() {
        $this->validateActive();
        if ($this->stream instanceof StreamInterface) {
            return $this->stream;
        }
        return new LazyOpenStream($this->file, 'r+');
    }

    /**
     * {@inheritdoc}
     *
     * @see http://php.net/is_uploaded_file
     * @see http://php.net/move_uploaded_file
     *
     * @param string $targetPath Path to which to move the uploaded file.
     *
     * @throws RuntimeException if the upload was not successful.
     * @throws InvalidArgumentException if the $path specified is invalid.
     * @throws RuntimeException on any error during the move operation, or on
     *     the second or subsequent call to the method.
     */
    public function moveTo($targetPath) {
        $this->validateActive();
        if (FALSE === $this->isStringNotEmpty($targetPath)) {
            throw new InvalidArgumentException('Invalid path provided for move operation; must be a non-empty string');
        }
        if ($this->file) {
            $this->moved = php_sapi_name() == 'cli' ? rename($this->file, $targetPath) : move_uploaded_file($this->file, $targetPath);
        } else {
            $this->copyToStream($this->getStream(), new LazyOpenStream($targetPath, 'w'));
            $this->moved = TRUE;
        }
        if (FALSE === $this->moved) {
            throw new RuntimeException(sprintf('Uploaded file could not be moved to %s', $targetPath));
        }
    }

    /**
     * {@inheritdoc}
     *
     * @return int|null The file size in bytes or null if unknown.
     */
    public function getSize() {
        return $this->size;
    }

    /**
     * {@inheritdoc}
     *
     * @see http://php.net/manual/en/features.file-upload.errors.php
     * @return int One of PHP's UPLOAD_ERR_XXX constants.
     */
    public function getError() {
        return $this->error;
    }

    /**
     * {@inheritdoc}
     *
     * @return string|null The filename sent by the client or null if none
     *     was provided.
     */
    public function getClientFilename() {
        return $this->clientFilename;
    }

    /**
     * {@inheritdoc}
     */
    public function getClientMediaType() {
        return $this->clientMediaType;
    }

    /**
     * @param StreamInterface $src
     * @param StreamInterface $dst
     * @param int             $maxLen
     */
    private function copyToStream(StreamInterface $src, StreamInterface $dst, $maxLen = -1) {
        if ($maxLen === -1) {
            while (!$src->eof()) {
                if (!$dst->write($src->read(1048576))) {
                    break;
                }
            }
            return;
        }
        $bytes = 0;
        while (!$src->eof()) {
            $buf = $src->read($maxLen - $bytes);
            if (!($len = strlen($buf))) {
                break;
            }
            $bytes += $len;
            $dst->write($buf);
            if ($bytes == $maxLen) {
                break;
            }
        }
    }
}
