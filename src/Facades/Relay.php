<?php

namespace Agatanga\Relay\Facades;

use Agatanga\Relay\Models\JobBatch;
use Agatanga\Relay\Dispatcher;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Facade;

class Relay extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return Dispatcher::class;
    }

    public static function whereMeta(...$arguments): Builder
    {
        return app(JobBatch::class)->whereMeta(...$arguments);
    }
}
