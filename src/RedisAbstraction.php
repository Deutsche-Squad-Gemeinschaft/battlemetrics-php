<?php

namespace BattlemetricsPHP;

abstract class RedisAbstraction {
    public abstract function set(string $key, $value);
    public abstract function get(string $key, $default = null);
}