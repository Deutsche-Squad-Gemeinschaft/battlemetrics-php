<?php

namespace BattlemetricsPHP\Models;

class Leaderboard
{
    protected array $data;
    protected ?string $nextUrl;

    function __construct(array $data, ?string $nextUrl = null)
    {
        $this->data = $data;
        $this->nextUrl = $nextUrl;
    }

    function getData() : array
    {
        return $this->data;
    }

    function getNextUrl() : ?string
    {
        return $this->nextUrl;
    }
}