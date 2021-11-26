<?php

namespace Agatanga\Relay;

use Illuminate\Bus\Batch;

class Relay
{
    private $name = '';

    private $batches = [];

    public function name($name)
    {
        $this->name = $name;

        return $this;
    }

    public function chain($jobs)
    {
        return $this->add($jobs, true);
    }

    public function batch($jobs)
    {
        return $this->add($jobs);
    }

    private function add($jobs, $chain = false)
    {
        $jobs = array_filter($jobs);

        if (!$jobs) {
            return $this;
        }

        if ($chain) {
            $jobs = [$jobs];
        }

        $this->batches[] = new LazyBatch($jobs);

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

        $then = false;
        $i = $total;

        while (--$i >= 0) {
            $current = $this->batches[$i];

            if ($then) {
                $current->then($then);
            }

            $then = function () use ($current) {
                $current->dispatch();
            };
        }

        return $this->batches[0]->dispatch();
    }
}
