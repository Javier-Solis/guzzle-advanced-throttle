<?php

namespace hamburgscleanest\GuzzleAdvancedThrottle\Tests;

use GuzzleHttp\Psr7\Request;
use hamburgscleanest\GuzzleAdvancedThrottle\Cache\Adapters\ArrayAdapter;
use hamburgscleanest\GuzzleAdvancedThrottle\RequestLimiter;
use PHPUnit\Framework\TestCase;

/**
 * Class RequestLimiterTests
 * @package hamburgscleanest\GuzzleAdvancedThrottle\Tests
 */
class RequestLimiterTest extends TestCase
{

    /** @test
     * @throws \Exception
     */
    public function can_be_created_statically() : void
    {
        $requestLimiter = RequestLimiter::create('www.test.com');

        $this->assertInstanceOf(RequestLimiter::class, $requestLimiter);
    }

    /** @test
     * @throws \Exception
     */
    public function can_be_created_from_rule() : void
    {
        $requestLimiter = RequestLimiter::createFromRule('www.test.com', []);

        $this->assertInstanceOf(RequestLimiter::class, $requestLimiter);
    }

    /** @test
     * @throws \Exception
     */
    public function can_request_is_correct() : void
    {
        $host = 'http://www.test.com';
        $requestLimiter = RequestLimiter::create($host, 1);
        $request = new Request('GET', $host . '/check');

        $this->assertTrue($requestLimiter->canRequest($request));
        $this->assertFalse($requestLimiter->canRequest($request));

        $otherRequest = new Request('GET', 'http://www.check.com');
        $this->assertTrue($requestLimiter->canRequest($otherRequest));
    }

    /** @test
     * @throws \Exception
     */
    public function remaining_seconds_are_correct() : void
    {
        $host = 'http://www.test.com';
        $requestLimiter = RequestLimiter::create($host, 20, 30);

        $requestLimiter->canRequest(new Request('GET', $host . '/check'));
        $this->assertEquals(30, $requestLimiter->getRemainingSeconds());
    }

    /** @test
     * @throws \Exception
     */
    public function current_request_count_is_correct() : void
    {
        $host = 'http://www.test.com';
        $requestLimiter = RequestLimiter::create($host, 1);
        $request = new Request('GET', $host . '/check');

        $this->assertEquals(0, $requestLimiter->getCurrentRequestCount());
        $requestLimiter->canRequest($request);
        $this->assertEquals(1, $requestLimiter->getCurrentRequestCount());
        $requestLimiter->canRequest($request);
        $this->assertEquals(1, $requestLimiter->getCurrentRequestCount());
    }

    /** @test
     * @throws \Exception
     */
    public function current_request_count_is_correct_when_expired() : void
    {
        $host = 'http://www.test.com';
        $requestLimiter = RequestLimiter::create($host, 1, 0);
        $requestLimiter->canRequest(new Request('GET', $host . '/check'));

        $this->assertEquals(0, $requestLimiter->getCurrentRequestCount());
    }

    /** @test
     * @throws \Exception
     */
    public function restores_state() : void
    {
        $host = 'http://www.test.com';

        $storage = new ArrayAdapter();
        $maxRequests = 15;
        $requestIntervalSeconds = 120;

        $requestLimiterOne = RequestLimiter::create($host, $maxRequests, $requestIntervalSeconds, $storage);
        $requestLimiterOne->canRequest(new Request('GET', $host . '/check'));
        $requestLimiterTwo = RequestLimiter::create($host, $maxRequests, $requestIntervalSeconds, $storage);

        $this->assertEquals($requestLimiterOne->getRemainingSeconds(), $requestLimiterTwo->getRemainingSeconds());
        $this->assertEquals($requestLimiterOne->getCurrentRequestCount(), $requestLimiterTwo->getCurrentRequestCount());
    }

    /** @test
     * @throws \Exception
     */
    public function matches_host_correctly() : void
    {
        $host = 'http://www.test.com';

        $requestLimiter = RequestLimiter::create($host);

        $this->assertTrue($requestLimiter->matches($host));
        $this->assertFalse($requestLimiter->matches('http://www.check.com'));
    }
}