<?php

namespace Agatanga\Relay\Facades;

use Agatanga\Relay\Dispatcher;
use Agatanga\Relay\Models\JobBatch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Facade;

class Relay extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return Dispatcher::class;
    }

    public static function __callStatic($method, $args)
    {
        $modelMethods = [
            'where',
            'whereMeta',
            'whereName',
        ];

        if (in_array($method, $modelMethods)) {
            return app(JobBatch::class)->{$method}(...$args);
        } elseif ($method === 'model') {
            return app(JobBatch::class);
        }

        return parent::__callStatic($method, $args);
    }
}
