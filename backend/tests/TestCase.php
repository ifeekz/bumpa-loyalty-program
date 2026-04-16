<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Event;

abstract class TestCase extends BaseTestCase
{

    protected function setUp(): void
    {
        parent::setUp();

        // Tell Laravel to use the 'api' guard for all auth calls in tests
        $this->app['config']->set('auth.defaults.guard', 'api');

        // Prevent real broadcasting (Redis/Pusher) during tests.
        // Feature tests that need to assert events are dispatched use
        // Event::fake() explicitly at the top of the test.
        Event::fake([
            \App\Domain\Loyalty\Events\AchievementUnlocked::class,
            \App\Domain\Loyalty\Events\BadgeUnlocked::class,
        ]);
    }
}
