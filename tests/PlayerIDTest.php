<?php

namespace BattlemetricsPHP\Tests;

use BattlemetricsPHP\Models\Player;
use BattlemetricsPHP\Tests\Abstracts\AbstractTest;

class PlayerIDTest extends AbstractTest {
    /** @var int Expected SteamID */
    private $steamId;
     /** @var int Expected other SteamID */
    private $steamIdOther;

    /** @var int Expected Battlemetrics Player ID */
    private $playerId;

    /** @var int Expected other Battlemetrics Player ID */
    private $playerIdOther;

    protected function setUp() : void
    {
        parent::setUp();

        $this->steamId = intval(getenv('BATTLEMETRICSPHP_TEST_STEAMID'));
        $this->playerId = intval(getenv('BATTLEMETRICSPHP_TEST_BMID'));

        $this->steamIdOther = intval(getenv('BATTLEMETRICSPHP_TEST_ANOTHER_STEAMID'));
        $this->playerIdOther = intval(getenv('BATTLEMETRICSPHP_TEST_ANOTHER_BMID'));
    }

    /**
     * Verifies that an error will be thrown if an invalid
     * or unknown SteamID is provided.
     *
     * @return void
     * @test
     */
    public function it_throws_an_error_on_invalid_steam_ids() {
        /* Get players with definitely not existing steamid */
        $players = $this->core->getPlayerForSteamId([123123]);

        /* Should not contain anything */
        $this->assertCount(0, $players);
    }

    /**
     * Verifies that Player's id can be queried.
     *
     * @return void
     * @test
     */
    public function it_can_query_player_ids() {
        $players = $this->core->getPlayerForSteamId([$this->steamId]);

        /* Should not contain a player */
        $this->assertCount(1, $players);

        foreach ($players as $player) {
            /* Check that whe have a player */
            $this->assertInstanceOf(Player::class, $player);

            /* Verify it is the correct id */
            $this->assertSame($this->playerId, $player->getId());
        }
    }

    /**
     * Verifies that Player's id can be queried.
     *
     * @return void
     * @test
     */
    public function it_can_query_multiple_player_ids() {
        $players = $this->core->getPlayerForSteamId([$this->steamId, $this->steamIdOther]);

        /* Should not contain two players */
        $this->assertCount(2, $players);

        foreach ($players as $steamId => $player) {

        }

        /* Check that whe have players */
        $this->assertInstanceOf(Player::class, $players[$this->steamId]);
        $this->assertInstanceOf(Player::class, $players[$this->steamIdOther]);

        /* Verify it is the correct id */
        $this->assertSame($this->playerId, $players[$this->steamId]->getId());
        $this->assertSame($this->playerIdOther, $players[$this->steamIdOther]->getId());
    }

    /**
     * Verifies that Player's id can be queried.
     *
     * @return void
     * @test
     */
    public function it_can_query_multiple_player_ids_with_failures() {
        $players = $this->core->getPlayerForSteamId([$this->steamId, $this->steamIdOther, 123123]);

        /* Should not contain two players */
        $this->assertCount(2, $players);

        /* Check that whe have a player */
        $this->assertInstanceOf(Player::class, $players[$this->steamId]);
        $this->assertInstanceOf(Player::class, $players[$this->steamIdOther]);

        /* Verify it is the correct id */
        $this->assertSame($this->playerId, $players[$this->steamId]->getId());
        $this->assertSame($this->playerIdOther, $players[$this->steamIdOther]->getId());
    }
}