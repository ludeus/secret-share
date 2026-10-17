<?php

/**
 * Secret Share - partage de secrets éphémères et chiffrés.
 *
 * @license AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace SecretShare\Test\Unit\Secret;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SecretShare\Secret\Duration;
use SecretShare\Secret\ShareRequest;
use SecretShare\Secret\ValidationException;

final class ShareRequestTest extends TestCase
{
    private const array VALID = [
        'secret' => 'mon secret',
        'duration' => '1h',
        'single_use' => '1',
        'password' => '',
        'short_url' => '0',
    ];

    public function testValidInput(): void
    {
        $request = ShareRequest::fromInput(self::VALID, 100);

        $this->assertSame('mon secret', $request->secret);
        $this->assertSame(Duration::OneHour, $request->duration);
        $this->assertTrue($request->singleUse);
        $this->assertNull($request->password);
        $this->assertFalse($request->shortUrl);
    }

    public function testPasswordAndShortUrl(): void
    {
        $request = ShareRequest::fromInput(['password' => 'pass', 'short_url' => '1'] + self::VALID, 100);

        $this->assertSame('pass', $request->password);
        $this->assertTrue($request->shortUrl);
    }

    public function testUncheckedCheckboxesAreAbsent(): void
    {
        $input = self::VALID;
        unset($input['single_use'], $input['short_url']);

        $request = ShareRequest::fromInput($input, 100);

        $this->assertFalse($request->singleUse);
        $this->assertFalse($request->shortUrl);
    }

    public function testLineEndingsAreNormalized(): void
    {
        $request = ShareRequest::fromInput(['secret' => "a\r\nb"] + self::VALID, 100);

        $this->assertSame("a\nb", $request->secret);
    }

    public function testLengthIsCountedInCharacters(): void
    {
        $request = ShareRequest::fromInput(['secret' => str_repeat('é', 10)] + self::VALID, 10);

        $this->assertSame(str_repeat('é', 10), $request->secret);
    }

    /**
     * @return iterable<string, array{array<string, mixed>, string}>
     */
    public static function invalidInputs(): iterable
    {
        yield 'secret vide' => [['secret' => ''] + self::VALID, 'secret'];
        yield 'secret blanc' => [['secret' => "  \n "] + self::VALID, 'secret'];
        yield 'secret trop long' => [['secret' => str_repeat('a', 11)] + self::VALID, 'secret'];
        yield 'secret non utf-8' => [['secret' => "\xff\xfe"] + self::VALID, 'secret'];
        yield 'secret tableau' => [['secret' => ['a']] + self::VALID, 'secret'];
        yield 'durée inconnue' => [['duration' => '2d'] + self::VALID, 'duration'];
        yield 'durée absente' => [['duration' => null] + self::VALID, 'duration'];
        yield 'mot de passe trop long' => [['password' => str_repeat('a', 1025)] + self::VALID, 'password'];
        yield 'url courte sans limite ni usage unique' => [
            ['short_url' => '1', 'single_use' => '0', 'duration' => 'none'] + self::VALID,
            'short_url',
        ];
    }

    /**
     * @param array<string, mixed> $input
     */
    #[DataProvider('invalidInputs')]
    public function testInvalidInput(array $input, string $field): void
    {
        try {
            ShareRequest::fromInput($input, 10);
            $this->fail('ValidationException attendue');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey($field, $e->errors);
        }
    }

    public function testShortUrlAllowed(): void
    {
        $this->assertTrue(ShareRequest::shortUrlAllowed(true, Duration::Unlimited));
        $this->assertTrue(ShareRequest::shortUrlAllowed(false, Duration::OneDay));
        $this->assertFalse(ShareRequest::shortUrlAllowed(false, Duration::Unlimited));
        $this->assertFalse(ShareRequest::shortUrlAllowed(false, null));
    }
}
