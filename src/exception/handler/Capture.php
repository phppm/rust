<?php
namespace rust\exception\handler;
use InvalidArgumentException;
use rust\exception\ErrorException;
use rust\exception\Inspector;
use rust\util\Config;
use rust\util\Result;

/**
 * Class CaptureHandler
 *
 * @package rust/exception
 * @author  rustysun.cn@gmail.com
 */
class Capture {
    //handler
    const EXCEPTION_HANDLER = 'handleException';
    const ERROR_HANDLER     = 'handleError';
    const SHUTDOWN_HANDLER  = 'handleShutdown';
    protected $is_registered;
    protected $can_throw_exception;

    //protected $request;
    /**
     * @var Config
     */
    //protected $appConfig;
    /**
     * @var ExceptionHandler[]
     */
    private $handlerStack = [];
    /**
     * In certain scenarios, like in shutdown handler, we can not throw exceptions
     *
     * @var bool
     */
    private $_can_throw_exceptions = TRUE;

    /**
     * Pushes a handler to the end of the stack
     *
     * @throws InvalidArgumentException  If argument is not callable or instance of HandlerInterface
     *
     * @param  ExceptionHandler $handler
     *
     * @return Capture
     */
    public function pushHandler($handler) {
        if (!$handler instanceof ExceptionHandler) {
            throw new InvalidArgumentException("Argument to " . __METHOD__ . " must be a callable, or instance of ");
        }
        $this->handlerStack[] = $handler;
        return $this;
    }

    /**
     * Registers this instance as an error handler.
     * @return Capture
     */
    public function register() {
        if ($this->is_registered) {
            return $this;
        }
        /*
        if (!$app_config || !$app_config instanceof Config) {
            return $this;
        }
        $this->appConfig = $app_config;
        $uri_config = $app_config->get('uri');
        $this->request = new Request($uri_config);;
        */
        class_exists('\\Error', FALSE) or class_exists('\\rust\\exception\\Error');
        class_exists('\\rust\\exception\\ErrorException');
        set_error_handler([$this, self::ERROR_HANDLER]);
        set_exception_handler([$this, self::EXCEPTION_HANDLER]);
        register_shutdown_function([$this, self::SHUTDOWN_HANDLER]);
        $this->is_registered = TRUE;
        return $this;
    }

    /**
     * Converts generic PHP errors to \ErrorException
     * instances, before passing them off to be handled.
     *
     * This method MUST be compatible with set_error_handler.
     *
     * @param int    $level
     * @param string $message
     * @param string $file
     * @param int    $line
     *
     * @return bool
     * @throws ErrorException
     */
    public function handleError($level, $message, $file = NULL, $line = NULL) {
        if ($level & error_reporting()) {
            $exception = new ErrorException($message, 9999, $level, $file, $line);
            if ($this->_can_throw_exceptions) {
                throw $exception;
            }
            $this->handleException($exception);
            return TRUE;
        }
        return FALSE;
    }

    /**
     * @param \Exception $exception
     *
     * @throws \rust\exception\RuntimeException
     */
    public function handleException($exception) {
        $code = $exception->getCode();
        $msg  = $exception->getMessage();
        if ($code < 10000 || $code > 60000) {
            $code = 99999;
        }
        $result = new Result($code,$msg);
        $inspector       = $this->getInspector($exception);
        $handlerResponse = NULL;
        foreach (array_reverse($this->handlerStack) as $handler) {
            if (!$handler instanceof ExceptionHandler) {
                continue;
            }
            $handler->setInspector($inspector);
            $handler->setException($exception);
            $handlerResponse = $handler->handle($result);
            if (in_array($handlerResponse, [ExceptionHandler::LAST_HANDLER, ExceptionHandler::QUIT])) {
                break;
            }
        }
    }

    /**
     * @throws ErrorException
     */
    public function handleShutdown() {
        $this->_can_throw_exceptions = FALSE;
        $error                       = error_get_last();
        if ($error && $this->isLevelFatal($error['type'])) {
            $this->handleError($error['type'], $error['message'], $error['file'], $error['line']);
        }
    }

    /**
     * @param  \Throwable $exception
     *
     * @return Inspector
     */
    private function getInspector($exception) {
        return new Inspector($exception, $this->handlerStack);
    }

    /**
     * @param $level
     *
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
