<?php

namespace Agatanga\Relay\Facades;

use Illuminate\Support\Facades\Facade;
use Agatanga\Relay\Relay as AgatangaRelay;

class Relay extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return AgatangaRelay::class;
    }
}
