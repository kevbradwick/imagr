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
    const VERSION = '0.1';

    /**
     * @var array
     */
    protected $config = array();

    /**
     * @var Cache
     */
    protected $imageCache;

    /**
     * @var Cache
     */
    protected $remoteCache;

    /**
     * @var Request
     */
    protected $request;

    /**
     * Class construct
     */
    public function __construct()
    {
        // merge custom config to default config
        $this->config = array_replace_recursive(
            $this->defaultConfig(),
            $this->getCustomConfig()
        );

        // scream errors and notices if in debug mode
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
     * Set the cache driver for remote request objects
     *
     * @param Cache $cache
     */
    public function setRemoteCache(Cache $cache)
    {
        $this->remoteCache = $cache;
    }

    /**
     * Set the cache driver to use for the image cache
     *
     * @param Cache $cache
     */
    public function setImageCache(Cache $cache)
    {
        $this->imageCache = $cache;
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
            'crop_type' => 'center',
            'default_width' => 100,
            'default_height' => 100,
            'debug' => false,
            'mime_types' => array(
                'image/jpeg',
                'image/jpg',
                'image/jpe',
                'image/png',
                'image/gif',
            ),
            'allowed_ips' => array(),
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
     * Get the remote file
     *
     * @return Remote
     */
    protected function getRemote()
    {
        $src = $this->request->get('src');
        $remote = $this->remoteCache->get($src);
        if ($remote === null) {
            $remote = new Remote($src);
            $this->remoteCache->set($src, $remote, (int) $this->getConfig('cache_time', 0));
        }

        return $remote;
    }

    /**
     * Process this request
     *
     * @return null
     */
    public function process()
    {
        if ($this->request->get('src') === null) {
            return '';
        }

        $remote     = $this->getRemote();
        $tmpFile    = $this->getTemporaryImageFile($remote);
        $dst_width  = $this->getCanvasWidth($tmpFile);
        $dst_height = $this->getCanvasHeight($tmpFile);
        $src_width  = $this->getSourceWidth($tmpFile);
        $src_height = $this->getSourceHeight($tmpFile);

//        var_dump("Canvas width: $dst_width, height: $dst_height");
//        var_dump("Source width: $src_width, height: $src_height");
//        var_dump($tmpFile);


        $out = imagecreatetruecolor($dst_width, $dst_height);
        imagecopyresampled($out, $tmpFile['resource'], 0, 0, 0, 0, $dst_width, $dst_height, $tmpFile['info'][0], $tmpFile['info'][1]);


        header('Content-Type: image/jpeg');
        imagejpeg($out);
    }

    public function getSourceWidth(array $file)
    {
        if ($this->request->get('c', '0') === '0') {
            return intval($file['info'][0]);
        }

        $dst_width  = $this->getCanvasWidth($file);
        $dst_height = $this->getCanvasHeight($file);

        $cmp_x = $file[0] / $dst_width;
        $cmp_y = $file[1] / $dst_height;


    }

    public function getSourceHeight(array $file)
    {
        if ($this->request->get('c', '0') === '0') {
            return intval($file['info'][1]);
        }

        $dst_width  = $this->getCanvasWidth($file);
        $dst_height = $this->getCanvasHeight($file);

        if ($file['orientation'] === 'landscape') {
            return intval($file['info'][0]);
        }
    }

    /**
     * Get the canvas width
     *
     * @param array $file
     * @return int|mixed
     */
    protected function getCanvasWidth(array $file)
    {
        $width = $this->request->get('w', false);
        $height = $this->request->get('h', false);

        if ($width === false && $height === false) {
            return intval($this->getConfig('default_width', 100));
        }

        $width = intval($width);
        $height = intval($height);

        if ($height > 0 && $width == 0) {
            return intval(($file['info'][0] / $file['info'][1]) * $height);
        }

        return $width;
    }

    /**
     * Get the canvas height
     *
     * @param array $file
     * @return int|mixed
     */
    protected function getCanvasHeight(array $file)
    {
        $width = $this->request->get('w', false);
        $height = $this->request->get('h', false);

        if ($width === false && $height === false) {
            return intval($this->getConfig('default_height', 100));
        }

        $width = intval($width);
        $height = intval($height);

        if ($width > 0 && $height == 0) {
            return intval(($file['info'][1] / $file['info'][0]) * $width);
        }

        return $height;
    }

    /**
     * This method will create the temporary image file made out of the original and return an array with information
     * about it including, dimensions, ratio, orientation and file object
     *
     * @param Remote $remote
     * @return array
     */
    protected function getTemporaryImageFile(Remote $remote)
    {
        $tmpFile = sprintf('%s/%s_tmp', $this->getConfig('tmp_dir'), $this->getCacheKey());
        if (file_exists($tmpFile) === false) {
            touch($tmpFile);
        }

        $tmp = new \SplFileObject($tmpFile, 'w');
        $tmp->fwrite($remote->getContent());

        $file = $tmp->getPath() . '/' . $tmp->getFilename();
        $info = getimagesize($file);

        switch ($info['mime']) {
            case 'image/jpeg':
            case 'image/jpg':
            case 'image/jpe':
                $img = imagecreatefromjpeg($file);
                break;

            case 'image/png':
                $img = imagecreatefrompng($file);
                break;

            case 'image/gif':
                $img = imagecreatefromgif($file);
                break;

            default:
                throw new \RuntimeException('Unknown file type. Are you sure this is an image?');
        }

        if ($info[0] === $info[1]) {
            $orientation = 'square';
        } else if ($info[0] > $info[1]) {
            $orientation = 'landscape';
        } else {
            $orientation = 'portrait';
        }

        return array(
            'file' => $tmp,
            'info' => $info,
            'filepath' => $file,
            'resource' => $img,
            'orientation' => $orientation,
            'ratio' => ($info[0] / $info[1])
        );
    }
}