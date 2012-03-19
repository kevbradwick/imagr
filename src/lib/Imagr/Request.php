<?php

namespace Imagr;

/**
 * Created by JetBrains PhpStorm.
 * User: kevin
 * Date: 19/03/12
 * Time: 09:48
 * To change this template use File | Settings | File Templates.
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
