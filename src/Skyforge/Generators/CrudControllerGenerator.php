<?php

namespace UntitledDevelopers\KockatoosAdminCore\Skyforge\Generators;


use UntitledDevelopers\KockatoosAdminCore\Skyforge\Models\Table;
use UntitledDevelopers\KockatoosAdminCore\Skyforge\Services\CodeBuilder;
use UntitledDevelopers\KockatoosAdminCore\Skyforge\Services\TemplatingService;
use UntitledDevelopers\KockatoosAdminCore\Skyforge\Stubs\CrudControllerStub;

class CrudControllerGenerator
{

    private TemplatingService $templatingService;
    private Table $table;
    private CodeBuilder $codeBuilder;

    /**
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    public function __construct(string $tableName)
    {
        $this->table = new Table($tableName);
        $this->codeBuilder = new CodeBuilder();
        $this->templatingService = new TemplatingService(
            __DIR__ . '/../Stubs/CrudController.stub',
            base_path('app/Http/Controllers/' . $this->table->getTableModelName() . 'Controller.php')
        );
    }

    /**
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    public function previewGeneratedController(): string
    {
        $this->templatingService->setVariableValue(CrudControllerStub::$CLASS_NAME, $this->table->getTableModelName() . 'Controller');
        $this->templatingService->setVariableValue(CrudControllerStub::$MODEL_NAME, $this->table->getTableModelName());
        $this->templatingService->setVariableValue(CrudControllerStub::$TABLE_NAME, $this->table->getName());
        $this->templatingService->setVariableValue(CrudControllerStub::$FILES_DIRECTORY, $this->table->getName());
        $this->templatingService->setVariableValue(CrudControllerStub::$SAFE_DELETE, 'true');
        $this->templatingService->setVariableValue(CrudControllerStub::$COLUMNS, $this->generateSelectColumns($this->table->getPrefixedColumns()));
        $this->templatingService->setVariableValue(CrudControllerStub::$SAVE_COLUMNS, $this->generateModelAssignments($this->table->getColumnsNames()));
        return $this->templatingService->previewFileContent();
    }

    function generateSelectColumns(array $columns): string
    {   // TODO exclude some of the columns
        $formattedColumns = [];

        foreach ($columns as $column) {
            $formattedColumns[] = "    '{$column}'";
        }

        return "\n" . implode(",\n", $formattedColumns) . "\n";
    }

    function generateModelAssignments(array $columns): string
    {
        //TODO handle edge cases (boolean, blobs, images....)
        $assignments = [];

        foreach ($columns as $column) {
            $assignments[] = "\$model->{$column} = \$data->{$column};";
        }

        return implode("\n", $assignments);
    }
}
