# Changelog

All notable changes to `filament-ai-helper` will be documented in this file.

## [1.0.0] - 2025-01-19

### Added
- Initial release of AccounTech Pro AI Helper
- Context-aware AI assistant for Filament v4 resources
- Google Gemini API integration
- Multi-language support with automatic locale detection
- Specialized accounting prompts for different record types
- Beautiful chat interface with Livewire components
- Rate limiting and security features
- Response caching for improved performance
- Comprehensive test suite with Pest
- HasAiHelper trait for easy integration
- Global header action for all resource pages
- Configurable UI elements and behavior
- Support for custom authorization rules
- Eager loading of relationships for context
- Input sanitization and security measures
- Detailed logging and error handling
- Fallback responses for API failures
- Welcome message generation
- Keyboard shortcuts (Ctrl+Enter to send)
- Clear chat functionality
- Loading states and error handling
- Responsive design for mobile devices

### Features
- **Context Awareness**: Automatically detects current Filament resource and record
- **Smart Prompts**: Different analysis prompts for invoices, journal entries, vendor bills, etc.
- **Multi-Language**: Responds in the application's current locale
- **Security**: Rate limiting, input sanitization, and role-based access control
- **Performance**: Response caching and optimized API calls
- **Extensibility**: Easy to customize prompts, UI, and behavior
- **Testing**: Comprehensive test coverage with mocked API calls

### Configuration Options
- Gemini API settings (key, URL, timeout, retries)
- UI customization (button label, icon, modal width)
- Security settings (rate limiting, input sanitization)
- Assistant behavior (prompts, context length, relationships)
- Caching configuration (TTL, enabled/disabled)

### Developer Experience
- Clean architecture with SOLID principles
- Dependency injection and service container integration
- Comprehensive documentation with examples
- Easy installation and configuration
- Trait-based integration for resources
- Plugin-based architecture for panels

### Supported Filament Features
- Resource pages (List, Create, Edit, View)
- Header actions integration
- Modal components
- Livewire components
- Multi-panel applications
- Custom authorization

### Requirements
- PHP 8.2+
- Laravel 11.0+ or 12.0+
- Filament 4.0+
- Google Gemini API key
