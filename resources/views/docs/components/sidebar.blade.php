@php /** @var array<int,array{slug:string,title:string}> $items */ @endphp

<nav class="text-sm space-y-1">
    @foreach($items as $it)
        <a href="{{ url('/docs/' . $it['slug']) }}" class="block px-2 py-1 rounded hover:bg-gray-100 dark:hover:bg-gray-800 @if(($active ?? '') === $it['slug']) font-medium @endif">{{ $it['title'] }}</a>
    @endforeach
</nav>

