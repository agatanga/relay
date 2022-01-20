<?php

namespace Agatanga\Relay\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class JobBatch extends Model
{
    protected $meta;

    protected $casts = [
        'failed_job_ids' => 'array',
    ];

    public function __construct(array $attributes = [])
    {
        if (!isset($this->table)) {
            $this->setTable(config('queue.batching.table') ?? 'job_batches');
        }

        parent::__construct($attributes);
    }

    public function meta($key)
    {
        if (is_null($this->meta)) {
            preg_match_all('/\[(.+?):(.+?)\]/', $this->attributes['name'] ?? '', $matches);

            $this->meta = [];

            if ($matches[1]) {
                foreach ($matches[1] as $i => $key) {
                    $this->meta[$key] = $matches[2][$i];
                }
            }
        }

        return Arr::get($this->meta, $key);
    }

    public function failedJobs()
    {
        return app(FailedJob::class)->whereIn('uuid', $this->failed_job_ids);
    }

    public function getProgressAttribute()
    {
        $progress = $this->total_jobs > 0 ?
            round(($this->processed_jobs / $this->total_jobs) * 100) :
            0;

        list($min, $max) = array_values($this->range);

        return round($min + ($progress * ($max - $min) / 100));
    }

    public function getProcessedJobsAttribute()
    {
        return $this->total_jobs - $this->pending_jobs;
    }

    public function getFailedAttribute()
    {
        return $this->failed_jobs > 0;
    }

    public function getExceptionAttribute()
    {
        return $this->failedJobs()->latest('failed_at')->first()->exception;
    }

    public function getFinishedAttribute()
    {
        return !is_null($this->finished_at) && $this->range['max'] === 100;
    }

    public function getRunningAttribute()
    {
        return !$this->failed_jobs && !$this->finished;
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

    public function scopeWhereMeta(Builder $query, $key, $value = null): Builder
    {
        $conditions = [];

        if (is_null($value)) {
            $conditions[] = $key;
            $value = [];
        } elseif (!is_iterable($value)) {
            $value = [$value];
        }

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
