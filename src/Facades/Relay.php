<?php

namespace Agatanga\Relay\Facades;

use Agatanga\Relay\JobBatch;
use Agatanga\Relay\Dispatcher;
use Illuminate\Support\Facades\Facade;

class Relay extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return Dispatcher::class;
    }

    public static function where(...$arguments): JobBatch
    {
        return app(JobBatch::class)->where(...$arguments);
    }
}
