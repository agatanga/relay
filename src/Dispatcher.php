<?php

namespace Agatanga\Relay;

class Dispatcher
{
    private $name = '';

    private $meta = [];

    private $batches = [];

    public function name($name)
    {
        $this->name = $name;

        return $this;
    }

    public function meta($key, $value = null)
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

    public function batch($name, $jobs = null)
    {
        return $this->add($name, $jobs);
    }

    public function chain($name, $jobs = null)
    {
        if (is_array($name)) {
            $jobs = $name;
            $name = null;
        }

        $jobs = array_filter($jobs);

        if (!$jobs) {
            return $this;
        }

        return $this->add($name, [$jobs]);
    }

    public function then($name, $jobs = null)
    {
        return $this->add($name, $jobs);
    }

    public function finally($name, $jobs = null)
    {
        return $this->add($name, $jobs, 'finally');
    }

    private function add($name, $jobs, $method = 'then')
    {
        if (is_array($name)) {
            $jobs = $name;
            $name = null;
        }

        $jobs = array_filter($jobs);

        if (!$jobs) {
            return $this;
        }

        $this->batches[] = new LazyBatch($name, $jobs, $method);

        return $this;
    }

    public function dispatch()
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
}
