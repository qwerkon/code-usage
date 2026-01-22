<?php

namespace Qwerkon\CodeUsage\Tracker;

use Qwerkon\CodeUsage\Contracts\CodeUsageTracker;
use Illuminate\Container\Container;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Str;

class TrackingDispatcher extends Dispatcher
{
    public function __construct(Container $container, protected CodeUsageTracker $tracker)
    {
        parent::__construct($container);
    }

    public function dispatch($event, $payload = [], $halt = false)
    {
        $eventName = is_object($event) ? get_class($event) : $event;

        if (is_string($eventName)) {
            $this->tracker->track($eventName, 'event');
        }

        return parent::dispatch($event, $payload, $halt);
    }

    public function createClassListener($listener, $wildcard = false)
    {
        $callable = parent::createClassListener($listener, $wildcard);
        $class = is_array($listener) ? $listener[0] : Str::before($listener, '@');

        return function ($event, $payload) use ($callable, $class, $wildcard) {
            if ($class) {
                $this->tracker->track($class, 'listener');
            }

            return $callable($event, $payload);
        };
    }
}
