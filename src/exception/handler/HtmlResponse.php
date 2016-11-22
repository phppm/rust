<?php
namespace rust\exception\handler;

use rust\Rust;
use rust\util\Registry;
use rust\util\Config;
use rust\web\View;
use rust\http\Response;
use InvalidArgumentException;
use rust\web\App;

class HtmlResponse {
    const DONE = 0x10;
    const LAST_HANDLER = 0x20;
    const QUIT = 0x30;

    /**
     * @var View
     */
    protected $view;

    /**
     * @var
     */
    private $frames;

    /**
     * @var array[]
     */
    private $extraTables = [];

    /**
     * @var bool
     */
    private $handleUnconditionally = FALSE;

    /**
     * @param \rust\exception\ErrorException $exception
     * @param $request
     * @return int
     * @throws \Exception
     */
    public function handle($exception, $request) {
        if (!$this->handleUnconditionally()) {
            // Check conditions for outputting HTML:
            // @todo: Make this more robust
            if (php_sapi_name() === 'cli') {
                if (isset($_ENV['whoops-test'])) {
                    throw new \Exception('Use handleUnconditionally instead of whoops-test' . ' environment variable');
                }

                return self::DONE;
            }
        }
        $app_config = Registry::get(Rust::APP_CONFIG);
        if (!$app_config || !$app_config instanceof Config) {
            return self::DONE;
        }
        $view_path = $app_config->get('path')->get('view');
        if (!$view_path) {
            return self::DONE;
        }
        $inspector = $this->getInspector();
        $frames = $inspector->getFrames();

        $view = new View($app_config, $request);
        $view->setPath($view_path);
        $view->assign([
            'title'      => "Whoops! There was an error.",
            'message'    => $exception->getMessage(),
            "code"       => $exception->getCode(),
            "frames"     => $frames,
            "has_frames" => !!count($frames),
            "tables"     => [
                "GET Data"              => $_GET,
                "POST Data"             => $_POST,
                "Files"                 => $_FILES,
                "Cookies"               => $_COOKIE,
                "Session"               => isset($_SESSION) ? $_SESSION : [],
                "Server/Request Data"   => $_SERVER,
                "Environment Variables" => $_ENV,
            ],
        ]);
        $this->view = $view;
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
        $response = new Response();
        $response->write($this->view->render('exception/whoops'));
        $response->send();
    }
}