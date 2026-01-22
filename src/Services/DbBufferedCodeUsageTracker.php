<?php

namespace Qwerkon\CodeUsage\Services;

use Qwerkon\CodeUsage\Contracts\CodeUsageTracker;
use Qwerkon\CodeUsage\Jobs\FlushCodeUsageBatchJob;
use Qwerkon\CodeUsage\Tracker\CodeUsageRecorder;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Psr\Log\LoggerInterface;
use Illuminate\Support\Str;
use Throwable;

class DbBufferedCodeUsageTracker implements CodeUsageTracker
{
    protected array $buffer = [];

    public function __construct(
        protected CacheRepository $cache,
        protected LoggerInterface $logger,
        protected array $config
    ) {
    }

    public function track(string $symbol, string $kind, array $meta = []): void
    {
        if (empty($this->config['enabled'])) {
            return;
        }

        if (! $this->passesNamespaceFilters($symbol)) {
            return;
        }

        if (! $this->shouldSample($symbol)) {
            return;
        }

        if ($this->shouldThrottle($symbol)) {
            return;
        }

        $day = now()->toDateString();
        $metaHash = $this->metaHash($meta);
        $metaPayload = $this->metaPayload($meta, $metaHash);
        $key = implode('|', [$symbol, $kind, $day, $metaHash ?: '']);

        $entry = &$this->buffer[$key];

        if (! isset($entry)) {
            $entry = [
                'symbol' => $symbol,
                'kind' => $kind,
                'day' => $day,
                'count' => 0,
                'first_seen_at' => now()->toDateTimeString(),
                'last_seen_at' => now()->toDateTimeString(),
                'meta_hash' => $metaHash,
                'meta_payload' => $metaPayload,
            ];
        }

        $entry['count']++;
        $entry['last_seen_at'] = now()->toDateTimeString();
    }

    public function flush(): void
    {
        if (empty($this->buffer)) {
            return;
        }

        $payload = array_values($this->buffer);
        $this->buffer = [];

        try {
            FlushCodeUsageBatchJob::dispatch($payload)
                ->onConnection($this->config['queue_connection'] ?? 'sync')
                ->onQueue($this->config['queue_name'] ?? 'default');
        } catch (Throwable $throwable) {
            $this->logger->warning('Code usage flush job failed, falling back to sync persistence', [
                'exception' => $throwable,
            ]);
            $this->persistRecordsSync($payload);
        }
    }

    protected function persistRecordsSync(array $payload): void
    {
        try {
            CodeUsageRecorder::persistRecords($payload);
        } catch (Throwable $throwable) {
            $this->logger->error('Failed to persist code usage data', ['exception' => $throwable]);
        }
    }

    protected function shouldSample(string $symbol): bool
    {
        if (($this->config['sample_rate'] ?? 1) >= 1) {
            return true;
        }

        $whitelist = $this->config['sampling_whitelist'] ?? [];
        foreach ($whitelist as $pattern) {
            if (Str::is($pattern, $symbol)) {
                return true;
            }
        }

        return (mt_rand() / mt_getrandmax()) < ($this->config['sample_rate'] ?? 0);
    }

    protected function passesNamespaceFilters(string $symbol): bool
    {
        foreach ($this->normalizeNamespaces($this->config['exclude_namespaces'] ?? []) as $namespace) {
            if ($namespace === '') {
                continue;
            }

            if ($this->symbolStartsWith($symbol, $namespace)) {
                return false;
            }
        }

        $includes = $this->normalizeNamespaces($this->config['include_namespaces'] ?? []);
        if (empty($includes)) {
            return true;
        }

        foreach ($includes as $namespace) {
            if ($namespace === '' || $this->symbolStartsWith($symbol, $namespace)) {
                return true;
            }
        }

        return false;
    }

    protected function normalizeNamespaces(array $namespaces): array
    {
        return array_filter(array_map(function ($namespace) {
            if (! is_string($namespace)) {
                return '';
            }

            return trim(str_replace('\\\\', '\\', $namespace), '\\');
        }, $namespaces));
    }

    protected function symbolStartsWith(string $symbol, string $namespace): bool
    {
        return Str::startsWith($symbol, $namespace . '\\') || $symbol === $namespace;
    }

    protected function shouldThrottle(string $symbol): bool
    {
        $limit = max(1, (int) ($this->config['throttle_per_minute'] ?? 1));
        $window = now()->startOfMinute();
        $cacheKey = sprintf('code-usage-throttle:%s:%s', $symbol, $window->format('YmdHi'));
        $current = (int) $this->cache->get($cacheKey, 0);

        if ($current >= $limit) {
            return true;
        }

        $expiresAt = $window->copy()->addMinute();
        $this->cache->put($cacheKey, $current + 1, $expiresAt);

        return false;
    }

    protected function metaHash(array $meta): ?string
    {
        if (empty($meta) || empty($this->config['meta_enabled'])) {
            return null;
        }

        $payload = $this->sanitizeMeta($meta);

        if (empty($payload)) {
            return null;
        }

        ksort($payload);

        return sha1(json_encode($payload, JSON_UNESCAPED_SLASHES));
    }

    protected function metaPayload(array $meta, ?string $hash): ?array
    {
        if (! $hash || empty($this->config['meta_enabled'])) {
            return null;
        }

        return $this->sanitizeMeta($meta);
    }

    protected function sanitizeMeta(array $meta): array
    {
        $allowed = $this->config['meta_keys'] ?? [];
        $sanitized = [];

        foreach ($allowed as $key => $target) {
            if (! isset($meta[$key])) {
                continue;
            }

            $value = $meta[$key];

            if (is_scalar($value)) {
                $sanitized[$target] = (string) $value;
            }
        }

        return $sanitized;
    }
}
