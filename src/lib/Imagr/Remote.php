<?php

namespace Imagr;

/**
 * Remote
 *
 * A Remote object will represent the remote file and contain its headers and body.
 *
 * @author  Kevin Bradwick <kbradwick@gmail.com>
 * @package Imagr
 * @license http://www.opensource.org/licenses/bsd-license.php
 */
class Remote
{
    /**
     * @var string
     */
    protected $url;

    /**
     * @var string
     */
    protected $content;

    /**
     * @var array
     */
    protected $headers = array();

    /**
     * Class construct
     *
     * @param string $url
     */
    public function __construct($url='')
    {
        if (empty($url) === true) {
            throw new \InvalidArgumentException('Please specify a URL to pull from');
        }

        $this->url = $url;
        $this->process();
    }

    /**
     * Pull the remote content
     *
     * @return string
     */
    protected function process()
    {
        $ch = curl_init();

        curl_setopt($ch, \CURLOPT_URL, $this->url);
        curl_setopt($ch, \CURLOPT_REFERER, $this->getReferer());
        curl_setopt($ch, \CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, \CURLOPT_CONNECTTIMEOUT, 0);
        curl_setopt($ch, \CURLOPT_HEADER, 1);

        $content = curl_exec($ch);
        list($rawHeader, $body) = preg_split('/\r?\n\r?\n|\r\r/S', $content);
        $body = trim(str_replace($rawHeader, '', $content));
        $headers = array();

        preg_match('/Content\-Type:\s?(.*)/', $rawHeader, $matches);
        if (count($matches) === 2) {
            $headers['Content-Type'] = $matches[1];
        }

        preg_match('/Content\-Length:\s?(.*)/', $rawHeader, $matches);
        if (count($matches) === 2) {
            $headers['Content-Length'] = $matches[1];
        }

        preg_match('/Server:\s?(.*)/', $rawHeader, $matches);
        if (count($matches) === 2) {
            $headers['Server'] = $matches[1];
        }

        preg_match('/Date:\s?(.*)/', $rawHeader, $matches);
        if (count($matches) === 2) {
            $headers['Date'] = $matches[1];
        }

        curl_close($ch);

        $this->headers = $headers;
        $this->content = base64_encode($body);
    }

    /**
     * Get a header value
     *
     * @param string $name
     * @param string $default
     * @return string
     */
    public function getHeader($name, $default='')
    {
        if (isset($this->headers[$name]) === true) {
            return $this->headers[$name];
        }

        return $default;
    }

    /**
     * Get the referer string.
     *
     * @return mixed
     */
    protected function getReferer()
    {
        preg_match('/(^https?:\/\/[a-z0-9\-\.]*)/i', $this->url, $matches);

        if (isset($matches[1]) === true) {
            return $matches[1];
        }

        return '';
    }

    /**
     * Return a base64 decoded version of the image
     *
     * @return string
     */
    public function getContent()
    {
        return base64_decode($this->content);
    }

    /**
     * Get the implied file extension
     *
     * @return string
     */
    public function getExtension()
    {
        preg_match('/(\.[a-z]+)$/', $this->url, $matches);

        if (count($matches) > 0) {
            return $matches[1];
        }

        return '';
    }
}
