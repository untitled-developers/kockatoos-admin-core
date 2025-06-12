<x-kockatoos-admin-core::components.skyforge.table-details-layout table="{{$table}}">

    <div class="flex flex-col gap-y-4 px-4">
        <x-kockatoos-admin-core::components.skyforge.code-preview type="model" language="php" title="Model">
            {{$model}}
        </x-kockatoos-admin-core::components.skyforge.code-preview>

        <x-kockatoos-admin-core::components.skyforge.code-preview type="crud-controller" language="php"
                                                                  title="CRUD Controller">
            {{$crudController}}
        </x-kockatoos-admin-core::components.skyforge.code-preview>
    </div>
</x-kockatoos-admin-core::components.skyforge.table-details-layout>

