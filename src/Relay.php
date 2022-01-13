<?php

namespace Agatanga\Relay;

class Relay
{
    private $name = '';

    private $batches = [];

    public function name($name)
    {
        $this->name = $name;

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

        foreach ($this->batches as $i => $batch) {
            $batch->name(trans($this->name, [
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
