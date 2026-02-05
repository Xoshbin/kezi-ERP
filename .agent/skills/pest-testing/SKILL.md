---
name: pest-testing
description: >-
  Tests applications using the Pest 4 PHP framework. Activates when writing tests, creating unit or feature
  tests, adding assertions, testing Livewire components, browser testing, debugging test failures,
  working with datasets or mocking; or when the user mentions test, spec, TDD, expects, assertion,
  coverage, or needs to verify functionality works.
---

# Pest Testing 4

### 🧪 **Pest Testing Core**

<pest>
- **Organization:** Unit/Feature in `tests/` and `packages/kezi/{module}/tests/`.
- **Command:** `php artisan test --compact --parallel` (MANDATORY after every implementation/fix).
- **Assertions:** Use `assertSuccessful()`, `assertNotFound()`, etc. instead of raw status codes.
</pest>

### 🌐 **Browser Testing**

<browser>
- **Location:** `tests/Browser/`.
- **Patterns:** Use `visit()`, `click()`, `fill()`, and `assertNoJavaScriptErrors()`.
- **Visuals:** Visual regression testing with snapshots if requested.
</browser>

### 🏗️ **Architecture Testing**

<arch>
Use `arch()` to enforce coding standards across specific namespaces.
</arch>