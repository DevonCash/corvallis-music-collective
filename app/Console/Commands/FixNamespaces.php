<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Finder\SplFileInfo;

class FixNamespaces extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'namespace:fix
                            {--dry-run : Show what would be changed without making changes}
                            {--path= : Specific path to check (relative to project root)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix namespaces in PHP files to match PSR-4 standards from composer.json';

    /**
     * PSR-4 mappings from composer.json.
     *
     * @var array
     */
    protected $psr4Mappings = [];

    /**
     * Project base path.
     *
     * @var string
     */
    protected $basePath;

    /**
     * Report of changes.
     *
     * @var array
     */
    protected $report = [
        'checked' => 0,
        'updated' => 0,
        'skipped' => 0,
        'errors' => 0,
        'details' => []
    ];

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->basePath = base_path();
        $this->loadPSR4Mappings();

        $isDryRun = $this->option('dry-run');
        $specificPath = $this->option('path');

        $this->info('Starting namespace check' . ($isDryRun ? ' (DRY RUN)' : ''));

        foreach ($this->psr4Mappings as $namespace => $path) {
            $fullPath = $this->basePath . '/' . $path;

            if ($specificPath && !str_starts_with($path, $specificPath) && !str_starts_with($specificPath, $path)) {
                continue;
            }

            if (!File::isDirectory($fullPath)) {
                $this->warn("Directory not found: {$fullPath}");
                continue;
            }

            $this->info("Checking namespace '{$namespace}' in path: {$path}");
            $this->processDirectory($fullPath, $namespace, $isDryRun);
        }

        $this->displayReport();

        return Command::SUCCESS;
    }

    /**
     * Load PSR-4 mappings from composer.json.
     *
     * @return void
     */
    protected function loadPSR4Mappings()
    {
        $composerPath = $this->basePath . '/composer.json';

        if (!File::exists($composerPath)) {
            $this->error('composer.json not found!');
            exit(Command::FAILURE);
        }

        $composerJson = json_decode(File::get($composerPath), true);

        if (!isset($composerJson['autoload']['psr-4'])) {
            $this->error('No PSR-4 mappings found in composer.json!');
            exit(Command::FAILURE);
        }

        $this->psr4Mappings = $composerJson['autoload']['psr-4'];

        // Normalize paths (remove trailing slashes)
        foreach ($this->psr4Mappings as $namespace => $path) {
            $this->psr4Mappings[$namespace] = rtrim($path, '/\\');
        }
    }

    /**
     * Process a directory recursively.
     *
     * @param string $directory
     * @param string $baseNamespace
     * @param bool $isDryRun
     * @return void
     */
    protected function processDirectory($directory, $baseNamespace, $isDryRun)
    {
        $files = File::allFiles($directory);

        foreach ($files as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $this->processFile($file, $baseNamespace, $isDryRun);
        }
    }

    /**
     * Process a single PHP file.
     *
     * @param \Symfony\Component\Finder\SplFileInfo $file
     * @param string $baseNamespace
     * @param bool $isDryRun
     * @return void
     */
    protected function processFile(SplFileInfo $file, $baseNamespace, $isDryRun)
    {
        $this->report['checked']++;
        $filePath = $file->getPathname();
        $relativePath = $this->getRelativePath($filePath);

        // Skip files without classes/interfaces/traits/enums
        $content = $file->getContents();
        if (!preg_match('/(class|interface|trait|enum)\s+\w+/i', $content)) {
            $this->report['skipped']++;
            return;
        }

        // Calculate correct namespace
        $correctNamespace = $this->calculateCorrectNamespace($file, $baseNamespace);
        if (!$correctNamespace) {
            $this->report['errors']++;
            $this->report['details'][] = [
                'file' => $relativePath,
                'status' => 'error',
                'message' => 'Could not determine correct namespace'
            ];
            return;
        }

        // Extract current namespace
        $currentNamespace = null;
        if (preg_match('/namespace\s+([^;]+);/', $content, $matches)) {
            $currentNamespace = $matches[1];
        }

        // Check if namespace needs updating
        if ($currentNamespace === $correctNamespace) {
            return; // Already correct
        }

        // Record the change
        $this->report['details'][] = [
            'file' => $relativePath,
            'status' => 'updated',
            'from' => $currentNamespace ?: '(none)',
            'to' => $correctNamespace
        ];

        if (!$isDryRun) {
            $updatedContent = $this->updateNamespace($content, $currentNamespace, $correctNamespace);
            File::put($filePath, $updatedContent);
            $this->report['updated']++;
        } else {
            $this->report['updated']++;
        }
    }

    /**
     * Calculate the correct namespace for a file.
     *
     * @param \Symfony\Component\Finder\SplFileInfo $file
     * @param string $baseNamespace
     * @return string|null
     */
    protected function calculateCorrectNamespace(SplFileInfo $file, $baseNamespace)
    {
        $baseNamespace = rtrim($baseNamespace, '\\');
        $filePath = $file->getPathname();

        // Find the matching PSR-4 root directory
        foreach ($this->psr4Mappings as $namespace => $path) {
            $fullPath = $this->basePath . '/' . $path;

            if (str_starts_with($filePath, $fullPath)) {
                // Get the subdirectory path
                $subPath = substr($filePath, strlen($fullPath) + 1);
                // Remove the filename
                $subPath = substr($subPath, 0, strrpos($subPath, '/') ?: 0);
                // Convert directory separators to namespace separators
                $subNamespace = str_replace('/', '\\', $subPath);

                if ($subNamespace) {
                    return rtrim($namespace, '\\') . '\\' . $subNamespace;
                } else {
                    return rtrim($namespace, '\\');
                }
            }
        }

        return null;
    }

    /**
     * Update the namespace in the file content.
     *
     * @param string $content
     * @param string|null $currentNamespace
     * @param string $correctNamespace
     * @return string
     */
    protected function updateNamespace($content, $currentNamespace, $correctNamespace)
    {
        if ($currentNamespace) {
            // Replace existing namespace
            return str_replace(
                "namespace {$currentNamespace};",
                "namespace {$correctNamespace};",
                $content
            );
        } else {
            // Add namespace if it doesn't exist
            return preg_replace(
                '/^(<\?php)(\s+)/i',
                "$1\n\nnamespace {$correctNamespace};\n",
                $content
            );
        }
    }

    /**
     * Get the file path relative to the base path.
     *
     * @param string $filePath
     * @return string
     */
    protected function getRelativePath($filePath)
    {
        return substr($filePath, strlen($this->basePath) + 1);
    }

    /**
     * Display the report of changes.
     *
     * @return void
     */
    protected function displayReport()
    {
        $this->newLine();
        $this->info('====== Namespace Fix Report ======');
        $this->line("Files checked: {$this->report['checked']}");
        $this->line("Files updated: {$this->report['updated']}");
        $this->line("Files skipped: {$this->report['skipped']}");
        $this->line("Errors: {$this->report['errors']}");

        if (count($this->report['details']) > 0) {
            $this->newLine();
            $this->info('Namespace changes:');

            $headers = ['File', 'Status', 'From', 'To'];
            $rows = [];

            foreach ($this->report['details'] as $detail) {
                $rows[] = [
                    $detail['file'],
                    $detail['status'],
                    $detail['from'] ?? '-',
                    $detail['to'] ?? '-'
                ];
            }

            $this->table($headers, $rows);
        }

        $this->newLine();

        if ($this->option('dry-run')) {
            $this->warn('This was a dry run. No files were modified.');
            $this->info('Run without --dry-run to apply changes.');
        } else if ($this->report['updated'] > 0) {
            $this->info('Don\'t forget to run "composer dump-autoload" to update the class loader.');
        }
    }
}
