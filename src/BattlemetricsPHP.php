<?php

namespace BattlemetricsPHP;

use BattlemetricsPHP\Exceptions\PlayerNotFoundException;
use BattlemetricsPHP\Models\Leaderboard;
use BattlemetricsPHP\Models\Player;
use BattlemetricsPHP\RateLimiterMiddleware;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\CurlHandler;
use GuzzleHttp\HandlerStack;

class BattlemetricsPHP {
    private string $apiKey;
    private Client $client;

    /**
     * Undocumented function
     *
     * @param string $apiKey OAuth2.0 Bearer Key
     * @param string $apiURL URL to the Battlemetrics API, should be https://api.battlemetrics.com
     */
    function __construct(string $apiKey, string $apiURL = 'https://api.battlemetrics.com', int $timeout = 15)
    {
        $this->apiKey = $apiKey;

        $stack = new HandlerStack();
        $stack->setHandler(new CurlHandler());
        $stack->push(new RateLimiterMiddleware());

        /* Initialize GuzzleClient */
        $this->client = new Client([
            // Base URI is used with relative requests
            'base_uri' => $apiURL,
            'handler' => $stack,
            'timeout'  => $timeout,
        ]);

        
    }

    /**
     * Searches a Player based on the associated SteamID.
     * 
     * Note: Will only find players that are associated to a server
     * with the correct RCON permissions set-up for the provided API key.
     *
     * @param int[] $steamId The steamId associated to the player.
     * @return array
     */
    public function getPlayerForSteamId(array $steamIds) : array {
        /* Build Request data */
        $requestData = [
            'data' => []
        ];

        foreach ($steamIds as $sId) {
            array_push($requestData['data'], [
                'type' => 'identifier',
                'attributes' => [
                    'type' => 'steamID',
                    'identifier' => (string)$sId // Format requires quotes around the steam id => string
                ]
            ]);
        }

        $response = $this->client->request('POST', '/players/match', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
            ],
            'json' => $requestData,
        ]);

        /* Validate response status code */
        if ($response->getStatusCode() !== 200) {
            /* No player found, throw Exception */
            throw new PlayerNotFoundException('Could not read /players/match endpoint. Status code: ' . $response->getStatusCode());
        }

        /* Get JSON data from response */
        $data = json_decode($response->getBody(), true);

        /* Process results */
        $output = [];
        $results = self::getValueOrNull($data, ['data']) ?? [];
        foreach ($results as $r) {
            /* Check if ID is SteamID */
            $idType = self::getValueOrNull($r, ['attributes', 'type']);
            if ($idType !== 'steamID') {
                continue;
            }

            /* Get SteamID */
            $rSteamID = self::getValueOrNull($r, ['attributes', 'identifier']);
            if (!$rSteamID) {
                continue;
            }

            /* Get BattlemetricsID */
            $rBMID = self::getValueOrNull($r, ['relationships', 'player', 'data', 'id']);
            if (!$rBMID) {
                continue;
            }


            /* Add to the output data */
            $output[intval($rSteamID)] = new Player(intval($rBMID));
        }

        /* Found, initialize Player */
        return $output;
    }

    /**
     * Retrieves the leaderboard endpoint for a given server id.
     * Will filter for a specific player (api side) if not null.
     *
     * @param integer $serverId
     * @param integer|null $bmPlayerId
     * @return array
     */
    public function getTimeLeaderboard(int $serverId, int $bmPlayerId = null) : Leaderboard
    {
        /* Build the endpoint and add the necessary filters */
        $endpoint = '/servers/' . $serverId . '/relationships/leaderboards/time?filter[period]=AT&page[size]=100';
        if ($bmPlayerId) {
            $endpoint .= '&filter[player]=' . $bmPlayerId;
        }
        /* Build Request and exec */
        $response = $this->client->request('GET', $endpoint, [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
            ],
        ]);

        /* Get JSON data from response */
        $result = json_decode($response->getBody(), true);

        return new Leaderboard(self::getValueOrNull($result, ['data']), self::getValueOrNull($result, ['links', 'next']));
    }

    private static function getValueOrNull(array $data, array $path) {
        $value = $data;

        foreach($path as $p) {
            if (isset($value[$p])) {
                $value = $value[$p];
            } else {
                return null;
            }
        }

        return $value;
    }
}