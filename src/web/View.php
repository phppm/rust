<?php
namespace rust\web;

use rust\fso\FileSystemObject;
use rust\http\URL;
use rust\interfaces\IView;
use rust\Path;
use rust\template\Compiler;
use rust\util\Buffer;

/**
 * Class View
 *
 * @package rust\web
 */
final class View implements IView {
    private $_suffix    = '.html';
    private $_path;
    private $_curLayout = NULL, $_curBlock;
    private $_data      = [
        'layouts' => [],
        'blocks'  => [],
        'vars'    => [],
    ];
    private $_blockPre  = '%%BLOCK__', $_blockSuf = '__BLOCK%%';
    /**
     * @var Uri
     */
    private $_uri;
    private $_compiler;

    /**
     * View constructor.
     *
     * @param Uri $uri
     */
    public function __construct(Uri $uri) {
        $this->_data['vars'] = [
            'view' => &$this,
        ];
        $this->setPath(Path::getRootPath());
        $this->_uri = $uri;
    }

    /**
     *
     *
     * @param string|array $name
     * @param              $value
     */
    public function assign($name, $value = NULL): Void {
        if ('view' !== $name) { //protected 'view' variable.
            if (is_array($name) && $name) {
                unset($name['view']);
                $this->_data['vars'] = array_merge($this->_data['vars'], $name);
            } else {
                $this->_data['vars'][$name] = $value;
            }
        } else {
            //TODO: throw execption.
        }
    }

    /**
     * clean buffer
     */
    public function end() {
        Buffer::clean();
    }

    /**
     * @param      $block_name
     * @param null $val
     */
    public function beginBlock($block_name, $val = NULL) {
        $block_name = strtoupper($block_name);
        $this->_curBlock = $block_name;
        if (NULL !== $val) {
            $this->_data['blocks'][$this->_curBlock] = $val;
            //TODO:exception
            //return FALSE;
        }
        Buffer::start();
        //return TRUE;
    }

    /**
     *
     */
    public function endBlock() {
        $content = Buffer::getAndClean();
        if (!isset($this->_data['blocks'][$this->_curBlock])) {
            echo $this->_blockPre . $this->_curBlock . $this->_blockSuf;
        }
        $this->_data['blocks'][$this->_curBlock] = trim($content);
    }

    /**
     *
     * @param $block_name
     */
    public function beginLayout($block_name) {
        $block_name = strtoupper($block_name);
        if (isset($this->_data['blocks'][$block_name])) {
            $this->_curLayout = $block_name;
            Buffer::start();
        } else {
            $this->_curLayout = NULL;
        }
    }

    /**
     */
    public function endLayout() {
        if (NULL === $this->_curLayout) {
            //TODO:
            //return FALSE;
        }
        $content = Buffer::getAndClean();
        $this->_data['layouts'][$this->_curLayout] = trim($content);
        $this->_curLayout = NULL;
    }

    /**
     * @param $view
     */
    public function load($view) {
        $view = str_replace('\\', '/', $view);
        $viewFile = $view . $this->_suffix;
        $viewFile = $this->_getCompiler()->compile($viewFile);
        $this->_renderFile($viewFile);
    }

    /**
     * 将指定的block填入挖好的坑
     *
     * @param string $block
     */
    public function place($block) {
        $blockName = strtoupper($block);
        if (!isset($this->_data['blocks'][$blockName])) {
            $this->_data['blocks'][$blockName] = '';
            echo $this->_blockPre . $blockName . $this->_blockSuf;
        }
    }

    /**
     *
     * @param $view
     *
     * @return mixed
     */
    public function render($view) {
        $viewFile = $view . $this->_suffix;
        $viewFile = $this->_getCompiler()->compile($viewFile);
        Buffer::start();
        $this->_renderFile($viewFile);
        $result = Buffer::getAndClean();
        //parse layout
        $keys = array_keys($this->_data['layouts']);
        foreach ($keys as $key => $value) {
            $keys[$key] = $this->_blockPre . $value . $this->_blockSuf;
            unset($this->_data['blocks'][$value]);
        }
        $values = array_values($this->_data['layouts']);
        $result = str_replace($keys, $values, $result);
        //parse block
        $keys = array_keys($this->_data['blocks']);
        foreach ($keys as $key => $value) {
            $keys[$key] = $this->_blockPre . $value . $this->_blockSuf;
        }
        $values = array_values($this->_data['blocks']);
        return str_replace($keys, $values, $result);
    }

    /**
     *
     * @param $path
     */
    public function setPath($path) {
        $this->_path = $path;
    }

    /**
     *
     * @param string $path
     * @param array  $params
     * @param string $site
     *
     * @return string
     */
    public function url($path = '', $params = [], $site = '') {
        if (is_string($params)) {
            $site = $params;
            $params = [];
        }
        URL::setDomain($this->_uri->getHost(), $this->_uri->getMainDomain());
        $url = URL::create($path, $site, $params);
        return $url;
    }

    /**
     * TODO:remove to string?
     * Escapes a string for output in an HTML document
     *
     * @param  string $raw
     *
     * @return string
     */
    public function escape($raw) {
        $flags = ENT_QUOTES;
        // HHVM has all constants defined, but only ENT_IGNORE
        // works at the moment
        if (defined('ENT_SUBSTITUTE') && !defined('HHVM_VERSION')) {
            $flags |= ENT_SUBSTITUTE;
        } else {
            // This is for 5.3.
            // The documentation warns of a potential security issue,
            // but it seems it does not apply in our case, because
            // we do not blacklist anything anywhere.
            $flags |= ENT_IGNORE;
        }
        return htmlspecialchars($raw, $flags, "UTF-8");
    }

    /**
     * TODO:remove to string?
     * Escapes a string for output in an HTML document, but preserves
     * URIs within it, and converts them to clickable anchor elements.
     *
     * @param  string $raw
     *
     * @return string
     */
    public function escapeButPreserveUris($raw) {
        $escaped = $this->escape($raw);
        return preg_replace("@([A-z]+?://([-\w\.]+[-\w])+(:\d+)?(/([\w/_\.#-]*(\?\S+)?[^\.\s])?)?)@", "<a href=\"$1\" target=\"_blank\">$1</a>", $escaped);
    }

    /**
     * TODO:remove to string
     *
     * @param $original
     *
     * @return string
     */
    public function slug($original) {
        $slug = str_replace(" ", "-", $original);
        $slug = preg_replace('/[^\w\d\-\_]/i', '', $slug);
        return strtolower($slug);
    }

    /**
     * @param $filename
     */
    private function _renderFile($filename) {
        if (!$filename) {
            return;
        }
        $data = $this->_data['vars'];
        if (NULL !== $data) {
            extract($data);
        }
        require $filename;
    }

    /**
     * Get a compiler of view.
     *
     * @return Compiler
     */
    private function _getCompiler() {
        if (!$this->_compiler) {
            $fso = new FileSystemObject();
            $this->_compiler = new Compiler($fso, Path::getCachePath(), $this->_path);
        }
        return $this->_compiler;
    }
}
