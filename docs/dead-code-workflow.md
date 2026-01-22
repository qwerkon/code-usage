# Dead Code Workflow

1. **Static analysis** – run `composer analyse` to execute PHPStan/Larastan against `app/` and the new `CodeUsage` package.
2. **Coverage telemetry** – `composer test:coverage` launches PHPUnit with PCOV (fallback to Xdebug) and outputs `public/coverage` + `public/coverage/converage.xml`. Inspect those reports to confirm what code has been exercised.
3. **Runtime telemetry** – enable by setting `CODE_USAGE_ENABLED=true` in `.env` (and optionally adjust `CODE_USAGE_SAMPLE_RATE`, `CODE_USAGE_THROTTLE_PER_MINUTE`, `CODE_USAGE_INCLUDE_NAMESPACES`, `CODE_USAGE_EXCLUDE_NAMESPACES`, `CODE_USAGE_RETENTION_DAYS`). The telemetry service buffers hits in memory and flushes via `code-usage:flush` job/ command.
4. **Database tables** – migrations live alongside the CodeUsage package and must run (`php artisan migrate`) before telemetry data can be recorded. The schema is intentionally minimal and keeps only symbol metadata plus hit counts per day.
5. **Interpretation**:
   * Allow telemetry to run for 30–90 days (move the `CODE_USAGE_RETENTION_DAYS` window) before pruning anything.
   * Symbols that never appear in `code_usage_hits` are safe to review for removal, but verify they are not triggered via reflection/config-driven strings. Use the `code-usage:report` command to inspect top hitters and last seen timestamps.
   * The `code-usage:prune` command removes old hits (optionally passing `--days`) and ancillary metadata.
6. **Checklists before removal**:
   * Routes/Controllers – check `routes/*.php` and `App\Http\Controllers`.
   * Events/Listeners – audit `App\Events`/`App\Listeners` plus wildcard events.
   * Jobs/Commands – ensure queue/console entries are no longer scheduled/called.
   * Policies/Observers – confirm nothing binds these classes anywhere (Route model binding, IoC container, service providers).
   * Config-driven strings – search in `config/`, `pipelines`, `services`, etc., for class names referenced as strings (`ClassName::class` expected).
   * Scheduled tasks – inspect any scheduler definitions in `App\Console\Kernel`.
7. **Removal process**:
   * Mark the code as deprecated (comment + doc) and monitor telemetry for 30–90 days.
   * Run `code-usage:report` and inspect logs/transmitted data for hits.
   * Once confident, remove/replace the unused code path with a documented commit and update telemetry configuration if necessary.

The telemetry system never stores user data; only sanitized meta (route names, queue names, command signatures) is persisted. All telemetry writes happen asynchronously through the queue and are best-effort.
