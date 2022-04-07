<?php

namespace League\Route\Test\Http\JsonResponse;

use League\Route\Http\JsonResponse\ResetContent;
use PHPUnit\Framework\TestCase;

class ResetContentTest extends TestCase
{
    /**
     * Asserts that new ResetContent responses have a 205 status code
     *
     * @return void
     */
    public function testConstructor()
    {
        $response = new ResetContent();
        $this->assertSame(205, $response->getStatusCode());
    }
}
