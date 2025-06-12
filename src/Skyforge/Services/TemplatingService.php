<?php

namespace UntitledDevelopers\KockatoosAdminCore\Skyforge\Services;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Facades\File;

class TemplatingService
{
    protected string $stubPath;
    protected string $outputPath;
    protected array $variablesValues = [];

    /**
     * @throws FileNotFoundException
     */
    public function __construct(string $stubPath, string $outputPath)
    {
        $this->stubPath = $stubPath;
        $this->outputPath = $outputPath;
        $this->variablesValues = $this->getAllVariablesFromStub();

    }

    /**
     * @throws \Exception
     */
    public function generateFile(): string
    {
        if (File::exists($this->outputPath)) {

            throw new \Exception("Template already exists");
        }

        if (!File::exists($this->stubPath)) {
            throw new \Exception("Stub file does not exist: " . $this->stubPath);
        }

        $stubContent = File::get($this->stubPath);

        $replacedContent = $this->replaceVariables($stubContent, $this->variablesValues);

        if (!File::isDirectory(dirname($this->outputPath))) {
            File::makeDirectory(dirname($this->outputPath), 0755, true);
        }
        File::put($this->outputPath, $replacedContent);

        return File::get($this->outputPath);


    }

    /**
     * @throws FileNotFoundException
     * @throws \Exception
     */
    public function previewFileContent(): string
    {
        if (!File::exists($this->stubPath)) {
            throw new \Exception("Stub file does not exist: " . $this->stubPath);
        }

        $stubContent = File::get($this->stubPath);
        return $this->replaceVariables($stubContent, $this->variablesValues);
    }

    private function replaceVariables(string $content, array $replacements): string
    {
        foreach ($replacements as $key => $value) {
            $placeholder = '%%' . strtoupper($key) . '%%';
            $content = str_replace($placeholder, $value, $content);
        }

        return $content;
    }

    public function setVariableValue(string $variableName, string $value): string
    {
        $this->variablesValues[$variableName] = $value;
        return $this->variablesValues[$variableName];
    }


    /**
     * @throws FileNotFoundException
     */
    private function getAllVariablesFromStub(): array
    {
        $stubContent = File::get($this->stubPath);
        preg_match_all('/%%(.*?)%%/', $stubContent, $matches);
        $variables = [];
        foreach ($matches[1] as $match) {
            $variables[$match] = '';
        }
        return $variables;
    }


}
