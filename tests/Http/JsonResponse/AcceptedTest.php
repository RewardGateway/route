<?php

namespace League\Route\Test\Http\JsonResponse;

use League\Route\Http\JsonResponse\Accepted;
use PHPUnit\Framework\TestCase;

class AcceptedTest extends TestCase
{
    /**
     * Asserts that new Accepted responses have a 202 status code
     *
     * @return void
     */
    public function testConstructor()
    {
        $response = new Accepted();
        $this->assertSame(202, $response->getStatusCode());
    }
}
