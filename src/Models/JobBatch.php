<?php

namespace Agatanga\Relay\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class JobBatch extends Model
{
    public function __construct(array $attributes = [])
    {
        if (!isset($this->table)) {
            $this->setTable(config('queue.batching.table') ?? 'job_batches');
        }

        parent::__construct($attributes);
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
