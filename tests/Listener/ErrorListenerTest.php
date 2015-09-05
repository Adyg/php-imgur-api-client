<?php

namespace Imgur\Tests\HttpClient;

use Guzzle\Http\Message\Request;
use Imgur\Listener\ErrorListener;

class ErrorListenerTest extends \PHPUnit_Framework_TestCase
{
    public function testNothinHappenOnOKResponse()
    {
        $response = $this->getMockBuilder('Guzzle\Http\Message\Response')
            ->disableOriginalConstructor()
            ->getMock();
        $response->expects($this->once())
            ->method('isClientError')
            ->will($this->returnValue(false));

        $listener = new ErrorListener();
        $listener->onRequestError($this->getEventMock($response));
    }

    /**
     * @expectedException \Imgur\Exception\RateLimitException
     * @expectedExceptionMessage No user credits available. The limit is 10
     */
    public function testRateLimitUser()
    {
        $response = $this->getMockBuilder('Guzzle\Http\Message\Response')
            ->disableOriginalConstructor()
            ->getMock();
        $response->expects($this->once())
            ->method('isClientError')
            ->will($this->returnValue(true));
        $response->expects($this->at(1))
            ->method('getHeader')
            ->with('X-RateLimit-UserRemaining')
            ->will($this->returnValue(0));
        $response->expects($this->at(2))
            ->method('getHeader')
            ->with('X-RateLimit-UserLimit')
            ->will($this->returnValue(10));

        $listener = new ErrorListener();
        $listener->onRequestError($this->getEventMock($response));
    }

    /**
     * @expectedException \Imgur\Exception\RateLimitException
     * @expectedExceptionMessage No application credits available. The limit is 10 and will be reset at 2015-09-04
     */
    public function testRateLimitClient()
    {
        $response = $this->getMockBuilder('Guzzle\Http\Message\Response')
            ->disableOriginalConstructor()
            ->getMock();
        $response->expects($this->once())
            ->method('isClientError')
            ->will($this->returnValue(true));
        $response->expects($this->at(1))
            ->method('getHeader')
            ->with('X-RateLimit-UserRemaining')
            ->will($this->returnValue(9));
        $response->expects($this->at(2))
            ->method('getHeader')
            ->with('X-RateLimit-UserLimit')
            ->will($this->returnValue(10));
        $response->expects($this->at(3))
            ->method('getHeader')
            ->with('X-RateLimit-ClientRemaining')
            ->will($this->returnValue(0));
        $response->expects($this->at(4))
            ->method('getHeader')
            ->with('X-RateLimit-ClientLimit')
            ->will($this->returnValue(10));
        $response->expects($this->at(5))
            ->method('getHeader')
            ->with('X-RateLimit-UserReset')
            ->will($this->returnValue('1441401387')); // 4/9/2015  23:16:27

        $listener = new ErrorListener();
        $listener->onRequestError($this->getEventMock($response));
    }

    /**
     * @expectedException \Imgur\Exception\ErrorException
     * @expectedExceptionMessage Request to: /here failed with: "oops"
     */
    public function testErrorWithJson()
    {
        $response = $this->getMockBuilder('Guzzle\Http\Message\Response')
            ->disableOriginalConstructor()
            ->getMock();
        $response->expects($this->once())
            ->method('isClientError')
            ->will($this->returnValue(true));
        $response->expects($this->once())
            ->method('getBody')
            ->will($this->returnValue(json_encode(array('data' => array('request' => '/here', 'error' => 'oops')))));
        $response->expects($this->at(1))
            ->method('getHeader')
            ->with('X-RateLimit-UserRemaining')
            ->will($this->returnValue(9));
        $response->expects($this->at(2))
            ->method('getHeader')
            ->with('X-RateLimit-UserLimit')
            ->will($this->returnValue(10));
        $response->expects($this->at(3))
            ->method('getHeader')
            ->with('X-RateLimit-ClientRemaining')
            ->will($this->returnValue(9));
        $response->expects($this->at(4))
            ->method('getHeader')
            ->with('X-RateLimit-ClientLimit')
            ->will($this->returnValue(10));

        $listener = new ErrorListener();
        $listener->onRequestError($this->getEventMock($response));
    }

    /**
     * @expectedException \Imgur\Exception\RuntimeException
     * @expectedExceptionMessage hihi
     */
    public function testErrorWithoutJson()
    {
        $response = $this->getMockBuilder('Guzzle\Http\Message\Response')
            ->disableOriginalConstructor()
            ->getMock();
        $response->expects($this->once())
            ->method('isClientError')
            ->will($this->returnValue(true));
        $response->expects($this->once())
            ->method('getBody')
            ->will($this->returnValue('hihi'));
        $response->expects($this->at(1))
            ->method('getHeader')
            ->with('X-RateLimit-UserRemaining')
            ->will($this->returnValue(9));
        $response->expects($this->at(2))
            ->method('getHeader')
            ->with('X-RateLimit-UserLimit')
            ->will($this->returnValue(10));
        $response->expects($this->at(3))
            ->method('getHeader')
            ->with('X-RateLimit-ClientRemaining')
            ->will($this->returnValue(9));
        $response->expects($this->at(4))
            ->method('getHeader')
            ->with('X-RateLimit-ClientLimit')
            ->will($this->returnValue(10));

        $listener = new ErrorListener();
        $listener->onRequestError($this->getEventMock($response));
    }

    private function getEventMock($response)
    {
        $mock = $this->getMockBuilder('Guzzle\Common\Event')->getMock();
        $request = $this->getMockBuilder('Guzzle\Http\Message\Request')->disableOriginalConstructor()->getMock();

        $request->expects($this->any())
            ->method('getResponse')
            ->will($this->returnValue($response));

        $mock->expects($this->any())
            ->method('offsetGet')
            ->will($this->returnValue($request));

        return $mock;
    }
}
