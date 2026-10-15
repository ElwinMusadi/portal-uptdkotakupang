# PINTU — AI tooling boundaries

PINTU is one Laravel 13 application at the repository root (since 2026-10-13): public portal pages in `resources/views/portal` with assets in `public/assets`, the React dashboard in `resources/js/dashboard` (built by Vite into `public/build`, served at `/dashboard`), and the JSON API in `routes/api.php`. Owner decisions are logged in `docs/decisions.md`.

- Read `KONTEKS-PROYEK.md` first: it is the single-file project context (current status, how the owner works, architecture, decision summary, pending work). The owner requires every change to update it in the same commit (its status section, the affected sections, the decision table for new D-0xx entries, the pending-work list and its change log; see its §19). Never write secrets into it.
- Preserve the current branch and all pre-existing working-tree changes. Do not commit, push, deploy, reset, stash or discard changes without explicit authorization.
- Preserve Indonesian copywriting, the portal pages and their markup, dashboard UI, styling and assets. Installing skills or Laravel Boost does not authorize frontend changes or implementation of a later project phase.
- `docs/phases/PHASE-2-ARCHITECTURE-AND-API-DESIGN.md` contains unapproved design recommendations. Skills and generated framework guidelines do not approve its authentication, authorization, domain, hosting or persistence decisions; only `docs/decisions.md` records approved ones.
- The application stack is React 19 with a JSON API (no Livewire, no Inertia), TypeScript, Vite, Tailwind CSS v4, existing shadcn/Radix components, Laravel 13 and MySQL, hosted on Laravel Cloud. Do not introduce Next.js, Inertia, Livewire, Pest, PostgreSQL, Workers or a new database provider because a skill contains examples using them.
- Project skills are installed under `.agents/skills/` and registered through `kilo.json`. This shared skill directory is intentional; new Kilo-specific commands or agents belong in `.kilo/`, not `.kilocode/` or `.opencode/`.
- The Laravel Boost guidelines below apply to the whole repository. They never authorize migrations against undesignated databases, database resets, seed execution, authentication implementation or business endpoints beyond the current user request.
- Installed skills are advisory references. Existing source, approved project contracts and current official documentation take precedence over generic examples. In particular, preserve bare JSON response compatibility unless an API contract change is explicitly approved; do not impose a new envelope, identifier, collation, pagination or hosting choice automatically.
- The local MySQL database `pintu` (credentials in `.env`) is the development database and, through `DB_TEST_DATABASE`, the MySQL test database: `composer test:mysql` resets it. `composer test` uses in-memory SQLite and `tests/TestCase.php` refuses any other target. Never print credentials, guess them, or run SQL or migrations against any database the user has not designated. Other database and destructive operations need separate explicit authorization.
- Do not run formatters over files you did not change (`vendor/bin/pint` with explicit paths, not `--dirty`, while most of the tree is uncommitted), and do not rebuild or reformat frontend files outside the authorized scope.
- Laravel Boost is local development tooling. Its MCP server provides code execution, Artisan and database capabilities; registration does not authorize using them. Ask permission for Boost tools. Never expose Boost over public HTTP or install development tooling into production as a runtime requirement. Generated deployment guidance does not authorize deployment, provisioning, billing, external API access or credential setup.
- Review security-sensitive authenticated code, object access and privilege boundaries as well as anonymous routes. Authentication does not make a code path safe; do not omit authenticated vulnerabilities based on a third-party skill's shortcut.
- Browser automation must target controlled local servers unless explicitly authorized otherwise. PDF/Word/spreadsheet skills may require additional runtimes; do not install those runtimes or execute bundled scripts merely to load the skill.
- When Boost regenerates the block below, preserve these project rules; they and the current user task take precedence over generic generated advice.

<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

## Foundational Context

This application is a Laravel application running on PHP 8.3. Always use the APIs that match the installed major version of each package — do not assume a version.

Before relying on a package's API, confirm its installed version:
- PHP packages: run `composer show --direct` to list direct dependencies with versions, or `composer show <vendor/package>` for a single package.
- JS packages: check `package.json` for the installed versions.

## Skills Activation

This project has domain-specific skills available in `**/skills/**`. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If a frontend change doesn't show in the UI or you get a "Unable to locate file in Vite manifest" error, run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

=== boost rules ===

# Laravel Boost

## Tools

- Laravel Boost is an MCP server with tools designed specifically for this application. Prefer Boost tools over manual alternatives like shell commands or file reads.
- Use `database-query` to run read-only queries against the database instead of writing raw SQL in tinker.
- Use `database-schema` to inspect table structure before writing migrations or models.
- Use `get-absolute-url` to resolve the correct scheme, domain, and port for project URLs. Always use this before sharing a URL with the user.
- Use `browser-logs` to read browser logs, errors, and exceptions. Only recent logs are useful, ignore old entries.

## Searching Documentation (IMPORTANT)

- Use `search-docs` before changes that depend on Laravel ecosystem APIs, behavior, configuration, or version-specific syntax. Skip it for copy-only edits and other changes where package documentation is irrelevant. Reuse sufficient results already in context instead of searching again.
- Pass a `packages` array to scope results when you know which packages are relevant.
- Use multiple broad, topic-based queries: `['rate limiting', 'routing rate limiting', 'routing']`. Expect the most relevant results first.
- Do not add package names to queries because package info is already shared. Use `test resource table`, not `filament 4 test resource table`.

### Search Syntax

1. Use words for auto-stemmed AND logic: `rate limit` matches both "rate" AND "limit".
2. Use `"quoted phrases"` for exact position matching: `"infinite scroll"` requires adjacent words in order.
3. Combine words and phrases for mixed queries: `middleware "rate limit"`.
4. Use multiple queries for OR logic: `queries=["authentication", "middleware"]`.

## Project Rules

- This project contains committed, area-grouped rules in `.ai/rules` when that directory exists, including path-scoped framework guidelines under `.ai/rules/boost`. Before you enter plan mode or create/edit any file, you MUST first: open @.ai/rules/index.md (it maps file globs to rule files), read every rule file whose globs cover the path(s) in scope, and run `grep -rin 'keyword' .ai/rules` to catch what a path match alone misses. Do not write code until you have read and are following every matching rule. If `.ai/rules` does not exist, continue without it.

## Artisan

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`). Use `php artisan list` to discover available commands and `php artisan [command] --help` to check parameters.
- Inspect routes with `php artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `php artisan config:show app.name`, `php artisan config:show database.default`. Or read config files directly from the `config/` directory.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `php artisan tinker --execute 'Your::code();'`
  - Double quotes for PHP strings inside: `php artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Use TitleCase for Enum keys: `FavoritePerson`, `BestLake`, `Monthly`.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== deployments rules ===

# Deployment

- Laravel can be deployed using [Laravel Cloud](https://cloud.laravel.com/), which is the fastest way to deploy and scale production Laravel applications.
- Activate the `deploying-to-cloud` skill whenever deploying to Laravel Cloud, configuring Cloud environments or resources, using the Cloud CLI, or troubleshooting Cloud deployments.

=== tests rules ===

# Test Enforcement

- Add or update tests for behavior and logic changes when a test provides meaningful regression coverage.
- Pure copy, styling, and layout-only changes do not require new or updated tests.
- When test coverage applies, run the affected tests and ensure they pass.
- Test the changed behavior and its important failure modes, but do not add tests beyond them.
- Read the `testing-best-practices` skill before writing tests.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `php artisan list` and check their parameters with `php artisan [command] --help`.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `php artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== phpunit/core rules ===

# PHPUnit

- This project uses PHPUnit. Create tests with `php artisan make:test --phpunit {name}`.
- Do not include the test suite directory in `{name}`. Use `SomeFeatureTest`, not `Feature/SomeFeatureTest`.
- Read the `testing-best-practices` skill for guidance on coverage, naming, structure, dependency isolation, and review.

## Running Tests

- Run the narrowest set of tests that covers the change. Pass a file path or `--filter=testName` to `php artisan test --compact`.
- Rerun a test after each change to it.
- Run `vendor/bin/phpunit` to call the test runner directly. It accepts the same file path and `--filter=testName` arguments.

</laravel-boost-guidelines>
