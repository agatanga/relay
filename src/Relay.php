<?php

namespace Agatanga\Relay;

use Illuminate\Bus\Batch;

class Relay
{
    private $name = '';

    private $batches = [];

    private $options = [];

    public function name($name)
    {
        $this->name = $name;

        return $this;
    }

    public function option($key, $value)
    {
        $this->options[$key] = $value;

        return $this;
    }

    public function chain($jobs)
    {
        return $this->add([$jobs]);
    }

    public function batch($jobs)
    {
        return $this->add($jobs);
    }

    private function add($jobs)
    {
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

            $batch->options($this->options);

            if ($i + 1 < $total) {
                $next = $this->batches[$i + 1];

                $batch->then(function (Batch $batch) use ($next) {
                    $next->dispatch();
                });
            }
        }

        return $this->batches[0]->dispatch();
    }
}
