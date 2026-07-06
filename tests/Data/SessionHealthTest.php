<?php

namespace SocialDept\AtpClient\Tests\Data;

use PHPUnit\Framework\TestCase;
use SocialDept\AtpClient\Data\SessionHealth;
use SocialDept\AtpClient\Enums\RefreshFailureReason;

class SessionHealthTest extends TestCase
{
    public function test_healthy(): void
    {
        $h = SessionHealth::healthy();

        $this->assertTrue($h->isHealthy());
        $this->assertFalse($h->isTerminal());
        $this->assertFalse($h->needsRefresh());
    }

    public function test_stale_access_needs_refresh_but_not_terminal(): void
    {
        $h = SessionHealth::staleAccess();

        $this->assertFalse($h->isHealthy());
        $this->assertTrue($h->needsRefresh());
        $this->assertFalse($h->isTerminal());
    }

    public function test_inactive_is_terminal(): void
    {
        $h = SessionHealth::inactive('deactivated');

        $this->assertTrue($h->isTerminal());
        $this->assertFalse($h->needsRefresh());
        $this->assertSame('deactivated', $h->status);
        $this->assertSame(RefreshFailureReason::AccountInactive, $h->reason);
    }

    public function test_terminal_reason(): void
    {
        $h = SessionHealth::terminal(RefreshFailureReason::InvalidGrant);

        $this->assertTrue($h->isTerminal());
        $this->assertFalse($h->isHealthy());
        $this->assertSame(RefreshFailureReason::InvalidGrant, $h->reason);
    }

    public function test_unreachable_is_never_terminal(): void
    {
        $h = SessionHealth::unreachable(RefreshFailureReason::Network);

        $this->assertFalse($h->reachable);
        $this->assertFalse($h->isTerminal());
        $this->assertFalse($h->isHealthy());
        $this->assertFalse($h->needsRefresh());
    }
}
