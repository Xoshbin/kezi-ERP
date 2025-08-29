# Contributing to AccounTech Pro AI Helper

Thank you for considering contributing to AccounTech Pro AI Helper! We welcome contributions from the community.

## Development Setup

1. **Fork the repository** on GitHub
2. **Clone your fork** locally:
   ```bash
   git clone https://github.com/your-username/filament-ai-helper.git
   cd filament-ai-helper
   ```

3. **Install dependencies**:
   ```bash
   composer install
   ```

4. **Set up environment**:
   ```bash
   cp .env.example .env
   # Add your Gemini API key for testing
   echo "GEMINI_API_KEY=your_test_api_key" >> .env
   ```

5. **Run tests** to ensure everything works:
   ```bash
   composer test
   ```

## Development Guidelines

### Code Style

We follow PSR-12 coding standards. Please ensure your code adheres to these standards:

```bash
# Check code style
./vendor/bin/php-cs-fixer fix --dry-run --diff

# Fix code style issues
./vendor/bin/php-cs-fixer fix
```

### Testing

All new features and bug fixes must include tests:

- **Unit tests** for individual classes and methods
- **Feature tests** for Livewire components and integrations
- **Mock external APIs** to avoid real API calls during testing

```bash
# Run all tests
composer test

# Run specific test file
./vendor/bin/pest tests/Unit/Services/GeminiServiceTest.php

# Run tests with coverage
composer test-coverage
```

### Architecture Principles

Please follow these architectural principles:

1. **SOLID Principles**: Single responsibility, open/closed, Liskov substitution, interface segregation, dependency inversion
2. **Clean Architecture**: Separate concerns, dependency injection, testable code
3. **Laravel Best Practices**: Use Laravel conventions and patterns
4. **Filament Integration**: Follow Filament v4 patterns and conventions

### Code Organization

```
src/
├── Actions/           # Business logic actions
├── Concerns/          # Reusable traits
├── DTOs/             # Data transfer objects
├── Exceptions/       # Custom exceptions
├── Livewire/         # Livewire components
├── Services/         # External service integrations
└── FilamentAiHelperServiceProvider.php
```

## Contributing Process

### 1. Create an Issue

Before starting work, please create an issue to discuss:
- Bug reports with reproduction steps
- Feature requests with use cases
- Questions about implementation

### 2. Create a Branch

Create a descriptive branch name:
```bash
git checkout -b feature/add-conversation-history
git checkout -b bugfix/fix-rate-limiting
git checkout -b docs/improve-installation-guide
```

### 3. Make Changes

- Write clean, well-documented code
- Follow existing code patterns
- Add or update tests as needed
- Update documentation if necessary

### 4. Test Your Changes

```bash
# Run the full test suite
composer test

# Check code style
./vendor/bin/php-cs-fixer fix --dry-run

# Test with different PHP versions if possible
```

### 5. Commit Your Changes

Use clear, descriptive commit messages:
```bash
git add .
git commit -m "Add conversation history feature

- Store chat history in session
- Add clear history functionality
- Update UI to show conversation context
- Add tests for history management"
```

### 6. Submit a Pull Request

1. Push your branch to your fork
2. Create a pull request on GitHub
3. Provide a clear description of your changes
4. Link to any related issues
5. Ensure all CI checks pass

## Types of Contributions

### Bug Fixes
- Fix existing functionality that isn't working correctly
- Include reproduction steps in the issue
- Add regression tests to prevent future issues

### New Features
- Enhance existing functionality
- Add new capabilities
- Ensure backward compatibility
- Update documentation

### Documentation
- Improve README, examples, or inline documentation
- Add missing documentation
- Fix typos or unclear explanations

### Performance Improvements
- Optimize existing code
- Reduce memory usage or API calls
- Improve response times

## Code Review Process

All contributions go through code review:

1. **Automated checks**: CI runs tests and code style checks
2. **Manual review**: Maintainers review code quality and design
3. **Feedback**: Address any requested changes
4. **Approval**: Once approved, changes are merged

## Release Process

We follow semantic versioning (SemVer):
- **Major** (1.0.0): Breaking changes
- **Minor** (1.1.0): New features, backward compatible
- **Patch** (1.0.1): Bug fixes, backward compatible

## Getting Help

If you need help:
- Check existing issues and documentation
- Create a new issue with your question
- Join our community discussions

## Recognition

Contributors are recognized in:
- CHANGELOG.md for significant contributions
- README.md credits section
- GitHub contributors list

Thank you for contributing to AccounTech Pro AI Helper!
