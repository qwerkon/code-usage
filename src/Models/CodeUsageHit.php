<?php

namespace Qwerkon\CodeUsage\Models;

use Illuminate\Database\Eloquent\Model;

class CodeUsageHit extends Model
{
    protected $table = 'code_usage_hits';

    protected $fillable = [
        'symbol_id',
        'day',
        'hits',
        'first_seen_at',
        'last_seen_at',
        'meta_hash',
    ];

    protected $casts = [
        'day' => 'date:Y-m-d',
        'first_seen_at' => 'datetime',
        'last_seen_at' => 'datetime',
    ];

    public function symbol()
    {
        return $this->belongsTo(CodeUsageSymbol::class, 'symbol_id');
    }
}
