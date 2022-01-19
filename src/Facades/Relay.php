<?php

namespace Agatanga\Relay\Facades;

use Agatanga\Relay\Dispatcher;
use Agatanga\Relay\Models\JobBatch;

class Relay
{
    public static function __callStatic($method, $args)
    {
        $modelMethods = [
            'model',
            'where',
            'whereMeta',
            'whereName',
        ];

        if (in_array($method, $modelMethods)) {
            $model = app(JobBatch::class);

            if ($method === 'model') {
                return $model;
            }

            return $model->latest('id')->{$method}(...$args);
        }

        return app(Dispatcher::class)->{$method}(...$args);
    }
}
