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
     * @var Remote
     */
    protected $remoteFile;

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
     * Get the remote file. The remote file is represented as a Remote object that is cacheable and provides
     * introspection of the image file.
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
     * Only allow image file extensions here. Mime will get checked later
     *
     * @return bool
     */
    protected function validateRequest()
    {
        $pass = true;

        if ($this->request->get('src') === null) {
            $pass = false;
        }

        if (preg_match('/(jpg|jpeg|gif|png)$/', $this->request->get('src')) === 0) {
            $pass = false;
        }

        return $pass;
    }

    /**
     * Send a 404 header and exit
     *
     * @return null
     */
    protected function send404()
    {
        header('HTTP/1.0 404 Not Found');
        exit(1);
    }

    /**
     * Validate a mime type is an image
     *
     * @return null
     */
    protected function validateMimeTypeIsImage()
    {
        $mime = $this->remoteFile->getHeader('Content-Type');
        $mimes = $this->getConfig('mime_types', array());

        if (in_array($mime, $mimes) === false) {
            $this->send404();
        }
    }

    /**
     * Run validation.
     *
     * @return null
     */
    protected function runValidation()
    {
        $validators = array(
            'validateRequest',
            'validateMimeTypeIsImage',
        );

        foreach ($validators as $validator) {
            call_user_func(array($this, $validator));
        }
    }

    /**
     * Process this request
     *
     * @return null
     */
    public function process()
    {
        // get the remote file to be used in
        $this->remoteFile = $this->getRemote();
        $this->runValidation();

        header(sprintf('Content-Type: %s', $this->remoteFile->getHeader('Content-Type')));

        $cached = $this->getCachedOutputImage();
        if ($cached !== false) {
            echo $cached;
            exit(0);
        }

        $tmpImage  = $this->getOriginalImageGdResource($this->remoteFile);
        $dstWidth  = $this->getCanvasWidth();
        $dstHeight = $this->getCanvasHeight();
        $origInfo  = $this->getOriginalImageInfo();

        // aspect ratios
        $srcRatio = ($origInfo[0] / $origInfo[1]);
        $dstRatio = ($dstWidth / $dstHeight);

        if ( $srcRatio > $dstRatio ) {
            $tmpHeight = $dstHeight;
            $tmpWidth = intval($dstHeight * $srcRatio);
        } else {
            $tmpWidth = $dstWidth;
            $tmpHeight = intval($dstWidth / $srcRatio);
        }

        $tmpGd = imagecreatetruecolor($tmpWidth, $tmpHeight);
        imagecopyresampled($tmpGd, $tmpImage, 0, 0, 0, 0, $tmpWidth, $tmpHeight, $origInfo[0], $origInfo[1]);

        $x = ($tmpWidth - $dstWidth) / 2;
        $y = ($tmpHeight - $dstHeight) / 2;

        $gdim = imagecreatetruecolor($dstWidth, $dstHeight);
        imagecopy($gdim, $tmpGd, 0, 0, $x, $y, $dstWidth, $dstHeight);

        imagejpeg( $gdim );
    }

    /**
     * This will check if there already exists a cached image file and return it
     *
     * @return bool|string
     */
    protected function getCachedOutputImage()
    {
        $imageRootDir = sprintf('%s/%s', $this->imageCache->getPath(), md5($this->request->get('src')));
        $cachedImage = sprintf('%s/%s', $imageRootDir, $this->getCacheKey());
        if (file_exists($cachedImage) === true) {
            return file_get_contents($cachedImage);
        }

        return false;
    }

    /**
     * Get the canvas width
     *
     * @return int|mixed
     */
    protected function getCanvasWidth()
    {
        $info = $this->getOriginalImageInfo();
        $width = $this->request->get('w', false);
        $height = $this->request->get('h', false);

        if ($width === false && $height === false) {
            return intval($this->getConfig('default_width', 100));
        }

        $width = intval($width);
        $height = intval($height);

        if ($height > 0 && $width == 0) {
            return intval(($info[0] / $info[1]) * $height);
        }

        return $width;
    }

    /**
     * Get the canvas height
     *
     * @return int|mixed
     */
    protected function getCanvasHeight()
    {
        $info = $this->getOriginalImageInfo();
        $width = $this->request->get('w', false);
        $height = $this->request->get('h', false);

        if ($width === false && $height === false) {
            return intval($this->getConfig('default_height', 100));
        }

        $width = intval($width);
        $height = intval($height);

        if ($width > 0 && $height == 0) {
            return intval(($info[1] / $info[0]) * $width);
        }

        return $height;
    }

    /**
     * Get or create the GD image resource and return it
     *
     * @param Remote $remote
     * @return resource
     */
    protected function getOriginalImageGdResource(Remote $remote)
    {
        $tmpFile = $this->getOriginalImagePath();

        // if the temporary file does not exist, create it, otherwise return it
        if (file_exists($tmpFile) === false) {
            touch($tmpFile);
            $file = new \SplFileObject($tmpFile, 'w');
            $file->fwrite($remote->getContent());
        } else {
            $file = new \SplFileObject($tmpFile, 'r');
        }

        $info = getimagesize($tmpFile);

        switch ($info['mime']) {
            case 'image/png':
                $img = imagecreatefrompng($file);
                break;

            case 'image/gif':
                $img = imagecreatefromgif($file);
                break;

            default:
                $img = imagecreatefromjpeg($file);
                break;
        }

        return $img;
    }

    /**
     * Get the image info
     *
     * @return array
     */
    protected function getOriginalImageInfo()
    {
        $tmpFile = $this->getOriginalImagePath();
        return getimagesize($tmpFile);
    }

    /**
     * This will look for the original image in the cache and return it or create it if it doesn't exist
     *
     * @return string
     */
    protected function getOriginalImagePath()
    {
        $imageRootDir = sprintf('%s/%s', $this->imageCache->getPath(), md5($this->request->get('src')));
        if (is_dir($imageRootDir) === false) {
            mkdir($imageRootDir);
        }

        $imageFileName = md5($this->request->get('src')) . '_original' . $this->remoteFile->getExtension();
        $tmpFile = sprintf('%s/%s', $imageRootDir, $imageFileName);

        if (file_exists($tmpFile) === false) {
            touch($tmpFile);
            $file = new \SplFileObject($tmpFile, 'w');
            $file->fwrite($this->remoteFile->getContent());
        }

        return $tmpFile;
    }
}