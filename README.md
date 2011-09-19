Introduction
------------
Oembed is a lightweight PHP 5.3 library for fetching OEmbed data. 
Read more about oembed here: http://oembed.com

    $oembed = new OEmbed\OEmbedService(array(), true);
    $response = $browser->get('http://www.youtube.com/watch?v=REy3wCFjqZo');

    echo $response->html; //stdClass

If you want to use it with Symfony, I created a [bundle](https://github.com/saadtazi/SaadTaziOEmbedBundle).

Constructor parameters
----------------------

The constructor method has 3 optional parameters:

<dl>
<dt>$endPoints</dt>
<dd>
<ul><li>an array of endPoints (array of array)</li>
    <li>each endPoint can have 3 keys: pattern (required), url (required), 
params (optional)</li>
    <li>the service will try if the resource URL matches the pattern;
if true, then the url value will be used to get oembed data</li>
    <li>if provided, the params values are passed when querying the url everytime (a way to pass
a user_token for example - useful for embed.ly)</li>
<li>example:

<code>
    array(
            array(
                'pattern' => '/http:\/\/www\.youtube\.com/', 
                'url'     => 'http://www.youtube.com/oembed',
                'params'  => array('width' => 200)
            ),
            array(
                'pattern' => '/http:\/\/www\.flickr\.com/', 
                'url' => 'http://www.flickr.com/services/oembed'
            )
        )

</code>
</li>
</dd>

  
<dt>$discovery</dt>
<dd>
    <ul>
        <li>default: false</li>
        <li>if true, and if no endpoint is found for the resource URL, then the service 
will fetch the resource Url and try to extract the oembed URL, 
then call that URL.
Check the oembed specification for more information.</li>
</dd>
<dt>$allowedUrlPatterns</dt>
<dd>
if not provided (null or empty), the service will try 
to get all the urls. If provided, only urls that matchs
</dd>
</dl>

Constructor example with all the params
---------------------------

Here is an example that:

* sets 3 endpoints. the last one (because the pattern is /.*/) is the default 
one, and the param 'key=YOUR_KEY' will be added when requresting oembed data

* allows discovery (2nd param = true)

* allows only urls from http://www.youtube.com and http://www.flickr.com

``` php
$oembed = new OEmbed\OEmbedService(
                // endPoint
                array(
                    array(
                        'pattern' => '/http:\/\/www\.youtube\.com/', 
                        'url'     => 'http://www.youtube.com/oembed'
                    ),
                    array(
                        'pattern' => '/http:\/\/www\.flickr\.com/', 
                        'url'     => 'http://www.flickr.com/services/oembed'
                    ),
                    array(
                        'pattern' => '/.*/', 
                        'url'     => 'http://api.embed.ly/1/oembed',
                        'params'  => array('key' => 'YOUR_KEY')
                    ),
                ),
                // discovery
                true,
                // allowed URL
                array(
                    '/http:\/\/www\.youtube\.com/',
                    '/http:\/\/www\.flickr\.com/'
                )
);
```

Class Loader
------------
Before doing any of this you need to register the Buzz class loader 
(not needed if used with the SaadTaziOEmbedBundle).

``` php
    require_once 'OEmbed/ClassLoader.php';
    OEmbed\ClassLoader::register();
```

Unit Tests
OEmbed is tested using PHPUnit. The run the test suite, execute the following
command:

```
    $ phpunit --bootstrap test/bootstrap.php test/
```