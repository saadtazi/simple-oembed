<?php
namespace OEmbed;

use OEmbed\BaseClient;
use OEmbed\Response;
use OEmbed\Exceptions;

/**
 * Allows to get resource information form one oembed endpoint.
 * Check http://http://oembed.com/ for more information about oembed format specification.
 * It allows discovery: if no endpoints match (or no enpoints provided),
 * then the service will get the resource url and try to find the oembed <link> tag
 * 
 * It only supports oembed services that accept json format
 * 
 * @link http://oembed.com/
 * 
 * Example:
 * <code>
 * $endpoint = new OEmbedEndpoint(
 *          // oembed andpoints that will be used by the service
 *                http://www.flickr.com/oembed', // url
 *                array('width' => 200, 'token'=> 'MY_TOKEN', 'anyotherparam' =>'yes') // default params
 *              )
 *           );
 *       $response = $enpoint->get($this->getTestUrl('discovery'));
 * </code>
 */
class OEmbedEndpoint {
    
    /**
     * @var string
     */
    var $oembedUrl;
    
    /**
     * @var array
     */
    var $params;
    
    /**
     *
     * @param string $oembedUrl
     * @param array $params
     */
    public function __construct($oembedUrl = '', $params = array()) {
        $this->oembedUrl = $oembedUrl;
        $this->params = $params;
    }
    
    /**
     * performs the http call(s) to retrieve oembed data.
     * Can throw a bunch of different exceptions (inherits from OEmbed\Exception\OEmebedException
     * @throws Exception\OEmbedUnauthorizedException
     * @throws Exception\OEmbedNotFoundException
     * @throws Exception\OEmbedNotImplementedException
     * @throws Exception\InvalidResponseException
     * 
     * @param string $resourceUrl
     * @param array $params params
     * 
     * @return \stdClass 
     */
    public function get($resourceUrl, $params = array()) {
        
        $params = $this->mergeParams($resourceUrl, $params);
        // ok, we have a url now...
        return self::getUrl($this->oembedUrl, $params);
    }
    
    public static function getUrl($url, $params) {
        $client = new Client();
        $resp = $client->get($url, $params);
        
        return self::analyseResponse($resp);
    }
    
    public static function analyseResponse($resp) {
        self::checkHttpStatus($resp->getHttpCode());
        $respContent = json_decode($resp->content);
        
        //if invalid json, there is nothing we can do with the data...
        if (!$respContent) {
            //var_dump($resp->getResponseInfo());
            //var_dump($resp->content);
            throw new Exception\InvalidResponseException();
        }
        return $respContent;
        
    }
    
    /**
     * merge and 
     * @param string $url
     * @return array $params
     */
    public function mergeParams($url, $params = array()) {
        return array_merge($this->params, $params, array('url' => $url));
    }
    
    
    
    protected static function checkHttpStatus($httpCode) {
        switch ($httpCode) {
            case 401:
                throw new Exception\OEmbedUnauthorizedException();
                break;
            case 404:
                throw new Exception\OEmbedNotFoundException();
                break;
            case 501:
                throw new Exception\OEmbedNotImplementedException();
                break;
        }
        //still alive?
        return true;
    }
    
}
