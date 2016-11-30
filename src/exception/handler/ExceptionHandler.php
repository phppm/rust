<?php
/**
 * Whoops - php errors for cool kids
 *
 * @author Filipe Dobreira <http://github.com/filp>
 */
namespace rust\exception\handler;
use rust\common\Config;
use rust\exception\Formatter;
use rust\exception\Inspector;
use rust\http\Response;
use rust\util\Log;
use rust\util\Result;

/**
 * a Exception Handler.
 */
class ExceptionHandler {
    /**
     * Return constants that can be returned from Handler::handle
     * to message the handler walker.
     */
    const DONE = 0x10; // returning this is optional, only exists for
    // semantic purposes
    const LAST_HANDLER = 0x20;
    const QUIT         = 0x30;
    /**
     * @var Inspector $inspector
     */
    private $inspector;
    /**
     * @var \Throwable $exception
     */
    private $exception;
    /**
     * @var Config
     */
    private $config;

    public function __construct(Config $config = NULL) {
        $this->config = $config;
    }

    /**
     * @param Result $result
     *
     * @return int
     */
    public function handle($result = NULL) {
        if (php_sapi_name() === 'cli') {
            return ExceptionHandler::DONE;
        }
        $inspector = $this->getInspector();
        $frames    = $inspector->getFrames();
        $exception = $inspector->getException();
        $code      = $exception->getCode();
        if ($exception instanceof \ErrorException) {
            // ErrorExceptions wrap the php-error types within the "severity" property
            ////$code = Misc::translateErrorCode($inspector->getException()->getSeverity());
        }
        // List of variables that will be passed to the layout template.
        $vars         = [
            "title"           => 'Whoops! There was an error.',
            "name"            => explode("\\", $inspector->getExceptionName()),
            "message"         => $inspector->getException()->getMessage(),
            "code"            => $code,
            "plain_exception" => Formatter::formatExceptionPlain($inspector),
            "frames"          => $frames,
            "has_frames"      => !!count($frames),
            "handler"         => $this,
            "handlers"        => $inspector->getHandlers(),
            "tables"          => [
                "GET Data"              => $_GET,
                "POST Data"             => $_POST,
                "Files"                 => $_FILES,
                "Cookies"               => $_COOKIE,
                "Session"               => isset($_SESSION) ? $_SESSION : [],
                "Server/Request Data"   => $_SERVER,
                "Environment Variables" => $_ENV,
            ],
        ];
        $result->data = $vars;
        Log::write($result, 'error');
        return ExceptionHandler::QUIT;
    }

    /**
     * @param Inspector $inspector
     */
    public function setInspector(Inspector $inspector) {
        $this->inspector = $inspector;
    }

    /**
     * @return Inspector
     */
    protected function getInspector() {
        return $this->inspector;
    }

    /**
     * @param \Throwable $exception
     */
    public function setException($exception) {
        $this->exception = $exception;
    }

    /**
     * 获取错误配置
     *
     * @return Config
     */
    protected function getConfig() {
        return $this->config;
    }

    /**
     * @return \Throwable
     */
    protected function getException() {
        return $this->exception;
    }

    /**
     * TODO:output
     */
    public function output() {
        $response = new Response();
        //$response->write($this->view->render('exception/whoops'));
        $response->send();
    }
}
