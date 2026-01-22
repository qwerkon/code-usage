<?php

namespace Qwerkon\CodeUsage\Tracker;

use Carbon\Carbon;
use Qwerkon\CodeUsage\Models\CodeUsageHit;
use Qwerkon\CodeUsage\Models\CodeUsageMeta;
use Qwerkon\CodeUsage\Models\CodeUsageSymbol;
use Illuminate\Support\Facades\DB;

class CodeUsageRecorder
{
    public static function persistRecords(array $records): void
    {
        foreach ($records as $record) {
            DB::transaction(fn () => self::persistOne($record));
        }
    }

    protected static function persistOne(array $record): void
    {
        $symbol = CodeUsageSymbol::firstOrCreate(
            ['symbol' => $record['symbol']],
            ['kind' => $record['kind']]
        );

        if (!empty($record['meta_hash']) && !empty($record['meta_payload'])) {
            CodeUsageMeta::updateOrCreate(
                ['meta_hash' => $record['meta_hash']],
                ['payload_json' => $record['meta_payload']]
            );
        }

        $hit = CodeUsageHit::firstOrNew(['symbol_id' => $symbol->id, 'day' => $record['day']]);

        $hit->hits = ($hit->exists ? $hit->hits : 0) + $record['count'];
        $hit->first_seen_at = self::earlier($hit->first_seen_at, $record['first_seen_at']);
        $hit->last_seen_at = self::later($hit->last_seen_at, $record['last_seen_at']);
        $hit->meta_hash = $record['meta_hash'];
        $hit->save();
    }

    protected static function earlier(?string $current, ?string $candidate): ?Carbon
    {
        $currentDate = $current ? Carbon::parse($current) : null;
        $candidateDate = $candidate ? Carbon::parse($candidate) : null;

        if (! $currentDate) {
            return $candidateDate;
        }

        if (! $candidateDate) {
            return $currentDate;
        }

        return $currentDate->lt($candidateDate) ? $currentDate : $candidateDate;
    }

    protected static function later(?string $current, ?string $candidate): ?Carbon
    {
        $currentDate = $current ? Carbon::parse($current) : null;
        $candidateDate = $candidate ? Carbon::parse($candidate) : null;

        if (! $currentDate) {
            return $candidateDate;
        }

        if (! $candidateDate) {
            return $currentDate;
        }

        return $currentDate->gt($candidateDate) ? $currentDate : $candidateDate;
    }
}
