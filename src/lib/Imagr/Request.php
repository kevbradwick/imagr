<?php

namespace Imagr;

/**
 * Request
 *
 * The request object provides a simple way of obtaining request parameters or sensible defaults
 *
 * @author  Kevin Bradwick <kbradwick@gmail.com>
 * @package Imagr
 * @license http://www.opensource.org/licenses/bsd-license.php
 */
class Request
{
    /**
     * @var array
     */
    protected $params = array();

    /**
     * Class construct
     *
     * @param array $params
     */
    public function __construct(array $params)
    {
        foreach ($params as $param) {
            $this->params = array_replace_recursive($this->params, $param);
        }
    }

    /**
     * Get a parameter
     *
     * @param string $name
     * @param null   $default
     * @return mixed
     */
    public function get($name, $default=null)
    {
        if (array_key_exists($name, $this->params) === true) {
            return $this->params[$name];
        }

        return $default;
    }
}
