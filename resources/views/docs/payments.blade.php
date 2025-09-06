@php /** @var string $html */ @endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('Payments Guide') }}</title>
    <style>
        body { font-family: ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial, "Apple Color Emoji", "Segoe UI Emoji"; }
        .container { max-width: 64rem; margin: 0 auto; padding: 2rem 1rem; }
        .prose { line-height: 1.6; }
    </style>
</head>
<body class="bg-gray-50 text-gray-900">
    <div class="mx-auto max-w-4xl py-8 px-4">
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-semibold">{{ __('Payments Guide') }}</h1>
            <a href="{{ url()->current() }}" class="text-sm text-gray-500 hover:text-gray-700">
                {{ __('Open in full page') }}
            </a>
        </div>
        <div class="prose max-w-none">{!! $html !!}</div>
    </div>
</body>
</html>

