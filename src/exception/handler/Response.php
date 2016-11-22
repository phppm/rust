<?php
namespace rust\exception\handler;

use InvalidArgumentException;

class Response {
    const DONE = 0x10;
    const LAST_HANDLER = 0x20;
    const QUIT = 0x30;

    protected $format;

    /**
     * @var array[]
     */
    private $extraTables = [];

    /**
     * @var bool
     */
    private $handleUnconditionally = FALSE;

    /**
     * Response constructor.
     * @param $format
     */
    public function __construct($format) {
        $this->format = $format;
    }

    /**
     * @return int|null
     */
    public function handle($exception) {
        if (!$this->handleUnconditionally()) {
            // Check conditions for outputting HTML:
            // @todo: Make this more robust
            if (php_sapi_name() === 'cli') {
                // Help users who have been relying on an internal test value
                // fix their code to the proper method
                if (isset($_ENV['whoops-test'])) {
                    throw new \Exception('Use handleUnconditionally instead of whoops-test' . ' environment variable');
                }

                return self::DONE;
            }
        }
        /*
                $inspector = $this->getInspector();
                $frames = $inspector->getFrames();
        
                $code = $inspector->getException()->getCode();
        
                if ($inspector->getException() instanceof \ErrorException) {
                    // ErrorExceptions wrap the php-error types within the "severity" property
                    $code = Misc::translateErrorCode($inspector->getException()->getSeverity());
                }
        
                // List of variables that will be passed to the layout template.
                $vars = [
                    "page_title"  => $this->getPageTitle(),
        
                    // @todo: Asset compiler
                    "stylesheet"  => file_get_contents($cssFile),
                    "zepto"       => file_get_contents($zeptoFile),
                    "javascript"  => file_get_contents($jsFile),
        
                    // Template paths:
                    "header"      => $this->getResource("views/header.html.php"),
                    "frame_list"  => $this->getResource("views/frame_list.html.php"),
                    "frame_code"  => $this->getResource("views/frame_code.html.php"),
                    "env_details" => $this->getResource("views/env_details.html.php"),
        
                    "title"           => "Whoops! There was an error.",
                    "name"            => explode("\\", $inspector->getExceptionName()),
                    "message"         => $inspector->getException()->getMessage(),
                    "code"            => $code,
                    "plain_exception" => Formatter::formatExceptionPlain($inspector),
                    "frames"          => $frames,
                    "has_frames"      => !!count($frames),
                    "handler"         => $this,
                    "handlers"        => $this->getRun()->getHandlers(),
        
                    "tables" => [
                        "GET Data"              => $_GET,
                        "POST Data"             => $_POST,
                        "Files"                 => $_FILES,
                        "Cookies"               => $_COOKIE,
                        "Session"               => isset($_SESSION) ? $_SESSION : [],
                        "Server/Request Data"   => $_SERVER,
                        "Environment Variables" => $_ENV,
                    ],
                ];
        
                if (isset($customCssFile)) {
                    $vars["stylesheet"] .= file_get_contents($customCssFile);
                }
        
                // Add extra entries list of data tables:
                // @todo: Consolidate addDataTable and addDataTableCallback
                $extraTables = array_map(function ($table) {
                    return $table instanceof \Closure ? $table() : $table;
                }, $this->getDataTables());
                $vars["tables"] = array_merge($extraTables, $vars["tables"]);
        
                $helper->setVariables($vars);
                $helper->render($templateFile);
        */
        return self::QUIT;
    }

    /**
     * Adds an entry to the list of tables displayed in the template.
     * The expected data is a simple associative array. Any nested arrays
     * will be flattened with print_r
     * @param string $label
     * @param array $data
     */
    public function addDataTable($label, array $data) {
        $this->extraTables[$label] = $data;
    }

    /**
     * Lazily adds an entry to the list of tables displayed in the table.
     * The supplied callback argument will be called when the error is rendered,
     * it should produce a simple associative array. Any nested arrays will
     * be flattened with print_r.
     *
     * @throws InvalidArgumentException If $callback is not callable
     * @param  string $label
     * @param  callable $callback Callable returning an associative array
     */
    public function addDataTableCallback($label, /* callable */
                                         $callback) {
        if (!is_callable($callback)) {
            throw new InvalidArgumentException('Expecting callback argument to be callable');
        }

        $this->extraTables[$label] = function () use ($callback) {
            try {
                $result = call_user_func($callback);

                // Only return the result if it can be iterated over by foreach().
                return is_array($result) || $result instanceof \Traversable ? $result : [];
            } catch (\Exception $e) {
                // Don't allow failure to break the rendering of the original exception.
                return [];
            }
        };
    }

    /**
     * Returns all the extra data tables registered with this handler.
     * Optionally accepts a 'label' parameter, to only return the data
     * table under that label.
     * @param  string|null $label
     * @return array[]|callable
     */
    public function getDataTables($label = NULL) {
        if ($label !== NULL) {
            return isset($this->extraTables[$label]) ? $this->extraTables[$label] : [];
        }

        return $this->extraTables;
    }

    /**
     * Allows to disable all attempts to dynamically decide whether to
     * handle or return prematurely.
     * Set this to ensure that the handler will perform no matter what.
     * @param  bool|null $value
     * @return bool|null
     */
    public function handleUnconditionally($value = NULL) {
        if (func_num_args() == 0) {
            return $this->handleUnconditionally;
        }

        $this->handleUnconditionally = (bool) $value;
    }

    public function output() {
        /*
                $response = PHPKit_Response::getInstance();
                $request = PHPKit_Request::getInstance();
                $configInstance=PHPKit_Config::getInstance();
                $pathInfo=$configInstance::get('path');
                $viewPath = $pathInfo['business'];
                $view = new PHPKit_MVC_View();
                $view->setPath($viewPath);
                $response->write($view->render('common/error'));
                $response->send();
        */
    }
}