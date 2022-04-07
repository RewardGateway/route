<?php

namespace League\Route\Test\Http\JsonResponse;

use League\Route\Http\JsonResponse\NoContent;
use PHPUnit\Framework\TestCase;

class NoContentTest extends TestCase
{
    /**
     * Asserts that new NoContent responses have a 204 status code
     *
     * @return void
     */
    public function testConstructor()
    {
        $response = new NoContent();
        $this->assertSame(204, $response->getStatusCode());
    }
}
