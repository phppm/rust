<?php
/**
 * Whoops - php errors for cool kids
 * @author Filipe Dobreira <http://github.com/filp>
 */

namespace rust\exception;

class Inspector {
    /**
     * @var \Throwable
     */
    private $exception;

    /**
     * @var array
     */
    private $handlers;

    /**
     * @var \rust\exception\FrameCollection
     */
    private $frames;

    /**
     * @var \rust\exception\Inspector
     */
    private $previousExceptionInspector;

    /**
     * @param \Throwable $exception The exception to inspect
     */
    public function __construct($exception, $handlers) {
        $this->exception = $exception;
        $this->handlers = $handlers;
    }

    /**
     * @return \Throwable
     */
    public function getException() {
        return $this->exception;
    }

    /**
     * @return string
     */
    public function getExceptionName() {
        return get_class($this->exception);
    }

    /**
     * @return string
     */
    public function getExceptionMessage() {
        return $this->exception->getMessage();
    }

    /**
     * @return array
     */
    public function getHandlers() {
        return $this->handlers;
    }

    /**
     * Does the wrapped Exception has a previous Exception?
     * @return bool
     */
    public function hasPreviousException() {
        return $this->previousExceptionInspector || $this->exception->getPrevious();
    }

    /**
     * Returns an Inspector for a previous Exception, if any.
     * @todo   Clean this up a bit, cache stuff a bit better.
     * @return Inspector
     */
    public function getPreviousExceptionInspector() {
        if ($this->previousExceptionInspector === NULL) {
            $previousException = $this->exception->getPrevious();

            if ($previousException) {
                $this->previousExceptionInspector = new Inspector($previousException);
            }
        }

        return $this->previousExceptionInspector;
    }

    /**
     * Returns an iterator for the inspected exception's
     * frames.
     * @return \rust\exception\FrameCollection
     */
    public function getFrames() {
        if ($this->frames === NULL) {
            $frames = $this->getTrace($this->exception);

            // Fill empty line/file info for call_user_func_array usages (PHP Bug #44428) 
            foreach ($frames as $k => $frame) {

                if (empty($frame['file'])) {
                    // Default values when file and line are missing
                    $file = '[internal]';
                    $line = 0;

                    $next_frame = !empty($frames[$k + 1]) ? $frames[$k + 1] : [];

                    if ($this->isValidNextFrame($next_frame)) {
                        $file = $next_frame['file'];
                        $line = $next_frame['line'];
                    }

                    $frames[$k]['file'] = $file;
                    $frames[$k]['line'] = $line;
                }

            }

            // Find latest non-error handling frame index ($i) used to remove error handling frames
            $i = 0;
            foreach ($frames as $k => $frame) {
                if ($frame['file'] == $this->exception->getFile() && $frame['line'] == $this->exception->getLine()) {
                    $i = $k;
                }
            }

            // Remove error handling frames
            if ($i > 0) {
                array_splice($frames, 0, $i);
            }

            $firstFrame = $this->getFrameFromException($this->exception);
            array_unshift($frames, $firstFrame);

            $this->frames = new FrameCollection($frames);

            if ($previousInspector = $this->getPreviousExceptionInspector()) {
                // Keep outer frame on top of the inner one
                $outerFrames = $this->frames;
                $newFrames = clone $previousInspector->getFrames();
                // I assume it will always be set, but let's be safe
                if (isset($newFrames[0])) {
                    $newFrames[0]->addComment($previousInspector->getExceptionMessage(), 'Exception message:');
                }
                $newFrames->prependFrames($outerFrames->topDiff($newFrames));
                $this->frames = $newFrames;
            }
        }

        return $this->frames;
    }

    /**
     * Gets the trace from an exception.
     *
     * @param  \Throwable $e
     * @return array
     */
    protected function getTrace($e) {
        $traces = $e->getTrace();

        // Get trace from xdebug if enabled, failure exceptions only trace to the shutdown handler by default
        if (!$e instanceof \ErrorException) {
            return $traces;
        }

        if (!$this->isLevelFatal($e->getSeverity())) {
            return $traces;
        }

        if (!extension_loaded('xdebug') || !xdebug_is_enabled()) {
            return [];
        }

        // Use xdebug to get the full stack trace and remove the shutdown handler stack trace
        $stack = array_reverse(xdebug_get_function_stack());
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        $traces = array_diff_key($stack, $trace);

        return $traces;
    }

    /**
     * Given an exception, generates an array in the format
     * generated by Exception::getTrace()
     * @param  \Throwable $exception
     * @return array
     */
    protected function getFrameFromException($exception) {
        return [
            'file'  => $exception->getFile(),
            'line'  => $exception->getLine(),
            'class' => get_class($exception),
            'args'  => [
                $exception->getMessage(),
            ],
        ];
    }

    /**
     * Given an error, generates an array in the format
     * generated by ErrorException
     * @param  ErrorException $exception
     * @return array
     */
    protected function getFrameFromError(ErrorException $exception) {
        return [
            'file'  => $exception->getFile(),
            'line'  => $exception->getLine(),
            'class' => NULL,
            'args'  => [],
        ];
    }

    /**
     * Determine if the frame can be used to fill in previous frame's missing info
     * happens for call_user_func and call_user_func_array usages (PHP Bug #44428)
     *
     * @param array $frame
     * @return bool
     */
    protected function isValidNextFrame(array $frame) {
        if (empty($frame['file']) || empty($frame['line'])) {
            return FALSE;
        }

        if (empty($frame['function']) || !stristr($frame['function'], 'call_user_func')) {
            return FALSE;
        }

        return TRUE;
    }


    /**
     * Determine if an error level is fatal (halts execution)
     *
     * @param int $level
     * @return bool
     */
    protected function isLevelFatal($level) {
        $errors = E_ERROR;
        $errors |= E_PARSE;
        $errors |= E_CORE_ERROR;
        $errors |= E_CORE_WARNING;
        $errors |= E_COMPILE_ERROR;
        $errors |= E_COMPILE_WARNING;
        return ($level & $errors) > 0;
    }
}
