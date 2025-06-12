<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    {{--    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet"/>--}}

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
</head>
<body class="bg-gray-100">
<h2 class="text-center font-bold text-4xl py-2 text-stone-800 border-b border-stone-400">
    SKYFORGE
</h2>
<div>
    <div class="flex items-center justify-center gap-x-2">
        <p class="text-lg text-stone-600  italic py-4">
            "Skyforge Steel is my art and honor. The Companions need the best, so they come to me."
            ―Eorlund Gray-Mane

        </p>
        <button id="quote-btn"
                class="text-stone-800 cursor-pointer">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                 stroke="currentColor" class="size-6">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M19.114 5.636a9 9 0 0 1 0 12.728M16.463 8.288a5.25 5.25 0 0 1 0 7.424M6.75 8.25l4.72-4.72a.75.75 0 0 1 1.28.53v15.88a.75.75 0 0 1-1.28.53l-4.72-4.72H4.51c-.88 0-1.704-.507-1.938-1.354A9.009 9.009 0 0 1 2.25 12c0-.83.112-1.633.322-2.396C2.806 8.756 3.63 8.25 4.51 8.25H6.75Z"/>
            </svg>
        </button>
    </div>

</div>

<div class="w-full">
        {{ $slot }}
</div>

<script>
    document.getElementById('quote-btn').addEventListener('click', function () {
        const audio = new Audio('{{ asset('Eorlund.ogg') }}');
        audio.play();
    });
</script>
</body>
</html>
