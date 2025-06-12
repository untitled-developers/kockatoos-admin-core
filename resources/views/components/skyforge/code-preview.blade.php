@props(['language' , 'type', 'title'])

<section id="{{$type}}">
    <div class="flex flex-col gap-y-2 py-2">
        <h2 class="font-medium text-xl">{{$title}}</h2>
        <pre class="language-{{ $language }}">
        <code>
            {{ $slot }}
        </code>
    </pre>

    </div>

</section>
