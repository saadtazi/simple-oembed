<?php
namespace OEmbed;

/**
 * A response returned by the Client
 * Super simple response, contains the http header and content
 * http header can be fetch using get('NAME') (ie. get('http_code') or get('url')...)
 */
class Response {
    
    var $curl = null;
    var $content = null;
    
    /**
     *
     * @param text $content - the body of the response
     * @param array $curl  curl info result
     */
    public function __construct($content, $curl) {
        //print($content);
        $this->curl = $curl;
        $this->content = $content;
    }
    
    public function __get($name) {
        $res = null;
        if (isset($this->curl[$name])) {
            $res = $this->curl[$name];
            
        }
        return $res;
    }
    
    public function getContent() {
        return $this->content;
    }
    
    public function getHttpCode() {
        return $this->__get('http_code');
    }
    
    public function getResponseInfo() {
        return $this->curl;
    }
    
}