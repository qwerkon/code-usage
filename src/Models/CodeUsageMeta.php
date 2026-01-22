<?php

namespace Qwerkon\CodeUsage\Models;

use Illuminate\Database\Eloquent\Model;

class CodeUsageMeta extends Model
{
    protected $table = 'code_usage_meta';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'meta_hash',
        'payload_json',
    ];

    protected $casts = [
        'payload_json' => 'array',
    ];
}
