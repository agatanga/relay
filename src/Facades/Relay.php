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
            'model',
            'where',
            'whereMeta',
            'whereName',
        ];

        if (in_array($method, $modelMethods)) {
            $model = app(JobBatch::class)->latest('id');

            if ($method === 'model') {
                return $model;
            }

            return $model->{$method}(...$args);
        }

        return parent::__callStatic($method, $args);
    }
}
