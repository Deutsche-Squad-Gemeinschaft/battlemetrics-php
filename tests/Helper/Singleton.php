<?php

namespace BattlemetricsPHP\Tests\Helper;

use BattlemetricsPHP\BattlemetricsPHP;

class Singleton
{
    static ?BattlemetricsPHP $_instance = null;

    static function getInstance() : BattlemetricsPHP
    {
        if (!self::$_instance) {
            self::$_instance = new BattlemetricsPHP(getenv('BATTLEMETRICSPHP_API_KEY'));
        }

        return self::$_instance;
    }
}