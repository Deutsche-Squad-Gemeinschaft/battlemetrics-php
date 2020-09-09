<?php

namespace BattlemetricsPHP\Tests;

use BattlemetricsPHP\Models\Leaderboard;
use BattlemetricsPHP\Tests\Abstracts\AbstractTest;

class LeaderboardsTest extends AbstractTest {
    /**
     * Verifies that an error will be thrown if an invalid
     * or unknown SteamID is provided.
     *
     * @return void
     * @test
     */
    public function it_can_get_the_playtime_leaderboards() {
        /* Get players with definitely not existing steamid */
        $leaderboard = $this->core->getTimeLeaderboard(3219649);

        /* Check that we got a Leaderboard instance */
        $this->assertInstanceOf(Leaderboard::class, $leaderboard);

        /* Check that it contains any data */
        $this->assertCount(100, $leaderboard->getData());

        /* Check that it contains a next page url */
        $this->assertNotNull($leaderboard->getNextUrl());
    }
}