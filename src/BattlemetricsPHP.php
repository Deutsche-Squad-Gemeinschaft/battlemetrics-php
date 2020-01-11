<?php

namespace BattlemetricsPHP;

use BattlemetricsPHP\Exceptions\PlayerNotFoundException;
use BattlemetricsPHP\Models\Player;

class BattlemetricsPHP {
    /** @var string */
    private $apiKey;

    /** @var string */
    private $apiURL;

    /**
     * Undocumented function
     *
     * @param string $apiKey OAuth2.0 Bearer Key
     * @param string $apiURL URL to the Battlemetrics API, should be https://api.battlemetrics.com
     */
    function __construct(string $apiKey, string $apiURL = 'https://api.battlemetrics.com')
    {
        $this->apiKey = $apiKey;
        $this->apiURL = $apiURL;
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
    public function getPlayerForSteamId(array $steamId) : array {
        /* Build Request data */
        $data = [
            'data' => []
        ];

        foreach ($steamId as $sId) {
            array_push($data['data'], [
                'type' => 'identifier',
                'attributes' => [
                    'type' => 'steamID',
                    'identifier' => (string)$sId // Format requires quotes around the steam id => string
                ]
            ]);
        }

        $data = json_encode($data);

        /* Build Request and exec */
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->apiURL . '/players/match');
        curl_setopt($ch, CURLOPT_POST, 1); // POST
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [                                                                          
            'Content-Type: application/json',                                                                                
            'Content-Length: ' . strlen($data),
            'Authorization: Bearer ' . $this->apiKey,
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Receive response
        $response = json_decode(curl_exec($ch), true);

        /* Validate result Format */
        if (!$response || !is_array($response)) {
            /* No player found, throw Exception */
            throw new PlayerNotFoundException("Could not find a player for SteamIDs");
        }

        /* Process results */
        $output = [];
        $results = self::getValueOrNull($response, ['data']) ?? [];
        foreach ($results as $r) {
            /* Check if ID is SteamID */
            $idType = self::getValueOrNull($r, ['attributes', 'type']);
            if ($idType !== 'steamID') {
                echo 'COULD NOT FIND ID TYPE';
                continue;
            }

            /* Get SteamID */
            $rSteamID = self::getValueOrNull($r, ['attributes', 'identifier']);
            if (!$rSteamID) {
                echo 'COULD NOT FIND STEAMID';
                continue;
            }

            /* Get BattlemetricsID */
            $rBMID = self::getValueOrNull($r, ['relationships', 'player', 'data', 'id']);
            if (!$rBMID) {
                echo 'COULD NOT FIND BMID';
                continue;
            }


            $output[intval($rSteamID)] = new Player(intval($rBMID));
        }

        /* Found, initialize Player */
        return $output;
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