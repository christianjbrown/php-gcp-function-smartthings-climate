# CLAUDE.md

Guidance for working in this repository. Match the existing conventions exactly — this codebase is
small, uniform, and highly opinionated, so new code should be indistinguishable from what's here. It
follows the same house style as the `christianjbrown/*` library packages it depends on.

## What this is

A private **Google Cloud Function** (PHP 8.2, runtime `php82`) that reads temperatures from
SmartThings devices and returns them as a JSON envelope. The entry point is the top-level
`index.php` `run(ServerRequestInterface): ResponseInterface`, which wires the pieces together and
delegates the HTTP envelope (header auth, CORS, cache-control, error handling) to
`ChristianBrown\CloudFunction\CloudFunction` from `php-cloud-function-lib`.

Request flow: `getenv()` → `ConfigTransformer` builds a `Config` (API token + `FunctionConfig`) →
`SmartThings` API client → `DataProvider::getData()` fetches devices, keeps those exposing a
`temperatureMeasurement` capability, reads each temperature/timestamp and flags stale readings
(> 24h) → `OutputTransformer` sorts by label, per-device shapes via
`DeviceTemperatureOutputTransformer`, and appends an average of non-stale readings.

## Commands

Binaries install into `bin/` (Composer `bin-dir`), not `vendor/bin/`. Both `bin/` and `vendor/` are
gitignored and Composer-installed, so run `composer install` first. The runtime and dev dependencies
are private `dev-main` GitHub packages, so Composer needs SSH access (or a `COMPOSER_AUTH` token) to
fetch them.

| Task | Command |
| --- | --- |
| Run tests + coverage (opens HTML report) | `composer test` |
| Run tests, no coverage | `php -d memory_limit=-1 ./bin/phpunit --no-coverage` |
| Run one test | `php -d memory_limit=-1 ./bin/phpunit --filter DataProviderTest` |
| Static analysis | `composer stan` |
| Check code style | `composer check-style` |
| Auto-fix code style | `composer fix-style` |
| Check / fix style on git diff only | `composer check-style-diff` / `composer fix-style-diff` |
| Run the function locally | `composer start` (serves `index.php`, exports `.local.env`) |

Under coverage, always run PHPUnit with `php -d memory_limit=-1` (path coverage on newer PHP exhausts
the default 128 MB limit). Style tooling comes from the `christianjbrown/php-code-quality-scripts`
dev dependency (php-cs-fixer + PHP_CodeSniffer, **Symfony2 coding standard**); the `bin/php-cs*`
scripts are thin wrappers over it. Static analysis is **PHPStan at `level: max`** (`phpstan.neon.dist`,
run with `composer stan`). CI (`.github/workflows/ci.yml`) runs style → PHPStan → PHPUnit with
coverage on every PR to `main`; `deploy.yml` deploys to Google Cloud Functions on push to `main`.
Before finishing, run `composer fix-style`, then `composer check-style`, then `composer stan`, then
`composer test`.

## Architecture

Everything lives directly under `src/` (no sub-namespaces). PSR-4: `ChristianBrown\GetSmartHomeTemps\`
→ `src/`, `ChristianBrown\GetSmartHomeTemps\Tests\` → `tests/` (the latter under `autoload-dev`).
Every concrete class is paired with a same-named `...Interface`:

- **`Config` / `ConfigInterface`** — holds the SmartThings API token and the `FunctionConfig`.
- **`ConfigTransformer` / `ConfigTransformerInterface`** — builds a `Config` from the environment
  array (validates `SMARTTHINGS_API_TOKEN`), delegating the rest to the lib's
  `FunctionConfigTransformer`.
- **`DataProvider` / `DataProviderInterface`** (extends the lib's `DataProviderInterface`) — the
  business logic: `getData()` walks devices/components/capabilities and returns the output array.
- **`DeviceTemperature` / `DeviceTemperatureInterface`** — an immutable value object for one reading
  (label, temperature, timestamp, stale flag).
- **`DeviceTemperatureOutputTransformer` / interface** — shapes one `DeviceTemperature` into its
  output map (`name`/`temp`/`timestamp`/`stale` keys).
- **`OutputTransformer` / interface** — sorts readings by label, aggregates the average, builds the
  top-level response map (`devices`/`averageTempDegrees`/`averageTempTimestamp`).

## Conventions (follow all of these)

- `declare(strict_types=1);` on every file (`src`, `tests`, and `index.php`), immediately after `<?php`.
- **Every concrete class is `final` and implements a matching `...Interface`** in the same namespace.
- **Constants live on the interface, not the class** — env keys, capability ids, thresholds, and
  output key names are all constants on the interfaces.
- **No constructor property promotion** — declare typed `private` properties and assign them in the
  constructor body. Fully typed params, returns, and properties throughout; no docblocks where the
  signature already says it.
- **Arrays crossing a public boundary carry a docblock** so PHPStan `level: max` is satisfied:
  `@param mixed[]` / `@return mixed[]` for arbitrary-shape payloads, or a specific element type
  (e.g. `@param DeviceTemperatureInterface[]`) where it is known. When a method has multiple params,
  the PEAR positional `@param` sniff requires **every** parameter documented in order, not just the
  array one.

## Testing

The `phpunit.xml` config is strict (`requireCoverageMetadata`, `beStrictAboutCoverageMetadata`,
`failOnRisky`, `failOnWarning`, `beStrictAboutOutputDuringTests`, path coverage). With that in mind:

- **Every test class needs a `#[CoversClass(...)]` attribute** (may list more than one) or the run
  fails. Use PHPUnit **attributes, not annotations**: `#[CoversClass]`, `#[TestWith]`, `#[DataProvider]`.
- One `final class XTest extends TestCase` per production class, methods named `test<Scenario>`.
- Mock every collaborator with `$this->createMock(SomeInterface::class)` (including the SmartThings
  and cloud-function library interfaces); assert statically (`self::assertSame`). Reference the same
  interface constants production code uses so no strings are hardcoded.
- Keep line and branch coverage at 100%; run `composer test` and check the report before finishing.
