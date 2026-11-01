<?php

declare(strict_types=1);

namespace Tests\Unit\Core;

use App\Core\Response;
use PHPUnit\Framework\TestCase;

final class ResponseTest extends TestCase
{
    public function testHtmlSetsStatusAndContentType(): void
    {
        $r = Response::html('<h1>Hi</h1>', 200);

        $this->assertSame(200, $r->status);
        $this->assertTrue($r->hasHeader('Content-Type'));
    }

    public function testHtmlDefaultStatus200(): void
    {
        $this->assertSame(200, Response::html('')->status);
    }

    public function testJsonSetsStatus(): void
    {
        $r = Response::json(['ok' => true], 201);

        $this->assertSame(201, $r->status);
    }

    public function testRedirectSetsLocationHeader(): void
    {
        $r = Response::redirect('/dashboard', 302);

        $this->assertSame(302, $r->status);
        $this->assertTrue($r->hasHeader('Location'));
    }

    public function testWithHeaderAddsHeader(): void
    {
        $r = Response::html('')->withHeader('X-Custom', 'value');

        $this->assertTrue($r->hasHeader('X-Custom'));
    }

    public function testWithHeaderDoesNotMutateOriginal(): void
    {
        $original = Response::html('');
        $modified = $original->withHeader('X-Custom', 'value');

        $this->assertFalse($original->hasHeader('X-Custom'));
        $this->assertTrue($modified->hasHeader('X-Custom'));
    }

    public function testHasHeaderReturnsFalseWhenAbsent(): void
    {
        $r = Response::html('');

        $this->assertFalse($r->hasHeader('X-Missing'));
    }
}
