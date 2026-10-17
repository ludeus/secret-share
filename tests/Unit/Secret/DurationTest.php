<?php

/**
 * Secret Share - partage de secrets éphémères et chiffrés.
 *
 * @license AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace SecretShare\Test\Unit\Secret;

use PHPUnit\Framework\TestCase;
use SecretShare\Secret\Duration;

final class DurationTest extends TestCase
{
    public function testSeconds(): void
    {
        $this->assertSame(600, Duration::TenMinutes->seconds());
        $this->assertSame(3600, Duration::OneHour->seconds());
        $this->assertSame(14400, Duration::FourHours->seconds());
        $this->assertSame(86400, Duration::OneDay->seconds());
        $this->assertSame(604800, Duration::OneWeek->seconds());
        $this->assertSame(2592000, Duration::OneMonth->seconds());
        $this->assertSame(31536000, Duration::OneYear->seconds());
        $this->assertNull(Duration::Unlimited->seconds());
    }

    public function testExpiresAt(): void
    {
        $this->assertSame(1600, Duration::TenMinutes->expiresAt(1000));
        $this->assertNull(Duration::Unlimited->expiresAt(1000));
    }

    public function testDefaultIsOneDay(): void
    {
        $this->assertSame(Duration::OneDay, Duration::DEFAULT);
    }

    public function testEveryDurationIsTranslated(): void
    {
        $messages = require dirname(__DIR__, 3) . '/lang/fr.php';
        $this->assertIsArray($messages);
        foreach (Duration::cases() as $duration) {
            $this->assertArrayHasKey($duration->labelKey(), $messages);
        }
    }
}
