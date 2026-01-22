<?php

namespace Qwerkon\CodeUsage\Jobs;

use Qwerkon\CodeUsage\Tracker\CodeUsageRecorder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class FlushCodeUsageBatchJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public array $records = [])
    {
    }

    public function handle(): void
    {
        CodeUsageRecorder::persistRecords($this->records);
    }
}
