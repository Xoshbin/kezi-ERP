# Contributing to Kezi

First off, thanks for taking the time to contribute!

All types of contributions are encouraged and valued. See the Table of Contents for different ways to help and details about how this project handles them. Please make sure to read the relevant section before making your contribution. It will make it a lot easier for us maintainers and smooth out the experience for all involved.

## Table of Contents

- [Code of Conduct](#code-of-conduct)
- [How to Contribute](#how-to-contribute)
  - [Reporting Bugs](#reporting-bugs)
  - [Suggesting Enhancements](#suggesting-enhancements)
  - [Translations](#translations)
  - [Documentation and User Guides](#documentation-and-user-guides)
  - [Your First Code Contribution](#your-first-code-contribution)
  - [Pull Request Process](#pull-request-process)
- [Development Guidelines](#development-guidelines)
  - [Coding Standards](#coding-standards)
  - [Testing](#testing)

## Code of Conduct

This project and everyone participating in it is governed by a Code of Conduct. By participating, you are expected to uphold this code.

## How to Contribute

### Reporting Bugs

Before creating bug reports, please check the existing issues as you might find out that you don't need to create one. When you are creating a bug report, please include as many details as possible:
* Use a clear and descriptive title for the issue to identify the problem.
* Describe the exact steps which reproduce the problem in as many details as possible.
* Provide specific examples to demonstrate the steps. Include links to files or GitHub projects, or copy/pasteable snippets, which you use in those examples.

### Suggesting Enhancements

Enhancement suggestions are tracked as GitHub issues. When you are creating an enhancement suggestion, please include:
* Use a clear and descriptive title for the issue to identify the suggestion.
* Provide a step-by-step description of the suggested enhancement in as many details as possible.
* Explain why this enhancement would be useful to most users.

### Translations

We welcome translations for Kezi to support users globally! We use [Spatie Translatable](https://spatie.be/docs/laravel-translatable/v6/introduction) for handling translations efficiently.

If you'd like to translate the UI or documentation:
1. Ensure the target language code is added to `config/app.php` locales if it doesn't exist.
2. Provide translations for the language files in the `lang/` directory within your PR.
3. Validate that your changes do not break existing Filament UI components layout for RTL/LTR views.
4. Follow the standard pull request process below.

### Documentation and User Guides

We maintain comprehensive documentation for developers, and user guides for the everyday users of Kezi.

Our documentation and user guides are hosted in a separate repository. If you are looking to contribute to the user guides, correct a typo in the documentation, or write a tutorial, please submit your contributions to:
[https://github.com/Xoshbin/kezi-docs](https://github.com/Xoshbin/kezi-docs).

### Your First Code Contribution

Unsure where to begin contributing? You can start by looking through `good first issue` and `help wanted` issues.

### Pull Request Process

1. Fork the repository and create your branch from `main`.
2. If you've added code that should be tested, add tests.
3. If you've changed APIs, update the documentation.
4. Ensure the test suite passes (`php artisan test --parallel`).
5. Ensure your code passes code styling (`vendor/bin/pint`).
6. Issue that pull request!

## Development Guidelines

### Coding Standards

This project follows the [PSR-12](https://www.php-fig.org/psr/psr-12/) coding standard and uses [Laravel Pint](https://laravel.com/docs/pint) to enforce code style.

Before committing, make sure to run:

```bash
vendor/bin/pint
```

We also use PHPStan (via Larastan) for static analysis. Before finishing a task, make sure to run:

```bash
vendor/bin/phpstan analyse
```

### Testing

We use [Pest](https://pestphp.com/) for testing. Every new feature and bug fix should include appropriate tests.

To run the test suite:

```bash
php artisan test --parallel
```

Ensure your tests follow logical accounting principles and best practices. We also write Filament tests for the UI.
