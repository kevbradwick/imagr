<?php

namespace Imagr;

/**
 * Cache
 *
 * This class will create and manage the file caches for each image
 *
 * @author  Kevin Bradwick <kbradwick@gmail.com>
 * @package Imagr
 * @license http://www.opensource.org/licenses/bsd-license.php
 */
class Cache
{
    /**
     * @var string
     */
    protected $extension = '.cache';

    /**
     * @var string
     */
    protected $path = '';

    /**
     * Class construct
     *
     * @param string $path      an absolute path to the directory that will contain the cache
     * @param string $extension the file extension for the cache files
     */
    public function __construct($path, $extension='.cache')
    {
        // check for relative path
        if (substr($path, 0, 2) === './') {
            $path = realpath(__DIR__ . substr($path, 1));
        }

        if (is_dir($path) === false) {
            if (@mkdir($path) === false) {
                throw new \InvalidArgumentException(
                    sprintf('Unable to create the directory "%s". Please check permissions.', $path));
            }
        }

        $this->path = $path;
        $this->extension = $extension;
    }

    /**
     * Get an item from the cache store
     *
     * @param string $id
     * @return bool
     */
    public function get($id)
    {
        $id = $this->createUniqueId($id);
        $cacheFile = sprintf('%s/%s%s', $this->path, $id, $this->extension);

        if (file_exists($cacheFile) === false) {
            return null;
        }

        $file = new \SplFileObject($cacheFile, 'r');
        $contents = '';
        while ($file->eof() === false) {
            $contents .= $file->fgets();
        }

        if (strlen($contents) === 0) {
            return false;
        }

        $data = unserialize($contents);

        if ($data['expires'] === 0) {
            return $data['data'];
        }

        if (time() > $data['expires']) {
            unlink($cacheFile);
            return false;
        }

        return $data['data'];
    }

    /**
     * Remove an item from the cache store
     *
     * @param string $id
     */
    public function delete($id)
    {
        $id = $this->createUniqueId($id);
        $cacheFile = sprintf('%s/%s%s', $this->path, $id, $this->extension);

        if (file_exists($cacheFile) === true) {
            unlink($cacheFile);
        }
    }

    /**
     * Set a data value to cache
     *
     * @param string $id      the id to set against the cache
     * @param mixed  $value   the value to store
     * @param int    $expires the time the cache expires in seconds from now
     */
    public function set($id, $value, $expires=0)
    {
        $id = $this->createUniqueId($id);
        $cacheFile = sprintf('%s/%s%s', $this->path, $id, $this->extension);

        $data = array(
            'expires' => (time() + (int) $expires),
            'data' => $value,
        );

        if ($expires === 0) {
            $data['expires'] = 0;
        }

        $_data = serialize($data);

        $file = new \SplFileObject($cacheFile, 'w');
        $file->fwrite($_data);
    }

    /**
     * Flush the entire cache store
     * @return mixed
     */
    public function flush()
    {
        $dir = new \RecursiveDirectoryIterator($this->path);
        $iter = new \RecursiveIteratorIterator($dir);
        $pattern = preg_replace('/[\.]/i', '\\\$0', $this->extension);
        $pattern = sprintf('/^.*%s$/i', $pattern);
        $files = new \RegexIterator($iter, $pattern, \RecursiveRegexIterator::GET_MATCH);

        foreach ($files as $file) {
            unlink($file[0]);
        }
    }


    /**
     * Check the data store has a variable
     *
     * @param string $id
     * @return boolean
     */
    public function has($id)
    {
        $id = $this->createUniqueId($id);
        $cacheFile = sprintf('%s/%s%s', $this->path, $id, $this->extension);
        return file_exists($cacheFile);
    }

    /**
     * Creates a unique id key. Anything can go in here and it will be reduced down to a lowercase string
     * with a md5 hash of the original id prepended to the final $id.
     *
     * @param string $id
     * @return string
     */
    protected function createUniqueId($id)
    {
        $hash = md5($id);
        $id = preg_replace('/[^a-zA-Z0-9]/', '', $id);
        $id = strtolower($id);
        return sprintf('%s-%s', $hash, $id);
    }

    /**
     * The path to this cache location
     *
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }
}
