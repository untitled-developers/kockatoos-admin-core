<x-kockatoos-admin-core::components.skyforge.layout>
    <div class="w-full flex justify-center">
        <div class="max-w-7xl w-full">
            <div class="w-full p-4 bg-white rounded-md shadow-sm">
                <h1 class="text-xl font-medium font-stone-800 py-4">Detected Tables sss</h1>
                <ul role="list" class="divide-y divide-gray-200 border border-gray-200">
                    @foreach($tables as $table)
                        <li class="flex items-center justify-between gap-x-6 py-4 hover:bg-gray-50 px-4 rounded-sm">
                            <div class="min-w-0">
                                <div class="flex items-start gap-x-3">
                                    <p class="text-sm/6 text-gray-900">{{$table}}</p>
                                </div>
                            </div>
                            <div class="flex flex-none items-center gap-x-4">
                                <a href="{{
                                route('skyforge.table-details', ['table' => $table]) }}"
                                   class="rounded-md bg-white flex items-center gap-x-2 px-2.5 py-1.5 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                                    <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960"
                                         width="24px"
                                         fill="#292524">
                                        <path
                                            d="M160-120v-80h480v80H160Zm226-194L160-540l84-86 228 226-86 86Zm254-254L414-796l86-84 226 226-86 86Zm184 408L302-682l56-56 522 522-56 56Z"/>
                                    </svg>
                                    Forge
                                </a>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
</x-kockatoos-admin-core::components.skyforge.layout>


