<?php

namespace BattlemetricsPHP;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;


class RateLimiterMiddleware
{
    private array $store = [];
    private int $available = 60;
    private int $remaining = 60;

    public function __invoke(callable $handler)
    {
        return function (RequestInterface $request, array $options) use ($handler) {
            return $this->handle(function () use ($request, $handler, $options) {
                return $handler($request, $options);
            });
        };
    }

    protected function handle(callable $callback) {
        /* Get the delay for the request to be safe */
        $delay = $this->delayUntilNextRequest();

        /* Wait for the delay and update until there is none */
        while ($delay > 0) {
            /* Wait until the request is safe */
            usleep($delay);

            /* Update the delay in case another request has been made */
            $delay = $this->delayUntilNextRequest();
        }

        /* Add the current time to the store */
        $this->store[] = microtime(true);

        /* Run request and set allowance afterwards */
        return $callback()->then($this->setRemaining());
    }

    protected function setRemaining() {
        return function (ResponseInterface $response) {
            if ($response->hasHeader('X-Rate-Limit-Limit') && $response->hasHeader('X-Rate-Limit-Remaining')) {
                $this->available = intval($response->getHeader('X-Rate-Limit-Limit'));
                $this->remaining = intval($response->getHeader('X-Rate-Limit-Remaining'));
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
    protected function delayUntilNextRequest() : int
    {
        /* Get microtime of the last request */
        $last = end($this->store);

        /* If there is not last time it is safe to request */
        if ($last) {
            /* Reset store & remaining if last request is older than one minute */
            if ($last < microtime(true) - 1000000 * 60) {
                $this->reset();
            }

            /* If there are no more requests remaining we will have to wait one minute */
            if (!$this->remaining) {
                return (1000000 * 60) - (microtime(true) - $last);
            }
            
            /* If there are more than 5 requests it is possible we are bursting */
            if (count($this->store) > 5) {
                $burstStart = $this->store[count($this->store) -  5];

                /* Check if burst start was within this second */
                if ($burstStart > microtime(true) - 1000000) {
                    return ($burstStart + 1000000) - microtime(true);
                }
            }
        }

        return 0;
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
}