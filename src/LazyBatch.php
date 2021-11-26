<?php

namespace Agatanga\Relay;

use Illuminate\Support\Facades\Bus;
use Laravel\SerializableClosure\SerializableClosure;

class LazyBatch
{
    public $jobs;

    public $name;

    public $callback;

    public function __construct($jobs)
    {
        $this->jobs = $jobs;
    }

    public function name($name)
    {
        $this->name = $name;
    }

    public function then($callback)
    {
        $this->callback = serialize(new SerializableClosure($callback));
    }

    public function dispatch()
    {
        $batch = Bus::batch($this->jobs)->name($this->name);

        if ($this->callback) {
            $batch->then(unserialize($this->callback)->getClosure());
        }

        return $batch->dispatch();
    }
}
