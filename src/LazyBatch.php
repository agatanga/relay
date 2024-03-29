<?php

namespace Agatanga\Relay;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Bus;
use Illuminate\Bus\Batchable;
use Laravel\SerializableClosure\SerializableClosure;

class LazyBatch
{
    public $jobs;

    public $name;

    public $callback;

    public $method = 'then';

    public function __construct($name, $jobs, $method)
    {
        $this->validateJobs($jobs);

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

    public function through($middleware)
    {
        $middleware = array_filter(Arr::wrap($middleware));

        if (!$middleware) {
            return $this;
        }

        foreach ($this->jobs as $jobs) {
            $jobs = Arr::wrap($jobs);

            foreach ($jobs as $job) {
                if ($job->middleware) {
                    continue;
                }

                $job->through($middleware);
            }
        }

        return $this;
    }

    public function __call($method, $parameters)
    {
        foreach ($this->jobs as $jobs) {
            $jobs = Arr::wrap($jobs);

            foreach ($jobs as $job) {
                $job->{$method}(...$parameters);
            }
        }

        return $this;
    }

    private function validateJobs(array $jobs)
    {
        foreach ($jobs as $job) {
            if (is_array($job)) {
                return $this->validateJobs($job);
            }

            if (!in_array(Batchable::class, class_uses_recursive($job))) {
                throw new \RuntimeException('Class ['.get_class($job).'] does not use the [Illuminate\Bus\Batchable] trait.');
            }
        }
    }
}
