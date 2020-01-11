<?php

namespace BattlemetricsPHP\Models;

class Player {
    /** @var int */
    protected $id;

    function __construct(int $id)
    {
        $this->id = $id;
    }

    /**
     * Returns the Player's id.
     *
     * @return integer
     */
    public function getId() : int {
        return $this->id;
    }
}