<?php

declare(strict_types=1);

namespace Tests\Unit\Core;

use App\Core\Request;
use PHPUnit\Framework\TestCase;

final class RequestTest extends TestCase
{
    public function testHeaderReadsServerArray(): void
    {
        $r = new Request('GET', '/', [], [], ['HTTP_ACCEPT' => 'text/html']);

        $this->assertSame('text/html', $r->header('Accept'));
    }

    public function testHeaderIsCaseInsensitive(): void
    {
        $r = new Request('GET', '/', [], [], ['HTTP_X_CSRF_TOKEN' => 'abc']);

        $this->assertSame('abc', $r->header('X-CSRF-Token'));
        $this->assertSame('abc', $r->header('x-csrf-token'));
    }

    public function testHeaderReturnsNullWhenAbsent(): void
    {
        $r = new Request('GET', '/');

        $this->assertNull($r->header('Accept'));
    }

    public function testIpReadsRemoteAddr(): void
    {
        $r = new Request('GET', '/', [], [], ['REMOTE_ADDR' => '1.2.3.4']);

        $this->assertSame('1.2.3.4', $r->ip());
    }

    public function testIsSecureViaHttpsOn(): void
    {
        $r = new Request('GET', '/', [], [], ['HTTPS' => 'on']);

        $this->assertTrue($r->isSecure());
    }

    public function testIsSecureViaPort443(): void
    {
        $r = new Request('GET', '/', [], [], ['SERVER_PORT' => '443']);

        $this->assertTrue($r->isSecure());
    }

    public function testIsNotSecureOnHttp(): void
    {
        $r = new Request('GET', '/', [], [], []);

        $this->assertFalse($r->isSecure());
    }

    public function testWantsJsonDetectsAcceptHeader(): void
    {
        $r = new Request('GET', '/', [], [], ['HTTP_ACCEPT' => 'application/json']);

        $this->assertTrue($r->wantsJson());
    }

    public function testWantsJsonFalseForHtml(): void
    {
        $r = new Request('GET', '/', [], [], ['HTTP_ACCEPT' => 'text/html']);

        $this->assertFalse($r->wantsJson());
    }

    public function testPostStringReturnsEmptyStringForMissingKey(): void
    {
        $r = new Request('POST', '/', [], []);

        $this->assertSame('', $r->postString('email'));
    }

    public function testPostStringReturnsValue(): void
    {
        $r = new Request('POST', '/', [], ['email' => 'a@b.com']);

        $this->assertSame('a@b.com', $r->postString('email'));
    }
}
