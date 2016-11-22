<?php
namespace rust;
use rust\interfaces\IApplication;
use rust\interfaces\IConfig;

/**
 * Class Application
 *
 * @package rust
 */
abstract class Application implements IApplication {
    protected $appName;
    protected $basePath;
    /**
     * @var IConfig
     */
    protected $config;
    /**
     * @var Application
     */
    static $instance;

    /**
     * clone
     */
    private function __clone() {
    }

    /**
     * Application constructor.
     *
     * @param string  $name
     * @param string  $base_path
     * @param IConfig $config
     */
    public function __construct($name, $base_path, IConfig $config) {
        $this->appName = $name;
        $this->setBasePath($base_path);
        $this->config = $config;
        static::setInstance($this);
    }

    public function init() {
        //init path
        Path::init($this->getBasePath());
    }

    /**
     * @return bool
     */
    public function run() {
        return TRUE;
    }

    public function getBasePath() {
        return $this->basePath;
    }

    /**
     * Set application base path
     *
     * @param string $base_path
     */
    public function setBasePath($base_path) {
        $this->basePath = $base_path;
    }

    /**
     * Set the globally available instance of the container.
     *
     * @return static
     */
    public static function getInstance() {
        return static::$instance;
    }

    /**
     * Set the shared instance of the container.
     *
     * @param  Application $app
     *
     * @return void
     */
    public static function setInstance(Application $app) {
        static::$instance = $app;
    }
}