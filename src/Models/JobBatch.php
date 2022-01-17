<?php

namespace Agatanga\Relay\Models;

use Illuminate\Database\Eloquent\Model;

class JobBatch extends Model
{
    public function __construct()
    {
        parent::__construct();
        $this->table = config('queue.batching.table') ?? 'job_batches';
    }

    public function whereMeta($key, $value)
    {
        return $this->where('name', 'like', "%[{$key}:{$value}]%");
    }
}
