---
name: laravel-boost
description: Guidelines for Laravel Boost, Filament v4, and general Laravel best practices. Use when working on Laravel features.
---

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to enhance the user's satisfaction building Laravel applications.

### 🚀 **Laravel Boost Core**

<boost>
- **Foundation:** PHP 8.4+, Laravel 12, Filament 4, Livewire 3, Pest 4.
- **Convention:** Descriptive naming, component reuse, and strict modular structure.
- **Tooling:** Use `search-docs`, `tinker`, `database-query`, and `browser-logs` from Boost MCP.
</boost>

### 🛠️ **Development Standards**

<standards>
#### PHP Rules
- Strict typing (return types and params).
- Constructor property promotion.
- PHPDoc for array shapes.

#### Filament Patterns
- Use static `make()` methods.
- Delegate logic to Services/Actions.
- Use `relationship()` for form components.
</standards>

### 🧪 **Verification & Testing**

<verification>
- **Zero-Tinker:** Prefer Pest tests over manual tinkering.
- **Full Verification:** Run `php artisan test --parallel` and `./vendor/bin/phpstan analyse` before completing any task.
- **Livewire Assertions:** All UI tests start with `livewire(Component::class)`.
</verification>
