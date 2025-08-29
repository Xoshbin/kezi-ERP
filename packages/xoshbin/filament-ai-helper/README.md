# AccounTech Pro AI Helper

A context-aware AI assistant plugin for Filament v4 powered by Google Gemini API.

## Features

- 🤖 **Context-Aware AI Assistant**: Intelligent analysis based on the current Filament resource record
- 🌍 **Multi-Language Support**: Responds in your application's current locale
- 📊 **Accounting Expertise**: Specialized prompts for financial records and accounting principles
- 🎯 **Smart Analysis**: Provides insights on journal entries, invoices, vendor bills, and more
- 🔒 **Secure Integration**: Proper authentication and data handling
- 🎨 **Beautiful UI**: Clean chat interface integrated seamlessly with Filament
- ⚡ **Performance Optimized**: Response caching and rate limiting
- 🧪 **Fully Tested**: Comprehensive test suite with Pest

## Requirements

- PHP 8.2+
- Laravel 11.0+ or 12.0+
- Filament 4.0+
- Google Gemini API Key

## Installation

### Step 1: Install the Package

Install the package via Composer:

```bash
composer require accountech/filament-ai-helper
```

### Step 2: Publish Configuration

Publish the configuration file:

```bash
php artisan vendor:publish --tag="filament-ai-helper-config"
```

### Step 3: Configure API Key

Add your Google Gemini API key to your `.env` file:

```env
GEMINI_API_KEY=your_gemini_api_key_here
```

To get a Gemini API key:
1. Visit [Google AI Studio](https://makersuite.google.com/app/apikey)
2. Create a new API key
3. Copy the key to your `.env` file

### Step 4: Register the Plugin (Optional)

The plugin will automatically register itself. However, if you want to customize it, you can register it manually in your panel provider:

```php
use Xoshbin\FilamentAiHelper\FilamentAiHelperPlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        // ... other configuration
        ->plugins([
            FilamentAiHelperPlugin::make()
                ->buttonLabel('AI Assistant')
                ->buttonIcon('heroicon-o-sparkles')
                ->modalWidth('xl'),
        ]);
}
```

## Configuration

The configuration file `config/filament-ai-helper.php` allows you to customize:

### API Settings
```php
'gemini' => [
    'api_key' => env('GEMINI_API_KEY'),
    'api_url' => env('GEMINI_API_URL', 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash-latest:generateContent'),
    'timeout' => env('GEMINI_API_TIMEOUT', 30),
    'max_retries' => env('GEMINI_API_MAX_RETRIES', 3),
],
```

### UI Preferences
```php
'ui' => [
    'button_label' => 'AccounTech Pro',
    'button_icon' => 'heroicon-o-sparkles',
    'modal_width' => 'lg',
    'enable_welcome_message' => true,
],
```

### Security Settings
```php
'security' => [
    'rate_limit' => [
        'enabled' => true,
        'max_requests' => 10,
        'per_minutes' => 1,
    ],
    'sanitize_input' => true,
    'log_requests' => env('FILAMENT_AI_HELPER_LOG_REQUESTS', false),
],
```

### Custom Prompts
```php
'assistant' => [
    'context_prompts' => [
        'journal_entry' => 'Analyze the journal lines. Is the entry balanced?',
        'invoice' => 'Calculate the gross profit margin for this invoice.',
        'vendor_bill' => 'Review the vendor bill for accuracy.',
        'default' => 'Provide analysis of this record.',
    ],
],
```

## Usage

### Basic Usage

Once installed, the AccounTech Pro AI Helper will automatically appear as a header action on all Filament resource pages. Click the "AccounTech Pro" button to open the AI assistant modal.

The AI assistant will automatically understand the context of the current record and provide relevant insights and analysis.

### Adding to Specific Resources

You can also add the AI helper to specific resource pages using the provided trait:

```php
use Xoshbin\FilamentAiHelper\Concerns\HasAiHelper;
use Filament\Resources\Pages\EditRecord;

class EditInvoice extends EditRecord
{
    use HasAiHelper;

    protected function getHeaderActions(): array
    {
        return $this->addAiHelperToHeaderActions([
            // ... your other actions
        ]);
    }
}
```

### Custom Authorization

You can control who can use the AI helper by overriding the authorization method:

```php
use Xoshbin\FilamentAiHelper\Concerns\HasAiHelper;

class EditInvoice extends EditRecord
{
    use HasAiHelper;

    protected function canUseAiHelper(): bool
    {
        return auth()->user()->can('use-ai-assistant');
    }
}
```

### Manual Action Creation

For more control, you can manually create the AI helper action:

```php
use Xoshbin\FilamentAiHelper\Actions\AiHelperHeaderAction;

protected function getHeaderActions(): array
{
    return [
        AiHelperHeaderAction::make(),
        // ... other actions
    ];
}
```
