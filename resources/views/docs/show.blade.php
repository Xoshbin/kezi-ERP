@php /** @var string $html */ @endphp

<x-docs.layout :title="$title">
    <x-slot:sidebar>
        @include('docs.components.sidebar', ['items' => \App\Services\DocumentationService::make()->list(), 'active' => $slug ?? null])
    </x-slot:sidebar>

    <article class="docs-prose prose prose-slate dark:prose-invert max-w-none">
        <header class="mb-6">
            <nav class="text-sm text-gray-500 dark:text-gray-400" aria-label="Breadcrumb">
                <ol class="flex space-x-2">
                    @foreach($breadcrumbs as $crumb)
                        <li>
                            @if($crumb['slug'])
                                <a href="{{ url('/docs/' . $crumb['slug']) }}" class="hover:underline">{{ $crumb['title'] }}</a>
                            @else
                                {{ $crumb['title'] }}
                            @endif
                        </li>
                        @if(!$loop->last)
                            <li aria-hidden="true">/</li>
                        @endif
                    @endforeach
                </ol>
            </nav>
            <h1 class="text-2xl font-semibold">{{ $title }}</h1>
        </header>

        {!! $html !!}

        <footer class="mt-8 border-t pt-4 text-sm text-gray-500 dark:text-gray-400">
            {{ __('Last updated') }}
        </footer>
    </article>

    <x-slot:toc>
        @if(!empty($toc))
            <nav class="text-sm">
                <h3 class="font-medium mb-2">{{ __('On this page') }}</h3>
                <ul class="space-y-1">
                    @foreach($toc as $item)
                        <li class="pl-{{ $item['level'] === 3 ? '3' : '0' }}">
                            <a href="#{{ $item['id'] }}" class="block px-2 py-1 rounded hover:bg-gray-100 dark:hover:bg-gray-800">{{ $item['text'] }}</a>
                        </li>
                    @endforeach
                </ul>
            </nav>
        @endif
    </x-slot:toc>
</x-docs.layout>

