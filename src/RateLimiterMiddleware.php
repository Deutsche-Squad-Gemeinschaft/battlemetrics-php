<?php

namespace BattlemetricsPHP;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;


class RateLimiterMiddleware
{
    private int $available = 60;
    private int $remaining = 60;

    private array $store = [];
    private array $endpointStore = [];

    static array $endpoints = [
        '/^.*\/players\/match$/' => 60000, // /players/match
    ];

    public function __invoke(callable $handler)
    {
        return function (RequestInterface $request, array $options) use ($handler) {
            return $this->handle($request->getUri(), function () use ($request, $handler, $options) {
                return $handler($request, $options);
            });
        };
    }

    protected function handle(string $uri, callable $callback) {
        /* Try to determine the endpoint of this uri */
        $endpoint = $this->getEndpointForUri($uri);

        /* Wait for the delay and update until there is none */
        while (($delay = $this->delayUntilNextRequest($endpoint)) > 0) {
            /* Wait until the request is safe */
            $this->msleep($delay);
        }

        /* Add the current time to the global & endpoint store (if set) */
        $now = $this->milliseconds();
        $this->store[] = $now;
        if ($endpoint) {
            if (!array_key_exists($endpoint, $this->endpointStore)) {
                $this->endpointStore[$endpoint] = [];
            }
            $this->endpointStore[$endpoint][] = $now;
        }

        /* Run request and set allowance afterwards */
        return $callback()->then($this->setRemaining());
    }

    protected function setRemaining() {
        return function (ResponseInterface $response) {
            if ($response->hasHeader('X-Rate-Limit-Limit') && $response->hasHeader('X-Rate-Limit-Remaining')) {
                $this->available = intval($response->getHeader('X-Rate-Limit-Limit')[0]);
                $this->remaining = intval($response->getHeader('X-Rate-Limit-Remaining')[0]);
            }
            
            return $response;
        };
    }

    /**
     * Gets the delay for the next request depending
     * on requests already made and retrieved headers.
     * Default values are from the Battlemetrics docs.
     * 
     * @return int microseconds to wait for the next request
     */
    protected function delayUntilNextRequest(?string $endpoint = null) : int
    {
        $delay = 0;

        /* If there is not last time it is safe to request */
        if (($last = end($this->store))) {
            /* Reset store & remaining if last request is older than one minute */
            if ($last < $this->milliseconds() - 1000 * 60) {
                $this->reset();
            }

            /* If there are no more requests remaining we will have to wait one minute */
            if (!$this->remaining) {
                $delay = (1000 * 60) - ($this->milliseconds() - $last);
            } else {
                /* If there are more than 5 requests it is possible we are bursting */
                if (count($this->store) >= 5) {
                    $burstStart = $this->store[count($this->store) -  5];

                    /* Check if burst start was within this second */
                    if ($burstStart > $this->milliseconds() - 1000) {
                        $delay = ($burstStart + 1000) - $this->milliseconds();
                    }
                }
            }
        }

        $endpointModifier = 0;
        if ($endpoint && array_key_exists($endpoint, $this->endpointStore) && count($this->endpointStore[$endpoint])) {
            $last = end($this->endpointStore[$endpoint]);
            if (self::$endpoints[$endpoint] >= ($this->milliseconds() - $last)) {
                $endpointModifier = self::$endpoints[$endpoint] - ($this->milliseconds() - $last);
            }
        }

        return $delay + $endpointModifier;
    }

    /**
     * Helper to reset the store & remaining members.
     *
     * @return void
     */
    protected function reset() : void
    {
        $this->store = [];
        $this->remaining = $this->available;
    }

    /**
     * Microtime but as milliseconds and integer
     *
     * @return integer
     */
    protected function milliseconds() : int 
    {
        return round(microtime(true) * 1000);
    }

    /**
     * usleep but for milliseconds.
     */
    protected function msleep(int $time) : void
    {
        usleep($time * 1000);
    }

    protected function getEndpointForUri(string $uri) : ?string
    {
        foreach (self::$endpoints as $regex => $rateLimit) {
            if (preg_match($regex, $uri) === 1) {
                return $regex;
            }
        }

        return null;
    }
}