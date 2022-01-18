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

    public function progress()
    {
        $progress = $this->total_jobs > 0 ?
            round(($this->processedJobs() / $this->total_jobs) * 100) :
            0;

        list($min, $max) = array_values($this->range);

        return round($min + ($progress * ($max - $min) / 100));
    }

    public function processedJobs()
    {
        return $this->total_jobs - $this->pending_jobs;
    }

    public function finished()
    {
        return !is_null($this->finished_at) && $this->range['max'] === 100;
    }

    public function running()
    {
        return !$this->failed_jobs && !$this->finished();
    }

    public function getNameAttribute($value)
    {
        if ($value) {
            return substr($value, 0, strpos($value, '|['));
        }

        return $value;
    }

    public function getRangeAttribute()
    {
        preg_match('/\[(\d+)\/(\d+)\]$/', $this->attributes['name'] ?? '', $matches);

        $min = 0;
        $max = 100;

        if ($matches) {
            $min = (($matches[1] - 1) / $matches[2]) * 100;
            $max = ($matches[1] / $matches[2]) * 100;
        }

        return [
            'min' => $min,
            'max' => $max,
        ];
    }

    public function scopeWhereMeta(Builder $query, $key, $value): Builder
    {
        if (!is_array($value)) {
            $value = [$value];
        }

        $conditions = [];
        foreach ($value as $val) {
            $conditions[] = $key . ':' . $val;
        }

        return $query->where(function ($q) use ($conditions) {
            foreach ($conditions as $condition) {
                $q->orWhere('name', 'like', '%[' . $condition . ']%');
            }
        });
    }

    public function scopeWhereName(Builder $query, $name): Builder
    {
        return $query->where('name', 'like', "{$name}|[%");
    }
}
