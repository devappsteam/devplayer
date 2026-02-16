<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MakeModuleCommand extends Command
{
    /**
     * Nome e assinatura do comando do console
     *
     * @var string
     */
    protected $signature = 'make:module {name : Nome do módulo}
                            {--force : Sobrescrever arquivos do módulo existente}';

    /**
     * Descrição do comando do console
     *
     * @var string
     */
    protected $description = 'Cria um novo módulo com estrutura completa';

    /**
     * Nome do módulo
     *
     * @var string
     */
    protected string $moduleName;

    /**
     * Caminho do módulo
     *
     * @var string
     */
    protected string $modulePath;

    /**
     * Caminho dos stubs
     *
     * @var string
     */
    protected string $stubsPath;

    /**
     * Executa o comando do console
     *
     * @return int
     */
    public function handle(): int
    {
        $this->moduleName = Str::studly($this->argument('name'));
        $this->modulePath = app_path("Modules/{$this->moduleName}");
        $this->stubsPath = base_path('stubs/modules');

        // Verifica se o módulo já existe
        if (File::exists($this->modulePath) && !$this->option('force')) {
            $this->error("O módulo {$this->moduleName} já existe!");
            $this->info("Use --force para sobrescrever os arquivos existentes.");
            return self::FAILURE;
        }

        // Verifica se os stubs existem
        if (!File::exists($this->stubsPath)) {
            $this->error("Diretório de stubs não encontrado em: {$this->stubsPath}");
            $this->info("Por favor, crie o diretório de stubs primeiro.");
            return self::FAILURE;
        }

        $this->info("Criando módulo: {$this->moduleName}");
        $this->newLine();

        // Cria a estrutura do módulo
        $this->createModuleStructure();

        // Gera os arquivos a partir dos stubs
        $this->generateFiles();

        $this->newLine();
        $this->info(">>>> Módulo {$this->moduleName} criado com sucesso!");
        $this->newLine();
        $this->info("Próximos passos:");
        $this->line("  1. Atualize o arquivo de migration em: app/Modules/{$this->moduleName}/Database/Migrations/");
        $this->line("  2. Adicione os campos fillable em: app/Modules/{$this->moduleName}/Models/{$this->moduleName}.php");
        $this->line("  3. Adicione as regras de validação em: app/Modules/{$this->moduleName}/Requests/");
        $this->line("  4. Customize o resource em: app/Modules/{$this->moduleName}/Resources/{$this->moduleName}Resource.php");
        $this->line("  5. Execute as migrations: php artisan migrate");

        return self::SUCCESS;
    }

    /**
     * Cria a estrutura de diretórios do módulo
     *
     * @return void
     */
    protected function createModuleStructure(): void
    {
        $directories = [
            'Controllers',
            'Models',
            'Repositories/Contracts',
            'Services',
            'Requests',
            'Resources',
            'Policies',
            'Middleware',
            'Enums',
            'Contracts',
            'Events',
            'Listeners',
            'Observers',
            'Helpers',
            'Database/Migrations',
            'Database/Seeders',
            'Database/Factories',
            'Routes',
            'Tests/Feature',
            'Tests/Unit',
            'Config',
            'Providers',
        ];

        foreach ($directories as $directory) {
            $path = "{$this->modulePath}/{$directory}";

            if (!File::exists($path)) {
                File::makeDirectory($path, 0755, true);
                $this->line("   >>>> Criado: {$directory}");
            }
        }
    }

    /**
     * Gera os arquivos a partir dos stubs
     *
     * @return void
     */
    protected function generateFiles(): void
    {
        $this->newLine();
        $this->info("Gerando arquivos...");

        // Controller
        $this->generateFile(
            'controller.stub',
            "Controllers/{$this->moduleName}Controller.php"
        );

        // Model
        $this->generateFile(
            'model.stub',
            "Models/{$this->moduleName}.php"
        );

        // Interface do repositório
        $this->generateFile(
            'repository-interface.stub',
            "Repositories/Contracts/{$this->moduleName}RepositoryInterface.php"
        );

        // Repositório
        $this->generateFile(
            'repository.stub',
            "Repositories/{$this->moduleName}Repository.php"
        );

        // Service
        $this->generateFile(
            'service.stub',
            "Services/{$this->moduleName}Service.php"
        );

        // Request de criação (Store)
        $this->generateFile(
            'store-request.stub',
            "Requests/Store{$this->moduleName}Request.php"
        );

        // Request de atualização (Update)
        $this->generateFile(
            'update-request.stub',
            "Requests/Update{$this->moduleName}Request.php"
        );

        // Resource
        $this->generateFile(
            'resource.stub',
            "Resources/{$this->moduleName}Resource.php"
        );

        // Policy
        $this->generateFile(
            'policy.stub',
            "Policies/{$this->moduleName}Policy.php"
        );

        // Seeder
        $this->generateFile(
            'seeder.stub',
            "Database/Seeders/{$this->moduleName}Seeder.php"
        );

        // Factory
        $this->generateFile(
            'factory.stub',
            "Database/Factories/{$this->moduleName}Factory.php"
        );

        // Rotas
        $this->generateFile(
            'routes-api.stub',
            "Routes/api.php"
        );

        // Configuração
        $this->generateFile(
            'config.stub',
            "Config/" . strtolower($this->moduleName) . ".php"
        );

        // Service Provider
        $this->generateFile(
            'service-provider.stub',
            "Providers/{$this->moduleName}ServiceProvider.php"
        );

        // Migration
        $this->generateMigration();

        // Observer
        $this->generateFile(
            'observer.stub',
            "Observers/{$this->moduleName}Observer.php"
        );

        // Testes
        $this->generateFile(
            'feature-test.stub',
            "Tests/Feature/{$this->moduleName}Test.php"
        );

        $this->generateFile(
            'unit-test.stub',
            "Tests/Unit/{$this->moduleName}ServiceTest.php"
        );
    }

    /**
     * Gera um arquivo a partir de um stub
     *
     * @param string $stub
     * @param string $destination
     * @return void
     */
    protected function generateFile(string $stub, string $destination): void
    {
        $stubPath = "{$this->stubsPath}/{$stub}";
        $destinationPath = "{$this->modulePath}/{$destination}";

        if (!File::exists($stubPath)) {
            $this->warn(">>>> Stub não encontrado: {$stub}");
            return;
        }

        $content = File::get($stubPath);
        $content = $this->replaceStubVariables($content);

        File::put($destinationPath, $content);
        $this->line("   >>>> Gerado: {$destination}");
    }

    /**
     * Gera o arquivo de migration
     *
     * @return void
     */
    protected function generateMigration(): void
    {
        $tableName = Str::snake(Str::pluralStudly($this->moduleName));
        $timestamp = date('Y_m_d_His');
        $migrationName = "create_{$tableName}_table";
        $fileName = "{$timestamp}_{$migrationName}.php";

        $stubPath = "{$this->stubsPath}/migration.stub";
        $destinationPath = "{$this->modulePath}/Database/Migrations/{$fileName}";

        if (!File::exists($stubPath)) {
            $this->warn("   >>>> Stub de migration não encontrado");
            return;
        }

        $content = File::get($stubPath);
        $content = $this->replaceStubVariables($content);
        $content = str_replace('{{TABLE_NAME}}', $tableName, $content);

        File::put($destinationPath, $content);
        $this->line("   >>>> Gerado: Database/Migrations/{$fileName}");
    }

    /**
     * Substitui as variáveis do stub pelos valores reais
     *
     * @param string $content
     * @return string
     */
    protected function replaceStubVariables(string $content): string
    {
        $moduleLower = Str::camel($this->moduleName);
        $moduleLowerPlural = Str::plural($moduleLower);
        $modulePlural = Str::pluralStudly($this->moduleName);
        $moduleUpper = Str::upper($this->moduleName);

        $replacements = [
            '{{MODULE}}'                => $this->moduleName,
            '{{MODULE_UPPER}}'          => $moduleUpper,
            '{{MODULE_LOWER}}'          => $moduleLower,
            '{{MODULE_LOWER_PLURAL}}'   => $moduleLowerPlural,
            '{{MODULE_PLURAL}}'         => $modulePlural,
        ];

        return str_replace(
            array_keys($replacements),
            array_values($replacements),
            $content
        );
    }
}
