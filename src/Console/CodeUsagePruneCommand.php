<?php

namespace Qwerkon\CodeUsage\Console;

use Qwerkon\CodeUsage\Models\CodeUsageHit;
use Qwerkon\CodeUsage\Models\CodeUsageMeta;
use Illuminate\Console\Command;

class CodeUsagePruneCommand extends Command
{
    protected $signature = 'code-usage:prune {--days= : Keep hits newer than this many days}';

    protected $description = 'Prune old code usage records';

    public function handle(): int
    {
        $days = (int) ($this->option('days') ?? config('code_usage.retention_days'));
        $threshold = now()->subDays($days)->toDateString();

        $count = CodeUsageHit::where('day', '<', $threshold)->delete();

        CodeUsageMeta::whereNotIn('meta_hash', function ($query) {
            $query->select('meta_hash')
                ->from('code_usage_hits')
                ->whereNotNull('meta_hash');
        })->delete();

        $this->info("Pruned {$count} hits older than {$days} days.");

        return 0;
    }
}
