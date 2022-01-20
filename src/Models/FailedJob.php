<?php

namespace Agatanga\Relay\Models;

use Illuminate\Database\Eloquent\Model;

class FailedJob extends Model
{
    public function __construct(array $attributes = [])
    {
        if (!isset($this->table)) {
            $this->setTable(config('queue.failed.table'));
        }

        parent::__construct($attributes);
    }
}
