<?php

namespace OEmbed;

use OEmbed\Response;
/**
 * really simple HttpClient
 * 
 * Uses cURL
 * 
 * Example:
 * <code>
 * $client = new Client();
 * $response = $client->get('http://www.urltofetch.com/test');
 * $response->getHttpCode(); // 404, 200, ...
 * $response->get('http_code'); // 404, 200, ...
 * echo $response->content; // the content of the response (String)
 * </code>
 */
class Client {

    /**
     * 
     * @var array - allows to pass curl options
     */
    var $options = array();

    /**
     *Constructor
     * @param array $options - curl options
     */
    public function __construct(array $options = null) {
        $this->options = $options;
    }

    /**
     * Convert an array into a query string
     * Just a http_build_query wrapper
     * @param array $arr
     * @return type 
     */
    public static function fromArrayToQueryString(array $arr) {
        return http_build_query($arr);
    }
    
    /**
     *
     * @param string $url
     * @param string $method - GET, POST, PUT, ...
     * @param string|array $params
     * @return OEmbed\Response 
     */
    function _call($url, $method = 'GET', $params = null) {
        return $this->_doSimpleCall($url, $method, $params);
    }
    /**
     * Does the cURL call
     * 
     * @param string $url
     * @param string $method
     * @param array|string $postfields - params are converted from array and appended to url if get, 
     *      or added to CURLOPT_POSTFIELDS instead...
     * @return Response
     */
    function _doSimpleCall($url, $method = 'GET', $params = null) {
        
        $paramStr = null;
        if (is_array($params))  {
            $paramStr = self::fromArrayToQueryString($params);
        } elseif (is_string($params)) {
            $paramStr = $params;
        }
        $this->http_info = array();
        $ci = curl_init();
        /* Curl settings */
        if (isset($this->options['useragent'])) {
            curl_setopt($ci, CURLOPT_USERAGENT, $this->options['useragent']);
        }
        if (isset($this->options['connecttimeout'])) {
            curl_setopt($ci, CURLOPT_CONNECTTIMEOUT, $this->options['connecttimeout']);
        }
        if (isset($this->options['timeout'])) {
            curl_setopt($ci, CURLOPT_CONNECTTIMEOUT, $this->options['timeout']);
        }
        if (isset($this->options['ssl_verifypeer'])) {
            curl_setopt($ci, CURLOPT_CONNECTTIMEOUT, $this->options['ssl_verifypeer']);
        }
        curl_setopt($ci, CURLOPT_RETURNTRANSFER, TRUE);

        curl_setopt($ci, CURLOPT_HEADER, FALSE);

        switch ($method) {
            case 'POST':
                curl_setopt($ci, CURLOPT_POST, TRUE);
                if (!empty($params)) {
                    curl_setopt($ci, CURLOPT_POSTFIELDS, $params);
                }
                break;
            case 'DELETE':
                curl_setopt($ci, CURLOPT_CUSTOMREQUEST, 'DELETE');
                if (!empty($params)) {
                    $url = "{$url}?{$params}";
                }
                break;
            case 'GET':
                if (!empty($paramStr)) {
                    $url = self::addParamsToUrl($url, $paramStr);
                }
        }
        
        curl_setopt($ci, CURLOPT_URL, $url);
        $content = curl_exec($ci);
        
        // the response contains curl info (response header) + the response content
        $this->response = new Response($content, curl_getinfo($ci)); 
        
        
        return $this->response;
    }
    
    /**
     * appends string params to url
     * Checks if it already has a '?' or not
     * @param string $url
     * @param string $params 
     */
    public static function addParamsToUrl($url, $params) {
        if (!empty($params)) {
            if (strpos($url, '?') !== false) {
                $url = $url . '&' . $params;
            } else {
                $url = $url . '?' . $params;
            }
        }
        return $url;
    }
    
    /**
     * Performs an http get call
     * @param string $url
     * @param array|string $params 
     * @return  Response
     */
    public function get($url, $params = null) {
        return $this->_call($url, 'GET', $params);
    }
    
    /**
     * Performs an http post call
     * @param string $url
     * @param array|string $params 
     * @return  Response
     */
    public function post($url, $params = null) {
        return $this->_call($url, 'POST', $params);
    }
    
    /**
     *
     * @param string|array $params
     * @param string|array $toAdd
     * @return array the merge params as array
     */
    public static function addParams($params, $toAdd) {
        $paramArr = self::convertParamsToArray($params);
        $toAddArr = self::convertParamsToArray($toAdd);
        
        return array_merge($paramArr, $toAddArr);
    }
    
    /**
     * converts a query string into an array of params
     * @param type $params
     * @return type 
     */
    public static function convertParamsToArray($params) {
        if (is_array($params))  {
            return $params;
        } 
        parse_str($params, $paramArr);
        return $paramArr;
    }
}