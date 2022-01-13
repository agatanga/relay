<?php

namespace Agatanga\Relay;

use Illuminate\Support\Facades\Bus;
use Laravel\SerializableClosure\SerializableClosure;

class Batch
{
    public $jobs;

    public $name;

    public $callback;

    public $method = 'then';

    public function __construct($jobs, $method)
    {
        $this->jobs = $jobs;
        $this->method = $method;
    }

    public function name($name)
    {
        $this->name = $name;
    }

    public function callback($callback)
    {
        $this->callback = serialize(new SerializableClosure($callback));
    }

    public function dispatch()
    {
        $batch = Bus::batch($this->jobs)->name($this->name);

        if ($this->callback && in_array($this->method, ['then', 'finally'])) {
            $batch->{$this->method}(unserialize($this->callback)->getClosure());
        }

        return $batch->dispatch();
    }
}
