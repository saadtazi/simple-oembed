<?php

namespace OEmbed;

class OEmbedEndpointTest extends \PHPUnit_Framework_TestCase {
    
    /**
     * @expectedException Exception
     */
    public function testContructorNoParams() {
        $endpoint = new OEmbedEndPoint();
        $endpoint->get($this->getTestUrl('discovery'));
        
    }
    
    public function testRegularConstructor() {
        $endpoint = new OEmbedEndPoint(
            'http://www.youtube.com/oembed',
            array('width' => 200)            
        );
        $response = $endpoint->get($this->getTestUrl('discovery'));
        //$this->assertInstanceOf('stdClass',$response);
        $this->assertObjectHasAttribute('version', $response);
        
    }
    
    /**
     * @expectedException Exception
     */
    public function testNotOEmbedResource() {
       $endpoint = new OEmbedEndPoint(
            'http://www.youtube.com/oembed',
            array('width' => 200)            
        );
        $response = $endpoint->get($this->getTestUrl('notoembed'));
    }

    /**
     * @expectedException OEmbed\Exception\OEmbedNotFoundException
     */
    public function testWrongEndPoint() {
        $endpoint = new OEmbedEndPoint(
            'http://www.flickr.com/services/oembed',
            array('width' => 200)            
        );
        $response = $endpoint->get($this->getTestUrl('discovery'));
        
    }
    
    
     /**
     * @expectedException OEmbed\Exception\OEmbedNotFoundException
     */
    public function testOembedUrl404() {
        $endpoint = new OEmbedEndPoint(
            'http://www.youtube.com/oembed',
            array('width' => 200)            
        );
        $endpoint->get($this->getTestUrl('404'));
        
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