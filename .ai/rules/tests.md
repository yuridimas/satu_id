---
paths:
  - 'tests/**'
---

# Tests

## Enable audit.console for tests
OwenIt Auditing skips ALL model-driven audits when App::runningInConsole() unless config('audit.console') is true. Feature tests run via pest/phpunit CLI, so `created`/`updated`/`deleted` audit rows never appear. tests/TestCase.php sets `config('audit.console', true)` in setUp; do not remove it. Custom AuditLogger::record always writes because it bypasses the observer.
