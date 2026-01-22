<?php

namespace Qwerkon\CodeUsage\Contracts;

interface CodeUsageTracker
{
    public function track(string $symbol, string $kind, array $meta = []): void;

    public function flush(): void;
}
