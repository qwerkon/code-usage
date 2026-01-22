<?php

namespace Qwerkon\CodeUsage\Models;

use Illuminate\Database\Eloquent\Model;

class CodeUsageSymbol extends Model
{
    protected $table = 'code_usage_symbols';

    protected $fillable = [
        'symbol',
        'kind',
    ];

    public function hits()
    {
        return $this->hasMany(CodeUsageHit::class, 'symbol_id');
    }
}
