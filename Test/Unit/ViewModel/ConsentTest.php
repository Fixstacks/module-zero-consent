<?php
/**
 * Copyright © 2026 Roman Pasichnyk
 * SPDX-License-Identifier: OSL-3.0 OR AFL-3.0
 */

declare(strict_types=1);

namespace Zero\Consent\Test\Unit\ViewModel;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\UrlInterface;
use PHPUnit\Framework\TestCase;
use Zero\Consent\Api\TrackerInterface;
use Zero\Consent\Model\Config;
use Zero\Consent\Model\Trackers;
use Zero\Consent\ViewModel\Consent;

/**
 * What the page gets: nothing without a tracker on; the banner when there's
 * something to choose; each purpose with what the trackers use it for.
 */
class ConsentTest extends TestCase
{
    public function testNothingWithoutATrackerOn(): void
    {
        $consent = $this->consent(['ask' => '1', 'do_not_sell' => '1'], [$this->tracker(false, ['analytics' => 'GA'])]);

        $this->assertFalse($consent->isActive());
        $this->assertFalse($consent->hasChoices());
        $this->assertFalse($consent->showsDoNotSell());
    }

    public function testPurposesFromTheActiveTrackers(): void
    {
        $consent = $this->consent(['ask' => '1'], [
            $this->tracker(true, ['analytics' => 'pages (GA).', 'marketing' => 'ads (Google Ads).']),
            $this->tracker(false, ['marketing' => 'off (Pixel).']),
            $this->tracker(true, ['marketing' => 'ads (Meta).', 'other' => 'ignored']),
        ]);

        $this->assertSame(
            ['analytics' => ['pages (GA).'], 'marketing' => ['ads (Google Ads).', 'ads (Meta).']],
            $consent->purposes()
        );
        $this->assertTrue($consent->hasChoices());
        $this->assertSame(['ask' => true, 'gpc' => false], $consent->settings());
    }

    public function testAStoreThatDoesntAskStillHasChoicesWithDoNotSell(): void
    {
        $ga = $this->tracker(true, ['analytics' => 'pages (GA).', 'marketing' => 'ads (Google Ads).']);

        $this->assertFalse($this->consent(['ask' => '0'], [$ga])->hasChoices());
        $usStore = $this->consent(['ask' => '0', 'do_not_sell' => '1', 'gpc' => '1'], [$ga]);
        $this->assertTrue($usStore->hasChoices());
        $this->assertTrue($usStore->showsDoNotSell());
        $this->assertSame(['ask' => false, 'gpc' => true], $usStore->settings());
        // Nothing to opt out of without a marketing tracker.
        $analyticsOnly = $this->consent(['do_not_sell' => '1'], [$this->tracker(true, ['analytics' => 'pages'])]);
        $this->assertFalse($analyticsOnly->showsDoNotSell());
    }

    public function testPrivacyUrlPathsAreTheStoresAndNothingElseButHttp(): void
    {
        $ga = $this->tracker(true, ['analytics' => 'pages']);

        $this->assertSame('https://shop.test/ua/privacy', $this->consent(['privacy_url' => '/privacy'], [$ga])->privacyUrl());
        $this->assertSame('https://x.test/p', $this->consent(['privacy_url' => 'https://x.test/p'], [$ga])->privacyUrl());
        $this->assertSame('', $this->consent(['privacy_url' => 'javascript:alert(1)'], [$ga])->privacyUrl());
    }

    /**
     * @param array<string, string> $settings zero_consent/general/*
     * @param TrackerInterface[] $trackers
     */
    private function consent(array $settings, array $trackers): Consent
    {
        $scopeConfig = $this->createStub(ScopeConfigInterface::class);
        $field = static fn (string $path) => substr($path, strlen('zero_consent/general/'));
        $scopeConfig->method('isSetFlag')->willReturnCallback(static fn ($path) => (bool) ($settings[$field($path)] ?? false));
        $scopeConfig->method('getValue')->willReturnCallback(static fn ($path) => $settings[$field($path)] ?? null);
        $url = $this->createStub(UrlInterface::class);
        $url->method('getDirectUrl')->willReturnCallback(static fn ($path) => 'https://shop.test/ua/' . $path);

        return new Consent(new Config($scopeConfig), new Trackers($trackers), $url);
    }

    /**
     * @param array<string, string> $purposes
     */
    private function tracker(bool $active, array $purposes): TrackerInterface
    {
        $tracker = $this->createStub(TrackerInterface::class);
        $tracker->method('isActive')->willReturn($active);
        $tracker->method('purposes')->willReturn($purposes);

        return $tracker;
    }
}
