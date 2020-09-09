<?php

/**
 * Small helper file for local testing.
 */
require_once('vendor/autoload.php');

use BattlemetricsPHP\BattlemetricsPHP;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$bm = new BattlemetricsPHP(getenv('BATTLEMETRICSPHP_API_KEY'));

// Do stuff below