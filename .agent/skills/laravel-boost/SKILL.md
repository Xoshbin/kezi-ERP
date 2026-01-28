---
name: laravel-boost
description: Guidelines for Laravel Boost, Filament v4, and general Laravel best practices. Use when working on Laravel features.
---

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to enhance the user's satisfaction building Laravel applications.

## Foundational Context

This application is a Laravel application and its main Laravel ecosystems package & versions are below. You are an expert with them all. Ensure you abide by these specific packages & versions.

-   php - 8.4+
-   filament/filament (FILAMENT) - v4
-   laravel/framework (LARAVEL) - v12
-   livewire/livewire (LIVEWIRE) - v3
-   larastan/larastan (LARASTAN) - v3
-   laravel/pint (PINT) - v1
-   pestphp/pest (PEST) - v4
-   nwidart/laravel-modules - v11 (modular architecture)
-   bezhansalleh/filament-shield - v4 (RBAC)
-   brick/money - v0.10.1 (monetary precision)

## Conventions

-   You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, naming.
-   Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
-   Check for existing components to reuse before writing a new one.

## Verification Scripts

-   Do not create verification scripts or tinker when tests cover that functionality and prove it works. Unit and feature tests are more important.

## Modular Testing

-   Tests are located in both root `tests/` and `Modules/{Module}/tests/`.
-   Run all tests: `php artisan test --parallel`
-   Run module-specific tests: `php artisan test Modules/Accounting/tests/`
-   Always run `./vendor/bin/phpstan analyse` after tests.

## Application Structure & Architecture

-   Stick to existing directory structure - don't create new base folders without approval.
-   Do not change the application's dependencies without approval.

## Frontend Bundling

-   If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `npm run build`, `npm run dev`, or `composer run dev`. Ask them.

## Replies

-   Be concise in your explanations - focus on what's important rather than explaining obvious details.

## Documentation Files

-   You must only create documentation files if explicitly requested by the user.

=== boost rules ===

## Laravel Boost

-   Laravel Boost is an MCP server that comes with powerful tools designed specifically for this application. Use them.

## Artisan

-   Use the `list-artisan-commands` tool when you need to call an Artisan command to double check the available parameters.

## URLs

-   Whenever you share a project URL with the user you should use the `get-absolute-url` tool to ensure you're using the correct scheme, domain / IP, and port.

## Tinker / Debugging

-   You should use the `tinker` tool when you need to execute PHP to debug code or query Eloquent models directly.
-   Use the `database-query` tool when you only need to read from the database.

## Reading Browser Logs With the `browser-logs` Tool

-   You can read browser logs, errors, and exceptions using the `browser-logs` tool from Boost.
-   Only recent browser logs will be useful - ignore old logs.

## Searching Documentation (Critically Important)

-   Boost comes with a powerful `search-docs` tool you should use before any other approaches. This tool automatically passes a list of installed packages and their versions to the remote Boost API, so it returns only version-specific documentation specific for the user's circumstance. You should pass an array of packages to filter on if you know you need docs for particular packages.
-   The 'search-docs' tool is perfect for all Laravel related packages, including Laravel, Inertia, Livewire, Filament, Tailwind, Pest, Nova, Nightwatch, etc.
-   You must use this tool to search for Laravel-ecosystem documentation before falling back to other approaches.
-   Search the documentation before making code changes to ensure we are taking the correct approach.
-   Use multiple, broad, simple, topic based queries to start. For example: `['rate limiting', 'routing rate limiting', 'routing']`.
-   Do not add package names to queries - package information is already shared. For example, use `test resource table`, not `filament 4 test resource table`.

### Available Search Syntax

-   You can and should pass multiple queries at once. The most relevant results will be returned first.

1. Simple Word Searches with auto-stemming - query=authentication - finds 'authenticate' and 'auth'
2. Multiple Words (AND Logic) - query=rate limit - finds knowledge containing both "rate" AND "limit"
3. Quoted Phrases (Exact Position) - query="infinite scroll" - Words must be adjacent and in that order
4. Mixed Queries - query=middleware "rate limit" - "middleware" AND exact phrase "rate limit"
5. Multiple Queries - queries=["authentication", "middleware"] - ANY of these terms

=== php rules ===

## PHP

-   Always use curly braces for control structures, even if it has one line.

### Constructors

-   Use PHP 8 constructor property promotion in `__construct()`.
    -   <code-snippet>public function \_\_construct(public GitHub $github) { }</code-snippet>
-   Do not allow empty `__construct()` methods with zero parameters.

### Type Declarations

-   Always use explicit return type declarations for methods and functions.
-   Use appropriate PHP type hints for method parameters.

<code-snippet name="Explicit Return Types and Method Params" lang="php">
protected function isAccessible(User $user, ?string $path = null): bool
{
    ...
}
</code-snippet>

## Comments

-   Prefer PHPDoc blocks over comments. Never use comments within the code itself unless there is something _very_ complex going on.

## PHPDoc Blocks

-   Add useful array shape type definitions for arrays when appropriate.

## Enums

-   Typically, keys in an Enum should be TitleCase. For example: `FavoritePerson`, `BestLake`, `Monthly`.

=== filament/core rules ===

## Filament

-   Filament is used by this application, check how and where to follow existing application conventions.
-   Filament is a Server-Driven UI (SDUI) framework for Laravel. It allows developers to define user interfaces in PHP using structured configuration objects. It is built on top of Livewire, Alpine.js, and Tailwind CSS.
-   You can use the `search-docs` tool to get information from the official Filament documentation when needed. This is very useful for Artisan command arguments, specific code examples, testing functionality, relationship management, and ensuring you're following idiomatic practices.
-   Utilize static `make()` methods for consistent component initialization.

### Artisan

-   You must use the Filament specific Artisan commands to create new files or components for Filament. You can find these with the `list-artisan-commands` tool, or with `php artisan` and the `--help` option.
-   Inspect the required options, always pass `--no-interaction`, and valid arguments for other options when applicable.

### Filament's Core Features

-   Actions: Handle doing something within the application, often with a button or link. Actions encapsulate the UI, the interactive modal window, and the logic that should be executed when the modal window is submitted. They can be used anywhere in the UI and are commonly used to perform one-time actions like deleting a record, sending an email, or updating data in the database based on modal form input.
-   Forms: Dynamic forms rendered within other features, such as resources, action modals, table filters, and more.
-   Infolists: Read-only lists of data.
-   Notifications: Flash notifications displayed to users within the application.
-   Panels: The top-level container in Filament that can include all other features like pages, resources, forms, tables, notifications, actions, infolists, and widgets.
-   Resources: Static classes that are used to build CRUD interfaces for Eloquent models. Typically live in `app/Filament/Resources`.
-   Schemas: Represent components that define the structure and behavior of the UI, such as forms, tables, or lists.
-   Tables: Interactive tables with filtering, sorting, pagination, and more.
-   Widgets: Small component included within dashboards, often used for displaying data in charts, tables, or as a stat.

### Relationships

-   Determine if you can use the `relationship()` method on form components when you need `options` for a select, checkbox, repeater, or when building a `Fieldset`:

<code-snippet name="Relationship example for Form Select" lang="php">
Forms\Components\Select::make('user_id')
    ->label('Author')
    ->relationship('author')
    ->required(),
</code-snippet>

## Testing

-   It's important to test Filament functionality for user satisfaction.
-   Ensure that you are authenticated to access the application within the test.
-   Filament uses Livewire, so start assertions with `livewire()` or `Livewire::test()`.

### Example Tests

<code-snippet name="Filament Table Test" lang="php">
    livewire(ListUsers::class)
        ->assertCanSeeTableRecords($users)
        ->searchTable($users->first()->name)
        ->assertCanSeeTableRecords($users->take(1))
        ->assertCanNotSeeTableRecords($users->skip(1))
        ->searchTable($users->last()->email)
        ->assertCanSeeTableRecords($users->take(-1))
        ->assertCanNotSeeTableRecords($users->take($users->count() - 1));
</code-snippet>

<code-snippet name="Filament Create Resource Test" lang="php">
    livewire(CreateUser::class)
        ->fillForm([
            'name' => 'Howdy',
            'email' => 'howdy@example.com',
        ])
        ->call('create')
        ->assertNotified()
        ->assertRedirect();

    assertDatabaseHas(User::class, [
        'name' => 'Howdy',
        'email' => 'howdy@example.com',
    ]);

</code-snippet>

<code-snippet name="Testing Multiple Panels (setup())" lang="php">
    use Filament\Facades\Filament;

    Filament::setCurrentPanel('app');

</code-snippet>

<code-snippet name="Calling an Action in a Test" lang="php">
    livewire(EditInvoice::class, [
        'invoice' => $invoice,
    ])->callAction('send');

    expect($invoice->refresh())->isSent()->toBeTrue();

</code-snippet>

=== filament/v4 rules ===

## Filament 4

### Important Version 4 Changes

-   File visibility is now `private` by default.
-   The `deferFilters` method from Filament v3 is now the default behavior in Filament v4, so users must click a button before the filters are applied to the table. To disable this behavior, you can use the `deferFilters(false)` method.
-   The `Grid`, `Section`, and `Fieldset` layout components no longer span all columns by default.
-   The `all` pagination page method is not available for tables by default.
-   All action classes extend `Filament\Actions\Action`. No action classes exist in `Filament\Tables\Actions`.
-   The `Form` & `Infolist` layout components have been moved to `Filament\Schemas\Components`, for example `Grid`, `Section`, `Fieldset`, `Tabs`, `Wizard`, etc.
-   A new `Repeater` component for Forms has been added.
-   Icons now use the `Filament\Support\Icons\Heroicon` Enum by default. Other options are available and documented.

### Organize Component Classes Structure

-   Schema components: `Schemas/Components/`
-   Table columns: `Tables/Columns/`
-   Table filters: `Tables/Filters/`
-   Actions: `Actions/`

### UI Patterns
- **Complex Repeaters:** For repeaters with many fields (causing overflow), use `extraItemActions()` with `slideOver()` actions to move secondary fields into a drawer. See `jmeryar-coding-style` for implementation details.

=== laravel/core rules ===

## Do Things the Laravel Way

-   Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using the `list-artisan-commands` tool.
-   If you're creating a generic PHP class, use `artisan make:class`.
-   Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Database

-   Always use proper Eloquent relationship methods with return type hints. Prefer relationship methods over raw queries or manual joins.
-   Use Eloquent models and relationships before suggesting raw database queries
-   Avoid `DB::`; prefer `Model::query()`. Generate code that leverages Laravel's ORM capabilities rather than bypassing the
