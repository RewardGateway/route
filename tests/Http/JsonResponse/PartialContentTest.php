<?php

namespace League\Route\Test\Http\JsonResponse;

use League\Route\Http\JsonResponse\PartialContent;
use PHPUnit\Framework\TestCase;

class PartialContentTest extends TestCase
{
    /**
     * Asserts that new PartialContent responses have a 206 status code
     *
     * @return void
     */
    public function testConstructor()
    {
        $response = new PartialContent();
        $this->assertSame(206, $response->getStatusCode());
    }
}
