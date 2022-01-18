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

    public static function whereMeta(...$arguments): Builder
    {
        return app(JobBatch::class)->whereMeta(...$arguments);
    }
}
