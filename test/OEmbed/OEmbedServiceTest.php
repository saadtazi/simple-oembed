<?php

namespace OEmbed;

class OEmbedServiceTest extends \PHPUnit_Framework_TestCase {
    
    /**
     * @expectedException OEmbed\Exception\NoEndPointFoundException
     */
    public function testContructorNoParams() {
        $service = new OEmbedService();
        $service->get($this->getTestUrl('discovery'));
        
    }
    
    public function testMatchFirstEndPoint() {
        $service = new OEmbedService(array(
            array(
                'pattern' => '/http:\/\/www\.youtube\.com/', 
                'url'     => 'http://www.youtube.com/oembed',
                'params'  => array('width' => 200)
            ),
            array('pattern' => '/http:\/\/www\.flickr\.com/', 'url' => 'http://www.flickr.com/services/oembed')
        ));
        $response = $service->get($this->getTestUrl('discovery'));
        //$this->assertInstanceOf('stdClass',$response);
        $this->assertObjectHasAttribute('version', $response);
        
    }
    
    public function testMatchNotFirstEndPoint() {
        $service = new OEmbedService(array(
            array(
                'pattern' => '/http:\/\/www\.youtube\.com/', 
                'url'     => 'http://www.youtube.com/oembed',
                'params'  => array('width' => 200)
            ),
            array(
                 'pattern' => '/http:\/\/www\.flickr\.com/', 
                'url' => 'http://www.flickr.com/services/oembed',
                'params' => array('format' => 'json')
            )
        ));
        $response = $service->get($this->getTestUrl('nodiscovery'));
        
        $this->assertObjectHasAttribute('version', $response);
        
    }
    
    /**
     * @expectedException Exception
     */
    public function testNotOEmbedResource() {
        $service = new OEmbedService(array(
            array(
                'pattern' => '/http:\/\/www\.youtube\.com/', 
                'url'     => 'http://www.youtube.com/oembed',
                'params'  => array('width' => 200)
            )
        ));
        $response = $service->get($this->getTestUrl('notoembed'));
    }

    
    public function testDefaultEndPoint() {
        $service = new OEmbedService(array(
            array('pattern' => '/.*/', 'url' => 'http://www.youtube.com/oembed')
        ));
        $response = $service->get($this->getTestUrl('discovery'));
        $this->assertObjectHasAttribute('version', $response);
    }
    
    public function testDiscovery() {
        $service = new OEmbedService(array(), true);
        $response = $service->get($this->getTestUrl('discovery'));
        $this->assertObjectHasAttribute('version', $response);
    }
    
    /**
     * @expectedException OEmbed\Exception\NoOEmbedLinkFoundException
     */
    public function testDiscoveryNotAvailableForUrl() {
        $service = new OEmbedService(array(), true);
        $service->get($this->getTestUrl('nodiscovery'));
        
    }
    
     /**
     * @expectedException OEmbed\Exception\OEmbedNotFoundException
     */
    public function testOembedUrl404() {
        $service = new OEmbedService(array(
            array(
                'pattern' => '/http:\/\/www\.youtube\.com/', 
                'url'     => 'http://www.youtube.com/oembed',
                'params'  => array('width' => 200)
            )), false);
        $service->get($this->getTestUrl('404'));
        
    }
    
    
    public function testAllowedUrlOk() {
        $service = new OEmbedService(array(), false, array('/http:\/\/www\.youtube\.com/'));
        $res = $service->isAllowed($this->getTestUrl('discovery'));
        $this->assertTrue($res);
        
    }
    
    
    public function testAllowedUrlNok() {
        $service = new OEmbedService(array(
            array(
                'pattern' => '/http:\/\/www\.youtube\.com/', 
                'url'     => 'http://www.youtube.com/oembed',
                'params'  => array('width' => 200)
            )), false, array('/http:\/\/www\.anydomain\.com/'));
        $res = $service->isAllowed($this->getTestUrl('discovery'));
        $this->assertFalse($res);
    }

    /**
     * @expectedException OEmbed\Exception\NotAllowedUrlException
     */
    public function testNotAllowedUrlThrowException() {
        $service = new OEmbedService(array(
            array(
                'pattern' => '/http:\/\/www\.youtube\.com/', 
                'url'     => 'http://www.youtube.com/oembed',
                'params'  => array('width' => 200)
            )), false, array('/http:\/\/www\.anydomain\.com/'));
        $service->get($this->getTestUrl('discovery'));
        
    }
    

// utils
    public function getTestUrl($type) {
        $res = '';
        switch ($type) {
            case 'discovery':
                $res = 'http://www.youtube.com/watch?v=REy3wCFjqZo';
                break;
            case 'nodiscovery':
                $res = 'http://www.flickr.com/photos/saadou/5232740420/';
                break;
            case 'notoembed':
                $res = 'http://www.youtube.com/t/about_youtube';
                break;
            case '404':
                $res = 'http://www.youtube.com/watch?v=SAAAAAAAAaaD';
                break;
        }
        return $res;
    }
}