<?php

namespace Agatanga\Relay\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class JobBatch extends Model
{
    public function __construct()
    {
        parent::__construct();
        $this->table = config('queue.batching.table') ?? 'job_batches';
    }

    public function scopeWhereMeta(Builder $query, $key, $value): Builder
    {
        return $query->where('name', 'like', "%[{$key}:{$value}]%");
    }

    public function scopeWhereName(Builder $query, $name): Builder
    {
        return $query->where('name', 'like', "{$name}|[%");
    }
}
