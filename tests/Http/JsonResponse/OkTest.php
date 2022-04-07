<?php

namespace League\Route\Test\Http\JsonResponse;

use League\Route\Http\JsonResponse\Ok;
use PHPUnit\Framework\TestCase;

class OkTest extends TestCase
{
    /**
     * Asserts that new Ok responses have a 200 status code
     *
     * @return void
     */
    public function testConstructor()
    {
        $response = new Ok();
        $this->assertSame(200, $response->getStatusCode());
    }
}
