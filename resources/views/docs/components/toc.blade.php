@php /** @var array<int,array{level:int,id:string,text:string}> $toc */ @endphp

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

