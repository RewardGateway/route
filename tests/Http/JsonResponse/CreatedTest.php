<?php

namespace League\Route\Test\Http\JsonResponse;

use League\Route\Http\JsonResponse\Created;
use PHPUnit\Framework\TestCase;

class CreatedTest extends TestCase
{
    /**
     * Asserts that new Created responses have a 201 status code
     *
     * @return void
     */
    public function testConstructor()
    {
        $response = new Created();
        $this->assertSame(201, $response->getStatusCode());
    }
}
