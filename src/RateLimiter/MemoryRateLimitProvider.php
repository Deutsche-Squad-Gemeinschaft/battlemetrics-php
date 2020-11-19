<?php

namespace BattlemetricsPHP;

abstract class MemoryRateLimitProvider extends AbstractRateLimitProvider
{
    private int $available = 60;
    private int $remaining = 60;

    private array $store = [];
    private array $endpointStore = [];

    protected function getAvailable() : int
    {
        return $this->available;
    }

    protected function setAvailable(int $available) : void
    {
        $this->available = $available;
    }

    protected function getRemaining() : int
    {
        return $this->remaining;
    }

    protected function setRemainingRequests(int $remaining) : void
    {
        $this->remaining = $remaining;
    }

    protected function getStore() : array
    {
        return $this->store;
    }

    protected function setStore(array $store) : void
    {
        $this->store = $store;
    }

    protected function addStore($value) : void
    {
        $this->store[] = $value;
    }

    protected function getEndpointStore() : array
    {
        return $this->endpointStore;
    }

    protected function setEndpointStore(array $store) : void
    {
        $this->endpointStore = $store;
    }
}
