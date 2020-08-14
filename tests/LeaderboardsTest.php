<?php

namespace BattlemetricsPHP\Tests;

use PHPUnit\Framework\TestCase;
use BattlemetricsPHP\BattlemetricsPHP;
use BattlemetricsPHP\Models\Player;
use Dotenv\Dotenv;

class LeaderboardsTest extends TestCase {
    /** @var BattlemetricsPHP */
    protected $core;

    protected function setUp() : void
    {
        $this->core = new BattlemetricsPHP(getenv('BATTLEMETRICSPHP_API_KEY'));
    }

    /**
     * Verifies that an error will be thrown if an invalid
     * or unknown SteamID is provided.
     *
     * @return void
     * @test
     */
    public function it_can_get_the_playtime_leaderboards() {
        /* Get players with definitely not existing steamid */
        $players = $this->core->getTimeLeaderboard(3219649);

        /* Should not contain anything */
        $this->assertTrue(count($players));

        /* API rate limit */
        sleep(1);
    }
}