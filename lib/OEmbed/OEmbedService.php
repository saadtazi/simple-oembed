<?php
namespace OEmbed;

use OEmbed\BaseClient;
use OEmbed\Response;
use OEmbed\Exceptions;

/**
 * Allows to get resource information form oembed services.
 * Check http://http://oembed.com/ for more information about oembed format specification.
 * It allows discovery: if no endpoints match (or no enpoints provided),
 * then the service will get the resource url and try to find the oembed <link> tag
 * 
 * It only supports oembed services that accept json format (I didn't find
 * 
 * @link http://oembed.com/
 * 
 * Example:
 * <code>
 * $service = new OEmbedService(
 *          // oembed andpoints that will be used by the service
 *          array(
 *              array(
 *                'pattern' => '/http:\/\/www\.flickr\.com/', 
 *                'url'     => 'http://www.flickr.com/oembed',
 *                'params'  => array('width' => 200, 'token'=> 'MY_TOKEN', 'anyotherparam' =>'yes')
 *              )
 *           ),
 *           // discovery option: true is not recommended (because it will do 2 requests per call)
 *           false,
 *           // allowed resource url pattern
 *           array(
 *              '/http:\/\/www\.flickr\.com/'
 *           )  
 * 
 *       ));
 *       $response = $service->get($this->getTestUrl('discovery'));
 * </code>
 */
class OEmbedService {
    
    /**
     * 
     * endPoints: 
     * array of array:
     * - key: int (non-associative)
     * - value: array of
        * pattern
        * url: embed url (mandatory)
        * params (optional): additional params passed to embed url to fetch the oEmbed
     * The order is important: the first matching endPoint found is used
     * 
     * @var array
     */
    protected $endPoints;
    
    /**
     * an array of regexp patterns of allowed resource urls
     * if empty, then all urls are allowed
     * 
     * @var array
     */
    protected $allowedUrlPatterns;
    
    /**
     * if true, and if no endPoint is found, then the resource URL will be fetched
     * and the oembed url will be fetched (link tag)
     * @link http://oembed.com/#section4
     * @var boolean
     */
    protected $discovery;
    
    /**
     *
     * @param array $endPoints
     * @param boolean $discovery
     * @paramO array $allowedUrlPatterns 
     */
    public function __construct($endPoints = array(), $discovery = false, $allowedUrlPatterns = array()) {
        //var_dump($endPoints);//die();
        $this->endPoints = $endPoints;
        foreach ($endPoints as $key => $params) {
            $this->endPoints[$key] = array(
                                        'pattern' => $params['pattern'],
                                        'endPoint' => new OEmbedEndpoint($params['url'], isset($params['params'])? $params['params']: array())
                                     );
        }
        $this->allowedUrlPatterns = $allowedUrlPatterns;
        $this->discovery = $discovery;
    }
    
    /**
     * performs the http call(s) to retrieve oembed data.
     * Can throw a bunch of different exceptions (inherits from OEmbed\Exception\OEmebedException
     * @throws Exception\NotAllowedUrlException
     * @throws Exception\NoEndPointFoundException
     * @throws Exception\NoOEmbedLinkFoundException
     * @throws Exception\OEmbedUnauthorizedException
     * @throws Exception\OEmbedNotFoundException
     * @throws Exception\OEmbedNotImplementedException
     * @throws Exception\InvalidResponseException
     * @throws Exception\OEmbedResourceNotFoundException
     * 
     * @param string $resourceUrl
     * @param array $params params 
     * @return stdClass 
     */
    public function get($resourceUrl, $params = array()) {
        // resourceUrl allowed or not?
        if (!$this->isAllowed($resourceUrl)) {
            throw new Exception\NotAllowedUrlException();
        }
        
        // try to find an endPoint
        $endPoint = $this->findEndPoint($resourceUrl);
        if ($endPoint) {
            // got one... cool
            $resp = $endPoint->get($resourceUrl, $params);
        } else {
            // are we allowed to get the oembedurl from the resourceurl (meta tag)?
            if (!$this->discovery) {
                throw new Exception\NoEndPointFoundException();
            } else {
                $url = $this->fetchOEmbedUrl($resourceUrl);
                if (!$url) {
                    throw new Exception\NoOEmbedLinkFoundException();
                } else {
                    // ok, we have a url now... so call it directly
                    $resp = OEmbedEndpoint::getUrl($url, $params);
                }
            }
        }
        
        return $resp;
        
    }
    
    
    /**
     * find a valid endpoint form the array of endpoints (from constructor)
     * stops when one endpoint is found
     * @see OEmbedService::__construct()
     * 
     * @param string $resourceUrl
     * @return OEmbedEndpoint one endpoint object
     */
    public function findEndPoint($resourceUrl) {
        $matchingEndPoint = false;
        foreach ($this->endPoints as $endPoint) {
            if (self::match($endPoint['pattern'], $resourceUrl)) {
                $matchingEndPoint = $endPoint['endPoint'];
                break;
            }
        }
        return $matchingEndPoint;
    }
    
    /**
     * Checks if the resource URL is allowed
     * returns true is no $allowedUrlPatterns is 
     * @param string $resourceUrl
     * @return boolean 
     */
    public function isAllowed($resourceUrl) {
        // if empty, then all urls are allowed
        if (count($this->allowedUrlPatterns) < 1) {
            return true;
        }
        // is it part of the allowed patterns
        foreach ($this->allowedUrlPatterns as $pattern) {
            if (self::match($pattern, $resourceUrl)) {
                return true;
            }
        }
        // if we're here, then not allowed
        return false;
    }
    
    /**
     * Just a preg_match wrapper...
     * @param string $pattern regexp pattern
     * @param string $url
     * @return boolean
     */
    public static function match($pattern, $url) {
        return (preg_match($pattern, $url) > 0);
    }
    
    /**
     * fetch and return the oembed url form the resource url itself (<link>)
     * @param string $resourceUrl
     * @return string|false the oembed url or false if not found
     */
    public function fetchOEmbedUrl($resourceUrl) {
        $oembedUrl = false;
        $client = new Client();
        $resp = $client->get($resourceUrl);
        
        if ($resp->getHttpCode() == 404) {
            throw new Exception\OEmbedResourceNotFoundException();
        }
        // fetch all the <link> tags using regular expression
        $nb = preg_match_all("/<link[^>]+>/i",
            $resp->content,
            $out);
        if ($out[0]) {
            foreach ($out[0] as $link) {
                if (strpos($link, 'application/json+oembed') !== false) {
                    $oembedLinkPos = strpos($link, 'href="') + 6;
                    $oembedEndLinkPos = strpos($link, '"', $oembedLinkPos + 7) - 1;
                    //echo $oembedLinkPos . ', ' . $oembedEndLinkPos . "\n";
                    $oembedUrl = substr($link, $oembedLinkPos, $oembedEndLinkPos - $oembedLinkPos + 1);
                    break;
                }
            }
        }
        
        return $oembedUrl;
    }
    
    /**
     * @param string $name
     * @return OEmbedEndpoint
     */
    public function getEndpoint($name) {
        if (!isset($this->endPoints[$name])) {
            if  (!isset($this->endPoints[$name]['endPoint'])) {
                throw new Exception\NoEndPointFoundException();
            }
        }
        
        return $this->endPoints[$name]['endPoint'];
    }
}
