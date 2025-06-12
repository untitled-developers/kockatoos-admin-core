@props(['table'])
<html lang="en" class="h-full bg-white">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Laravel') }}</title>
    <style>
        html {
            scroll-behavior: smooth;
        }
    </style>
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    {{--    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet"/>--}}

    <style>
        /* PrismJS 1.30.0
https://prismjs.com/download#themes=prism-tomorrow&languages=markup+css+clike+javascript+javadoclike+markup-templating+php+phpdoc+php-extras */
        code[class*=language-], pre[class*=language-] {
            color: #ccc;
            background: 0 0;
            font-family: Consolas, Monaco, 'Andale Mono', 'Ubuntu Mono', monospace;
            font-size: 1em;
            text-align: left;
            white-space: pre;
            word-spacing: normal;
            word-break: normal;
            word-wrap: normal;
            line-height: 1.5;
            -moz-tab-size: 4;
            -o-tab-size: 4;
            tab-size: 4;
            -webkit-hyphens: none;
            -moz-hyphens: none;
            -ms-hyphens: none;
            hyphens: none
        }

        pre[class*=language-] {
            padding: 1em;
            margin: .5em 0;
            overflow: auto
        }

        :not(pre) > code[class*=language-], pre[class*=language-] {
            background: #2d2d2d
        }

        :not(pre) > code[class*=language-] {
            padding: .1em;
            border-radius: .3em;
            white-space: normal
        }

        .token.block-comment, .token.cdata, .token.comment, .token.doctype, .token.prolog {
            color: #999
        }

        .token.punctuation {
            color: #ccc
        }

        .token.attr-name, .token.deleted, .token.namespace, .token.tag {
            color: #e2777a
        }

        .token.function-name {
            color: #6196cc
        }

        .token.boolean, .token.function, .token.number {
            color: #f08d49
        }

        .token.class-name, .token.constant, .token.property, .token.symbol {
            color: #f8c555
        }

        .token.atrule, .token.builtin, .token.important, .token.keyword, .token.selector {
            color: #cc99cd
        }

        .token.attr-value, .token.char, .token.regex, .token.string, .token.variable {
            color: #7ec699
        }

        .token.entity, .token.operator, .token.url {
            color: #67cdcc
        }

        .token.bold, .token.important {
            font-weight: 700
        }

        .token.italic {
            font-style: italic
        }

        .token.entity {
            cursor: help
        }

        .token.inserted {
            color: green
        }

    </style>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/9000.0.1/prism.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/9000.0.1/components/prism-javascript.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/9000.0.1/components/prism-php-extras.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/9000.0.1/components/prism-php.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
</head>
<body class=" h-full">
<div>
    <div class="fixed inset-y-0 z-50 flex w-72 flex-col">
        <div class="flex grow flex-col overflow-y-auto border-r border-gray-400 bg-white px-6">
            <div class="flex justify-between items-center">
                <a href="{{route('skyforge.index')}}" class="bg-gray-50 p-2 rounded-sm">
                    <svg xmlns="http://www.w3.org/2000/svg"
                         fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                         stroke="currentColor" class="size-6 text-stone-500">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25"/>
                    </svg>

                </a>
                <x-kockatoos-admin-core::components.skyforge.table-header>
                    {{$table}}
                </x-kockatoos-admin-core::components.skyforge.table-header>
            </div>

            <nav class="flex flex-1 flex-col">
                <ul role="list" class="flex flex-1 flex-col gap-y-7 ">
                    <li>
                        <ul role="list" class="-mx-2 space-y-1">
                            <li>
                                <a href="#model"
                                   class="justify-end flex  p-2 text-sm/6 font-semibold text-gray-700  hover:text-stone-600">
                                    Model
                                </a>
                            </li>
                            <li>
                                <a href="#crud-controller"
                                   class="justify-end flex   p-2 text-sm/6 font-semibold text-gray-700  hover:text-stone-600">
                                    CRUD Controller
                                </a>
                            </li>
                            <li>
                                <a href="#"
                                   class="justify-end flex   p-2 text-sm/6 font-semibold text-gray-700  hover:text-stone-600">
                                    CRUD Page
                                </a>
                            </li>
                            <li>
                                <a href="#"
                                   class="justify-end flex   p-2 text-sm/6 font-semibold text-gray-700  hover:text-stone-600">
                                    Edit Dialog
                                </a>
                            </li>
                        </ul>
                    </li>
                </ul>
            </nav>
        </div>
    </div>

    <main class="pl-72 pt-4">
        <div>
            {{$slot}}
        </div>
    </main>
</div>
</body>
</html>


