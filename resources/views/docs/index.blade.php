@php /** @var array<int,array{slug:string,title:string}> $items */ @endphp

<x-docs.layout :title="__('Docs')">
    <x-slot:sidebar>
        @include('docs.components.sidebar', ['items' => $items])
    </x-slot:sidebar>

    <div class="docs-prose prose prose-slate dark:prose-invert max-w-none">
        <h1 class="text-2xl font-semibold mb-4">{{ __('Documentation') }}</h1>
        <ul class="list-disc ml-5 space-y-2">
            @foreach($items as $it)
                <li>
                    <a href="{{ url('/docs/' . $it['slug']) }}" class="text-primary-600 hover:underline">{{ $it['title'] }}</a>
                </li>
            @endforeach
        </ul>
    </div>
</x-docs.layout>

