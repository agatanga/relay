<?php

namespace Agatanga\Relay;

use Illuminate\Support\Facades\Bus;

class LazyBatch
{
    public $jobs;

    public $name;

    public $options = [];

    public $callback;

    public function __construct($jobs)
    {
        $this->jobs = $jobs;
    }

    public function name($name)
    {
        $this->name = $name;
    }

    public function options($options)
    {
        $this->options = $options;
    }

    public function then($callback)
    {
        $this->callback = $callback;
    }

    public function dispatch()
    {
        $batch = Bus::batch($this->jobs)->name($this->name);

        if ($this->callback) {
            $batch->then($this->callback);
        }

        if ($this->options) {
            $batch->options = array_merge($this->options, $batch->options);
        }

        return $batch->dispatch();
    }
}
