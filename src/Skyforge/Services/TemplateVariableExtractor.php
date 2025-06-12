<?php

namespace UntitledDevelopers\KockatoosAdminCore\Skyforge\Services;
class TemplateVariableExtractor
{
    /**
     * Generate classes for all stub files automatically
     */
    public static function generateAll(): void
    {
        $stubsPath = base_path('app/skyforge/stubs/');


        $files = glob($stubsPath . '*');

        foreach ($files as $filePath) {

            if (!is_file($filePath) || pathinfo($filePath, PATHINFO_EXTENSION) !== 'stub') {
                continue;
            }


            $fileName = pathinfo($filePath, PATHINFO_FILENAME);
            $className = $fileName . 'Stub';


            $content = file_get_contents($filePath);


            preg_match_all('/%%([A-Z_]+)%%/', $content, $matches);
            $variables = array_unique($matches[1]);


            if (empty($variables)) {
                continue;
            }

            $classContent = self::generateClassContent($className, $variables);


            $outputPath = $stubsPath . $className . '.php';
            file_put_contents($outputPath, $classContent);

        }
    }

    /**
     * Generate the class content
     */
    private static function generateClassContent(string $className, array $variables): string
    {
        $staticVars = [];
        foreach ($variables as $variable) {
            $staticVars[] = "    public static string \${$variable} = '{$variable}';";
        }

        $staticVarsCode = implode("\n", $staticVars);

        return "<?php

namespace App\\skyforge\\stubs;

class {$className}
{
{$staticVarsCode}
}
";
    }
}

