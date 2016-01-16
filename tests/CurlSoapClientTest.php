<?php
namespace Aaharu\Soap\CurlSoapClient\Tests;

use Aaharu\Soap\CurlSoapClient;

/**
 * @coversDefaultClass \Aaharu\Soap\CurlSoapClient
 */
class CurlSoapClientTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function soap1_1()
    {
        $obj = new CurlSoapClient(null, array(
            'location' => 'http://localhost:8000/tests/server.php?auth=basic',
            'uri' => 'http://test-uri/',
            'compression' => SOAP_COMPRESSION_ACCEPT,
            'connection_timeout' => 1,
            'login' => 'hoge',
            'password' => 'fuga'
        ));

        $response = $obj->test('abc');
        $this->assertEquals('abc', $response);
    }

    /**
     * @test
     */
    public function soap1_2()
    {
        $obj = new CurlSoapClient(null, array(
            'location' => 'http://localhost:8000/tests/server.php?redirect=1&auth=digest',
            'uri' => 'http://test-uri/',
            'user_agent' => 'curlsoapclient',
            'soap_version' => SOAP_1_2,
            'compression' => SOAP_COMPRESSION_GZIP,
            'keep_alive' => false,
            'trace' => true,
            'login' => 'hoge',
            'password' => 'fuga',
            'authentication' => SOAP_AUTHENTICATION_DIGEST
        ));

        $response = $obj->__soapCall('test', array(123));
        $this->assertEquals(123, $response);

        $last_request_headers = $obj->__getLastRequestHeaders();
        $this->assertTrue(stripos($last_request_headers, 'User-Agent: curlsoapclient') !== false);
        $this->assertTrue(stripos($last_request_headers, 'Connection: close') !== false);
    }

    /**
     * @test
     */
    public function overRedirectMax()
    {
        // no exception option
        $obj = new CurlSoapClient(null, array(
            'location' => 'http://localhost:8000/tests/server.php?redirect=2',
            'uri' => 'http://test-uri/',
            'redirect_max' => 1,
            'exceptions' => false
        ));

        $response = $obj->test(123);
        $this->assertInstanceOf('SoapFault', $response);
        $this->assertTrue(is_soap_fault($response));
    }

    /**
     * @test
     * @expectedException        \SoapFault
     * @expectedExceptionMessage Error Fetching http, 
     */
    public function curlSoapFault()
    {
        $obj = new CurlSoapClient(null, array(
            'location' => 'http://noexists',
            'uri' => 'http://test-uri/'
        ));
        $obj->test('hoge');
    }

    /**
     * @test
     * @expectedException        \SoapFault
     * @expectedExceptionMessage Service Temporarily Unavailable
     */
    public function server503()
    {
        $obj = new CurlSoapClient(null, array(
            'location' => 'http://localhost:8000/tests/server.php?503=1',
            'uri' => 'http://test-uri/'
        ));
        $obj->test('hoge');
    }

    /**
     * @test
     * @expectedException        \SoapFault
     * @expectedExceptionMessage message
     */
    public function testFault()
    {
        $obj = new CurlSoapClient(null, array(
            'location' => 'http://localhost:8000/tests/server.php',
            'uri' => 'http://test-uri/'
        ));
        $obj->testFault();
    }

    /**
     * @test
     */
    public function http1_0()
    {
        $obj = new CurlSoapClient(null, array(
            'location' => 'http://localhost:8000/tests/server.php',
            'uri' => 'http://test-uri/',
            'compression' => SOAP_COMPRESSION_DEFLATE,
            'trace' => true
        ));
        $obj->___curlSetOpt(CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
        $class = new \stdClass();
        $response = $obj->test($class);
        $this->assertEquals($class, $response);
        $this->assertTrue(stripos($obj->__getLastRequestHeaders(), 'POST /tests/server.php HTTP/1.0') === 0);
    }

    /**
     * @test
     * @medium
     * @expectedException        \SoapFault
     * @expectedExceptionMessage Error Fetching http, 
     */
    public function timeout()
    {
        $obj = new CurlSoapClient(null, array(
            'location' => 'http://localhost:8000/tests/server.php?usleep=1300000',
            'uri' => 'http://test-uri/',
            'curl_timeout' => 1
        ));
        $class = new \stdClass();
        $obj->test($class);
    }

    /**
     * @test
     * @expectedException        \SoapFault
     * @expectedExceptionMessage Error Redirecting, No Location
     */
    public function noLocation()
    {
        $obj = new CurlSoapClient(null, array(
            'location' => 'http://localhost:8000/tests/server.php?300=1',
            'uri' => 'http://test-uri/'
        ));
        $class = new \stdClass();
        $obj->test($class);
    }

    /**
     * @test
     * @expectedException        \SoapFault
     * @expectedExceptionMessage Error Redirecting, Invalid Location
     */
    public function invalidLocation()
    {
        $obj = new CurlSoapClient(null, array(
            'location' => 'http://localhost:8000/tests/server.php?location=/tmp',
            'uri' => 'http://test-uri/'
        ));
        $obj->test(true);
    }

    /**
     * @test
     */
    public function cookie()
    {
        $obj = new CurlSoapClient(null, array(
            'location' => 'http://localhost:8000/tests/server.php',
            'uri' => 'http://test-uri/',
            'trace' => true
        ));
        $original_obj = new \SoapClient(null, array(
            'location' => 'http://localhost:8000/tests/server.php',
            'uri' => 'http://test-uri/',
            'trace' => true
        ));
        $this->assertEquals(array(), $obj->__getCookies());
        $this->assertEquals(array(), $original_obj->__getCookies());
        $obj->__setCookie('CookieTest', 'HelloWorld');
        $obj->__setCookie('CookieTest2', 'HelloWorld2');
        $original_obj->__setCookie('CookieTest', 'HelloWorld');
        $original_obj->__setCookie('CookieTest2', 'HelloWorld2');
        $this->assertEquals(array('CookieTest' => array('HelloWorld'), 'CookieTest2' => array('HelloWorld2')), $obj->__getCookies());
        $this->assertEquals(array('CookieTest' => array('HelloWorld'), 'CookieTest2' => array('HelloWorld2')), $original_obj->__getCookies());
        $this->assertEquals(array(1, 'a', false), $obj->test(array(1, 'a', false)));
        $this->assertEquals(array(1, 'a', false), $original_obj->test(array(1, 'a', false)));
        // difference of CurlSoapClient from SoapClient [";" -> "; "]
        $this->assertTrue(stripos($obj->__getLastRequestHeaders(), 'Cookie: CookieTest=HelloWorld; CookieTest2=HelloWorld2') !== false);
        $this->assertTrue(stripos($original_obj->__getLastRequestHeaders(), 'Cookie: CookieTest=HelloWorld;CookieTest2=HelloWorld2') !== false);
    }

    /**
     * @test
     * @expectedException        \SoapFault
     * @expectedExceptionMessage Bad Request
     */
    public function server400()
    {
        $obj = new CurlSoapClient(null, array(
            'location' => 'http://localhost:8000/tests/server.php?400=1',
            'uri' => 'http://test-uri/'
        ));
        $obj->test(true);
    }
}
