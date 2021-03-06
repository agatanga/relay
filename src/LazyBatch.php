<?php

namespace Agatanga\Relay;

use Illuminate\Support\Facades\Bus;
use Laravel\SerializableClosure\SerializableClosure;

class LazyBatch
{
    public $jobs;

    public $name;

    public $callback;

    public $method = 'then';

    public function __construct($name, $jobs, $method)
    {
        $this->name = $name;
        $this->jobs = $jobs;
        $this->method = $method;
    }

    public function name($name): static
    {
        $this->name = $name;

        return $this;
    }

    public function callback($callback): static
    {
        $this->callback = serialize(new SerializableClosure($callback));

        return $this;
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
