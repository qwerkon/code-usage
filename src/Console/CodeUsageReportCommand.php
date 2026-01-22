<?php

namespace Qwerkon\CodeUsage\Console;

use Qwerkon\CodeUsage\Models\CodeUsageHit;
use Qwerkon\CodeUsage\Models\CodeUsageSymbol;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CodeUsageReportCommand extends Command
{
    protected $signature = 'code-usage:report {--top=10 : Number of symbols to display}';

    protected $description = 'Summarize tracked code usage';

    public function handle(): int
    {
        $top = max(1, (int) $this->option('top'));

        $recent = CodeUsageHit::with('symbol')
            ->orderBy('hits', 'desc')
            ->limit($top)
            ->get()
            ->map(function (CodeUsageHit $hit) {
                return [
                    'symbol' => $hit->symbol->symbol ?? 'unknown',
                    'kind' => $hit->symbol->kind ?? 'other',
                    'hits' => $hit->hits,
                    'day' => $hit->day?->format('Y-m-d'),
                    'last_seen' => $hit->last_seen_at?->toDateTimeString(),
                ];
            });

        $this->info('Top tracked symbols:');
        if ($recent->isNotEmpty()) {
            $this->table(
                ['Symbol', 'Kind', 'Hits', 'Day', 'Last Seen'],
                $recent->toArray()
            );
        } else {
            $this->line('No records yet.');
        }

        $neverSeen = CodeUsageSymbol::whereDoesntHave('hits')->count();
        $this->line("Symbols never hit: {$neverSeen}");

        return 0;
    }
}
