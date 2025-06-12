<?php

namespace UntitledDevelopers\KockatoosAdminCore\Skyforge\Generators;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use UntitledDevelopers\KockatoosAdminCore\Skyforge\Models\Table;
use UntitledDevelopers\KockatoosAdminCore\Skyforge\Services\CodeBuilder;
use UntitledDevelopers\KockatoosAdminCore\Skyforge\Services\TemplatingService;
use UntitledDevelopers\KockatoosAdminCore\Skyforge\Stubs\ModelStub;

class ModelGenerator
{
    private TemplatingService $templatingService;
    private Table $table;
    private CodeBuilder $codeBuilder;

    /**
     * @throws FileNotFoundException
     */
    public function __construct(string $tableName)
    {
        $this->table = new Table($tableName);
        $this->codeBuilder = new CodeBuilder();
        $this->templatingService = new TemplatingService(
            __DIR__ . '/../Stubs/Model.stub',
            base_path('app/Models/' . $this->table->getTableModelName() . '.php')
        );
    }

    /**
     * @throws FileNotFoundException
     */
    public function previewGeneratedModel(): string
    {
        $this->templatingService->setVariableValue(ModelStub::$MODEL_NAME, $this->table->getTableModelName());
        $this->templatingService->setVariableValue(ModelStub::$CASTS, $this->codeBuilder->associateArrayToCodeString($this->getModelBooleanCasts()));
        return $this->templatingService->previewFileContent();
    }

    /**
     * @throws \Exception
     */
    public function generateModel(): string
    {
        $this->templatingService->setVariableValue('MODEL_NAME', $this->table->getTableModelName());
        $this->templatingService->setVariableValue('CASTS', $this->codeBuilder->associateArrayToCodeString($this->getModelBooleanCasts()));
        return $this->templatingService->generateFile();
    }

    private function getModelBooleanCasts(): array
    {
        $casts = [];
        foreach ($this->table->getBooleanColumns() as $column) {
            $casts[$column->getName()] = 'boolean';
        }

        return $casts;
    }


}
