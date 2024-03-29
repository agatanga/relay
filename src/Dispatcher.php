<?php

namespace Agatanga\Relay;

class Dispatcher
{
    private $name = '';

    private $meta = [];

    private $batches = [];

    public function name($name): static
    {
        $this->name = $name;

        return $this;
    }

    public function meta($key, $value = null): static
    {
        if (is_array($key)) {
            $meta = $key;
        } else {
            $meta = [$key => $value];
        }

        foreach ($meta as $key => $value) {
            if (strpos($key, '.') === false) {
                continue;
            }

            $entity = explode('.', $key)[0];

            if (!isset($this->meta[$entity])) {
                $this->meta[$entity] = $value;
            }
        }

        $this->meta = array_merge($this->meta, $meta);

        return $this;
    }

    public function batch($name, $jobs = null, $middleware = null): static
    {
        return $this->add($name, $jobs, $middleware);
    }

    public function chain($name, $jobs = null, $middleware = null): static
    {
        if (is_array($name)) {
            $middleware = $jobs;
            $jobs = $name;
            $name = null;
        }

        $jobs = array_filter($jobs);

        if (!$jobs) {
            return $this;
        }

        return $this->add($name, [$jobs], $middleware);
    }

    public function then($name, $jobs = null, $middleware = null): static
    {
        return $this->add($name, $jobs, $middleware);
    }

    public function finally($name, $jobs = null, $middleware = null): static
    {
        return $this->add($name, $jobs, $middleware, 'finally');
    }

    private function add($name, $jobs, $middleware, $method = 'then'): static
    {
        if (is_array($name)) {
            $middleware = $jobs;
            $jobs = $name;
            $name = null;
        }

        $jobs = array_filter($jobs);

        if (!$jobs) {
            return $this;
        }

        $this->batches[] = (new LazyBatch($name, $jobs, $method))->through($middleware);

        return $this;
    }

    public function dispatch(): mixed
    {
        $total = count($this->batches);
        $meta = '|';

        foreach ($this->meta as $key => $value) {
            $meta .= "[{$key}:{$value}]";
        }

        $meta .= '[:current/:total]';

        foreach ($this->batches as $i => $batch) {
            $name = $this->name;

            if ($batch->name) {
                $name = $batch->name;
            }

            $batch->name(strtr($name . $meta, [
                ':current' => $i + 1,
                ':total' => $total,
            ]));
        }

        $callback = false;
        $i = $total;

        while (--$i >= 0) {
            $current = $this->batches[$i];

            if ($callback) {
                $current->callback($callback);
            }

            $callback = function () use ($current) {
                $current->dispatch();
            };
        }

        return $this->batches[0]->dispatch();
    }

    public function __call($method, $parameters)
    {
        foreach ($this->batches as $batch) {
            $batch->{$method}(...$parameters);
        }

        return $this;
    }
}
