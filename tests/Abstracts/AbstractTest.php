<?php

namespace BattlemetricsPHP\Tests\Abstracts;

use BattlemetricsPHP\BattlemetricsPHP;
use BattlemetricsPHP\Tests\Helper\Singleton;
use Dotenv\Dotenv;
use PHPUnit\Framework\TestCase;

abstract class AbstractTest extends TestCase
{
    protected BattlemetricsPHP $core;

    protected function setUp() : void
    {
        $dotenv = Dotenv::createImmutable(__DIR__ . '/../../');
        $dotenv->load();
        
        $this->core = Singleton::getInstance();
    }
}