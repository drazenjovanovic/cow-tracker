<?php

declare(strict_types=1);

namespace Tests\Unit\Core;

use App\Core\Csrf;
use PHPUnit\Framework\TestCase;
use Tests\Support\ArraySession;

final class CsrfTest extends TestCase
{
    private ArraySession $session;
    private Csrf $csrf;

    protected function setUp(): void
    {
        $this->session = new ArraySession();
        $this->csrf    = new Csrf($this->session);
    }

    public function testTokenIsGeneratedAndStoredInSession(): void
    {
        $token = $this->csrf->token();

        $this->assertNotEmpty($token);
        $this->assertSame($token, $this->session->get('_csrf'));
    }

    public function testTokenIsStableAcrossCalls(): void
    {
        $this->assertSame($this->csrf->token(), $this->csrf->token());
    }

    public function testTokenReusesExistingSessionValue(): void
    {
        $this->session->set('_csrf', 'existing-token');

        $this->assertSame('existing-token', $this->csrf->token());
    }

    public function testIsValidAcceptsCorrectToken(): void
    {
        $token = $this->csrf->token();

        $this->assertTrue($this->csrf->isValid($token));
    }

    public function testIsValidRejectsWrongToken(): void
    {
        $this->csrf->token();

        $this->assertFalse($this->csrf->isValid('wrong-token'));
    }

    public function testIsValidRejectsNull(): void
    {
        $this->csrf->token();

        $this->assertFalse($this->csrf->isValid(null));
    }

    public function testIsValidRejectsEmptyString(): void
    {
        $this->csrf->token();

        $this->assertFalse($this->csrf->isValid(''));
    }
}
