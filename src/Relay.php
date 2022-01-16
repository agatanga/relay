<?php

namespace Agatanga\Relay;

class Relay
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

    public function batch($jobs)
    {
        return $this->add($jobs);
    }

    public function chain($jobs)
    {
        $jobs = array_filter($jobs);

        if (!$jobs) {
            return $this;
        }

        return $this->add([$jobs]);
    }

    public function then($jobs)
    {
        return $this->add($jobs);
    }

    public function finally($jobs)
    {
        return $this->add($jobs, 'finally');
    }

    private function add($jobs, $method = 'then')
    {
        $jobs = array_filter($jobs);

        if (!$jobs) {
            return $this;
        }

        $this->batches[] = new Batch($jobs, $method);

        return $this;
    }

    public function dispatch()
    {
        $total = count($this->batches);
        $name = $this->name . '|';

        foreach ($this->meta as $key => $value) {
            $name .= "[{$key}:{$value}]";
        }

        $name .= '[:current/:total]';

        foreach ($this->batches as $i => $batch) {
            $batch->name(trans($name, [
                'current' => $i + 1,
                'total' => $total,
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
