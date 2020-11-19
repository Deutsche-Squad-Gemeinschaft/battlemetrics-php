<?php

namespace BattlemetricsPHP;

abstract class MemoryRateLimitProvider extends AbstractRateLimitProvider
{
    private RedisAbstraction $redis;

    function __construct(RedisAbstraction $redis)
    {
        $this->redis = $redis;
    }

    protected function getAvailable() : int
    {
        return $this->redis->get('battlemetrics-php-available', 60);
    }

    protected function setAvailable(int $available) : void
    {
        $this->redis->set('battlemetrics-php-available', $available);
    }

    protected function getRemaining() : int
    {
        return $this->redis->get('battlemetrics-php-remaining', 60);
    }

    protected function setRemainingRequests(int $remaining) : void
    {
        $this->redis->set('battlemetrics-php-remaining', $remaining);
    }

    protected function getStore() : array
    {
        return $this->redis->get('battlemetrics-php-store', []);
    }

    protected function setStore(array $store) : void
    {
        $this->redis->set('battlemetrics-php-store', $store);
    }

    protected function addStore($value) : void
    {
        $store =  $this->getStore();

        $store[] = $value;

        $this->setStore($store);
    }

    protected function getEndpointStore() : array
    {
        return $this->redis->get('battlemetrics-php-endpointStore', []);
    }

    protected function setEndpointStore(array $endpointStore) : void
    {
        $this->redis->set('battlemetrics-php-endpointStore', $endpointStore);
    }
}
