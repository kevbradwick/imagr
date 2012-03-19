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
     * @var Request
     */
    protected $request;

    /**
     * Class construct
     */
    public function __construct()
    {
        $this->config = array_replace_recursive(
            $this->defaultConfig(),
            $this->getCustomConfig()
        );

        if ($this->getConfig('debug') === true) {
            ini_set('display_errors', 1);
            error_reporting(E_ALL);
        }
    }

    /**
     * Set the request object
     *
     * @param Request $request
     */
    public function setRequest(Request $request)
    {
        $this->request = $request;
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
            'cache_dir' => realpath(__DIR__ . '/../../cache'),
            'tmp_dir' => realpath(__DIR__ . '/../../tmp'),
            'cache_time' => 0,
            'debug' => false,
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
        $configFile = realpath(__DIR__ . '/../../imagr-config.php');

        if (file_exists($configFile) === true) {
            return include $configFile;
        }

        return array();
    }

    /**
     * Get the cache key for this request
     *
     * @return string
     */
    protected function getCacheKey()
    {
        $src    = $this->request->get('src', '');
        $width  = (int) $this->request->get('w', 100);
        $height = (int) $this->request->get('h', 100);

        return md5($src . $width . $height);
    }

    /**
     * Process this request
     *
     * @return null
     */
    public function process()
    {
        if ($this->cache->has($this->getCacheKey()) === true) {
            $remote = $this->cache->get($this->getCacheKey());
        } else {
            $remote = new Remote($this->request->get('src'));
            $this->cache->set($this->getCacheKey(), $remote, (int) $this->getConfig('cache_time', 0));
        }

        $remote->setHeaders();
        echo $remote->getContent();
    }
}