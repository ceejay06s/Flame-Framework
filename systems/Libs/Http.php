<?php

namespace Flame;

use String;

class Http
{
    private $http;
    public  $response;

    public  $url;
    public  $method;
    public $content;
    public  $header = array('Content-type: application/x-www-form-urlencoded');
    public   $proxy;
    public  $requestFullUri = false;
    public  $followLocation = 1;
    public  $maxRedirect = 20;
    public  $protocolVersion = 1.0;
    public  $ignoreError = false;
    private $context;
    private  $data;
    public $timeout = 30;



    /**
     * @param String $url String of url to make POST | Get Request
     * @param String $method String of Method POST | Get , Default : GET
     * @param Array $args Array of arguments containing Method, Headers, Body Content etc.
     * @return Object returns $this object and Response
     ***/
    private function buildHttp($url, $method = 'GET', $args = array())
    {

        $this->url = $url;
        $this->method = !empty($args['Method']) ? $args['Method'] : $method;
        $this->data = !empty($args['data']) ? $args['data'] : array();
        $this->header = !empty($args['header']) ? array_merge_recursive($args['header'], $this->header) : $this->header;
        $this->http['options']['http']['method'] = !empty($this->method) ? $this->method : 'GET';

        if (!empty($this->header)) {
            $this->http['options']['http']['header'] = $this->header;
        }
        if (!empty($this->data)) {
            $this->http['options']['http']['content'] = http_build_query($this->data);
        }
        if (!empty($this->proxy)) {
            $this->http['options']['http']['proxy'] = ($this->proxy);
        }
        if (!empty($this->requestFullUri)) {
            $this->http['options']['http']['request_fulluri'] = ($this->requestFullUri);
        }
        if (!empty($this->followLocation)) {
            $this->http['options']['http']['follow_location'] = ($this->followLocation);
        }
        if (!empty($this->maxRedirect)) {
            $this->http['options']['http']['max_redirects '] = ($this->maxRedirect);
        }
        if (!empty($this->protocolVersion)) {
            $this->http['options']['http']['protocol_version'] = ($this->protocolVersion);
        }
        if (!empty($this->timeout)) {
            $this->http['options']['http']['timeout'] = ($this->timeout);
        }
        if (!empty($this->ignoreError)) {
            $this->http['options']['http']['ignore_errors'] = ($this->ignoreError);
        }

        $this->context = stream_context_create($this->http['options']);
        $this->http['handler'] = fopen($this->url, 'r', false, $this->context);

        // get ta
        $this->response['meta'] = (stream_get_meta_data($this->http['handler']));
        $this->response['code'] = substr(trim($this->response['meta']['wrapper_data'][0]), -3);
        $this->response['contents'] = json_decode(stream_get_contents($this->http['handler']), true);

        fclose($this->http['handler']);

        return $this;
    }


    /**
     * @param String $url String of url to make POST Request
     * @param Array $args Array of arguments containing Method, Headers, Body Content etc.
     * @return Array return Metadata and Response of the executed request
     ***/
    function post($url, $args = array())
    {
        $this->buildHttp($url, 'POST', $args);
        return $this->response;
    }

    /**
     * @param String $url String of url to make GET Request
     * @param Array $args Array of arguments containing Method, Headers, Body Content etc.
     * @return Array return Metadata and Response of the executed request
     ***/
    function get($url, $args = array())
    {
        $this->buildHttp($url, 'GET', $args);
        return $this->response;
    }
}
