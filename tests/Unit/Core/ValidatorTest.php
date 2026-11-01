<?php

declare(strict_types=1);

namespace Tests\Unit\Core;

use App\Core\Validator;
use PHPUnit\Framework\TestCase;

final class ValidatorTest extends TestCase
{
    public function testPassesWhenAllRulesAreMet(): void
    {
        $errors = Validator::make(
            ['name' => 'Alice', 'email' => 'alice@example.com'],
            ['name' => ['required', 'min:2', 'max:60'], 'email' => ['required', 'email']],
        );

        $this->assertSame([], $errors);
    }

    public function testRequiredFailsOnEmptyString(): void
    {
        $errors = Validator::make(['name' => ''], ['name' => ['required']]);

        $this->assertArrayHasKey('name', $errors);
    }

    public function testRequiredFailsOnWhitespaceOnly(): void
    {
        $errors = Validator::make(['name' => '   '], ['name' => ['required']]);

        $this->assertArrayHasKey('name', $errors);
    }

    public function testRequiredFailsWhenKeyMissing(): void
    {
        $errors = Validator::make([], ['name' => ['required']]);

        $this->assertArrayHasKey('name', $errors);
    }

    public function testEmailPassesValidAddress(): void
    {
        $errors = Validator::make(['email' => 'user@example.com'], ['email' => ['email']]);

        $this->assertSame([], $errors);
    }

    public function testEmailFailsInvalidAddress(): void
    {
        $errors = Validator::make(['email' => 'not-an-email'], ['email' => ['email']]);

        $this->assertArrayHasKey('email', $errors);
    }

    public function testMinPassesAtExactLength(): void
    {
        $errors = Validator::make(['name' => 'ab'], ['name' => ['min:2']]);

        $this->assertSame([], $errors);
    }

    public function testMinFailsBelowLength(): void
    {
        $errors = Validator::make(['name' => 'a'], ['name' => ['min:2']]);

        $this->assertArrayHasKey('name', $errors);
    }

    public function testMaxPassesAtExactLength(): void
    {
        $errors = Validator::make(['name' => 'ab'], ['name' => ['max:2']]);

        $this->assertSame([], $errors);
    }

    public function testMaxFailsAboveLength(): void
    {
        $errors = Validator::make(['name' => 'abc'], ['name' => ['max:2']]);

        $this->assertArrayHasKey('name', $errors);
    }

    public function testMinBytesFailsBelowByteCount(): void
    {
        $errors = Validator::make(['pass' => 'short'], ['pass' => ['min_bytes:8']]);

        $this->assertArrayHasKey('pass', $errors);
    }

    public function testMaxBytesFailsAboveByteCount(): void
    {
        $errors = Validator::make(['pass' => str_repeat('a', 73)], ['pass' => ['max_bytes:72']]);

        $this->assertArrayHasKey('pass', $errors);
    }

    public function testConfirmedPassesWhenMatching(): void
    {
        $errors = Validator::make(
            ['password' => 'secret123', 'password_confirmation' => 'secret123'],
            ['password' => ['confirmed']],
        );

        $this->assertSame([], $errors);
    }

    public function testConfirmedFailsWhenNotMatching(): void
    {
        $errors = Validator::make(
            ['password' => 'secret123', 'password_confirmation' => 'different'],
            ['password' => ['confirmed']],
        );

        $this->assertArrayHasKey('password', $errors);
    }

    public function testStopsAtFirstErrorPerField(): void
    {
        $errors = Validator::make(
            ['email' => ''],
            ['email' => ['required', 'email']],
        );

        $this->assertCount(1, $errors);
        $this->assertStringContainsString('required', strtolower($errors['email']));
    }

    public function testReturnsErrorsForAllFailingFields(): void
    {
        $errors = Validator::make(
            ['name' => '', 'email' => 'bad'],
            ['name' => ['required'], 'email' => ['email']],
        );

        $this->assertArrayHasKey('name', $errors);
        $this->assertArrayHasKey('email', $errors);
    }

    public function testMinUsesCharacterCountNotBytes(): void
    {
        // "été" is 3 chars but 5 bytes in UTF-8 — min:3 should pass
        $errors = Validator::make(['name' => 'été'], ['name' => ['min:3']]);

        $this->assertSame([], $errors);
    }
}
