<?php

namespace UntitledDevelopers\KockatoosAdminCore\Skyforge;

use UntitledDevelopers\KockatoosAdminCore\Skyforge\Services\SkyForgeService;
use UntitledDevelopers\KockatoosAdminCore\Skyforge\Generators\CrudControllerGenerator;
use UntitledDevelopers\KockatoosAdminCore\Skyforge\Generators\ModelGenerator;
use UntitledDevelopers\KockatoosAdminCore\Skyforge\Services\TemplateVariableExtractor;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Http\Request;


class SkyForgeController
{
    protected SkyForgeService $skyForgeService;

    public function __construct(SkyForgeService $skyForgeService)
    {
        $this->skyForgeService = $skyForgeService;
    }

    public function index()
    {
        TemplateVariableExtractor::generateAll();
        return view('kockatoos-admin-core::components.skyforge.skyforge', [
            'tables' => $this->skyForgeService->getAllTableNames(),
        ]);
    }

    /**
     * @throws FileNotFoundException
     * @throws \Exception
     */
    public function table(Request $request, string $table)
    {
        $modelGenerator = new ModelGenerator($table);
        $crudControllerGenerator = new CrudControllerGenerator($table);
        return view('kockatoos-admin-core::components.skyforge.table-details', [
            'table' => $table,
            'model' => $modelGenerator->previewGeneratedModel(),
            'crudController' => $crudControllerGenerator->previewGeneratedController(),
        ]);

    }
}
