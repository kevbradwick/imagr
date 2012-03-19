<?php

namespace Imagr;

/**
 * Application
 *
 * The image processing application class
 *
 * @author  Kevin Bradwick <kbradwick@gmail.com>
 * @package Imagr
 * @license http://www.opensource.org/licenses/bsd-license.php
 */
class Imagr
{
    /**
     * @var array
     */
    protected $config = array();

    /**
     * @var Cache
     */
    protected $cache;

    /**
     * @var Curl
     */
    private $curl;

    /**
     * Class construct
     */
    public function __construct()
    {

    }

    /**
     * Set the cache driver
     *
     * @param Cache $cache
     */
    public function setCache(Cache $cache)
    {
        $this->cache = $cache;
    }

    /**
     * The default configuration array
     *
     * @return array
     */
    protected function defaultConfig()
    {
        return array(
            'cache_dir' => __DIR__ . '/cache'
        );
    }

    /**
     * Attempt to read from the config array, get a value or return a default of not set
     *
     * @param string $name
     * @param mixed  $default
     * @return null
     */
    public function getConfig($name, $default=null)
    {
        if (isset($this->config[$name]) === true) {
            return $this->config[$name];
        }

        return $default;
    }

    /**
     * Attempts to locate the configuration file called 'imagr-config.php' in the same directory as this script and if
     * it exists, return its contents as an array, else, return an empty array
     *
     * @return array|mixed
     */
    public function getCustomConfig()
    {
        $configFile = __DIR__ . '/imagr-config.php';

        if (file_exists($configFile) === true) {
            return include $configFile;
        }

        return array();
    }

    /**
     * Obtain a GET parameter or return a default value if not set
     *
     * @param string $name
     * @param mixed $default
     * @return mixed
     */
    protected function getParam($name, $default=null)
    {
        if (array_key_exists($name, $_GET) === true) {
            return trim($_GET[$name]);
        }

        return $default;
    }

    /**
     * Get the image src url
     *
     * @return string
     */
    protected function getSrc()
    {
        return $this->getParam('src', '');
    }

    /**
     * Get the width parameter
     *
     * @return int
     */
    protected function getWidth()
    {
        return (int) $this->getParam('w', 100);
    }

    /**
     * Get the height parameter
     *
     * @return int
     */
    protected function getHeight()
    {
        return (int) $this->getParam('h', 100);
    }

    /**
     * Get the cache key for this request
     *
     * @return string
     */
    protected function getCacheKey()
    {
        return md5($this->getSrc() . $this->getWidth() . $this->getHeight());
    }

    /**
     * Process this request
     *
     * @return null
     */
    public function process()
    {
        if ($this->cache->has($this->getCacheKey()) === true) {
            //.. server cached version
        }

        $tmp = $this->curl->get($this->getSrc());
    }
}