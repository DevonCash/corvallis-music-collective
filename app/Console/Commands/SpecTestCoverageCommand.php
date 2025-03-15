<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\Exception\ParseException;

class SpecTestCoverageCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'spec:test-coverage 
                            {module? : The specific module to analyze (optional)}
                            {--output=table : Output format (table, json, csv)}
                            {--uncovered : Show only uncovered requirements}
                            {--interactive : Run in interactive mode with prompts}
                            {--min-coverage=0 : Minimum required coverage percentage}
                            {--dir= : Filter by specific directory within the module}
                            {--spec-file= : Filter by specific specification file}
                            {--type= : Filter by requirement type (mandatory, recommended, optional)}
                            {--format= : Filter by specification format (yaml, markdown)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Parse specifications and compare them against test coverage';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $module = $this->argument('module');
        $outputFormat = $this->option('output');
        $onlyUncovered = $this->option('uncovered');
        $interactive = $this->option('interactive');
        $minCoverage = (float) $this->option('min-coverage');
        $dirFilter = $this->option('dir');
        $specFileFilter = $this->option('spec-file');
        $typeFilter = $this->option('type');
        $formatFilter = $this->option('format');

        // Interactive mode
        if ($interactive) {
            $modulePaths = $this->getAvailableModules();
            if (empty($modulePaths)) {
                $this->error('No modules found to analyze.');
                return 1;
            }
            
            $moduleChoices = array_map('basename', $modulePaths);
            $moduleChoices[] = 'all';
            
            $selectedModule = $this->choice(
                'Which module would you like to analyze?',
                $moduleChoices,
                'all'
            );
            
            if ($selectedModule !== 'all') {
                $module = $selectedModule;
                
                // If a specific module is selected, offer directory filtering
                $modulePath = base_path("app-modules/{$module}");
                if (File::exists($modulePath)) {
                    $dirs = $this->getSubdirectories($modulePath);
                    if (!empty($dirs)) {
                        $dirChoices = array_map(function($dir) use ($modulePath) {
                            return str_replace($modulePath . '/', '', $dir);
                        }, $dirs);
                        array_unshift($dirChoices, 'all');
                        
                        $selectedDir = $this->choice(
                            'Filter by directory?',
                            $dirChoices,
                            'all'
                        );
                        
                        if ($selectedDir !== 'all') {
                            $dirFilter = $selectedDir;
                        }
                    }
                    
                    // Offer spec file filtering
                    $specFiles = $this->findSpecFiles($modulePath);
                    if (count($specFiles) > 1) {
                        $specChoices = array_map(function($spec) {
                            return basename($spec['path']);
                        }, $specFiles);
                        array_unshift($specChoices, 'all');
                        
                        $selectedSpec = $this->choice(
                            'Filter by specification file?',
                            $specChoices,
                            'all'
                        );
                        
                        if ($selectedSpec !== 'all') {
                            $specFileFilter = $selectedSpec;
                        }
                    }
                }
            }
            
            // Offer requirement type filtering
            $typeChoices = ['all', 'mandatory', 'recommended', 'optional'];
            $selectedType = $this->choice(
                'Filter by requirement type?',
                $typeChoices,
                'all'
            );
            
            if ($selectedType !== 'all') {
                $typeFilter = $selectedType;
            }
            
            // Offer format filtering
            $formatChoices = ['all', 'yaml', 'markdown'];
            $selectedFormat = $this->choice(
                'Filter by specification format?',
                $formatChoices,
                'all'
            );
            
            if ($selectedFormat !== 'all') {
                $formatFilter = $selectedFormat;
            }
            
            $outputFormat = $this->choice(
                'Select output format',
                ['table', 'json', 'csv'],
                'table'
            );
            
            $onlyUncovered = $this->confirm('Show only uncovered requirements?', false);
        }

        // Determine which modules to analyze
        $modulePaths = $this->getModulePaths($module);
        
        if (empty($modulePaths)) {
            $this->error('No modules found to analyze.');
            return 1;
        }

        $results = [];
        $totalRequirements = 0;
        $totalCovered = 0;

        $this->newLine();
        $this->components->info('Starting specification analysis');
        $this->newLine();
        
        $bar = $this->output->createProgressBar(count($modulePaths));
        $bar->start();

        foreach ($modulePaths as $modulePath) {
            $moduleName = basename($modulePath);
            
            // Parse specifications
            $specFiles = $this->findSpecFiles($modulePath);
            
            // Apply spec file filter if specified
            if ($specFileFilter) {
                $specFiles = array_filter($specFiles, function($spec) use ($specFileFilter) {
                    return basename($spec['path']) === $specFileFilter;
                });
            }
            
            $requirements = $this->parseSpecifications($specFiles);
            
            // Find test files
            $testFiles = $this->findTestFiles($modulePath, $dirFilter);
            $testCoverage = $this->parseTestCoverage($testFiles);
            
            // Compare requirements with test coverage
            $moduleResults = $this->compareRequirementsWithCoverage($requirements, $testCoverage);
            
            // Apply filters
            if ($typeFilter) {
                $moduleResults = array_filter($moduleResults, function($item) use ($typeFilter) {
                    return $item['type'] === $typeFilter;
                });
            }
            
            if ($formatFilter) {
                $moduleResults = array_filter($moduleResults, function($item) use ($formatFilter) {
                    return $item['format'] === $formatFilter;
                });
            }
            
            $moduleTotal = count($moduleResults);
            $moduleCovered = count(array_filter($moduleResults, fn($item) => $item['covered']));
            
            $totalRequirements += $moduleTotal;
            $totalCovered += $moduleCovered;
            
            $results[$moduleName] = $moduleResults;
            $bar->advance();
        }
        
        $bar->finish();
        $this->newLine(2);
        
        // Output results
        $this->outputResults($results, $outputFormat, $onlyUncovered);
        
        // Summary
        $coveragePercentage = $totalRequirements > 0 
            ? round(($totalCovered / $totalRequirements) * 100, 2) 
            : 0;
            
        $this->newLine();
        $this->components->info("Test Coverage Summary");
        
        $summaryItems = [
            "Total Requirements: {$totalRequirements}",
            "Requirements Covered: {$totalCovered}",
            "Coverage Percentage: {$coveragePercentage}%"
        ];
        
        // Add filter information to summary
        if ($dirFilter) {
            $summaryItems[] = "Directory Filter: {$dirFilter}";
        }
        
        if ($specFileFilter) {
            $summaryItems[] = "Spec File Filter: {$specFileFilter}";
        }
        
        if ($typeFilter) {
            $summaryItems[] = "Type Filter: {$typeFilter}";
        }
        
        if ($formatFilter) {
            $summaryItems[] = "Format Filter: {$formatFilter}";
        }
        
        $this->components->bulletList($summaryItems);
        
        // Check against minimum coverage requirement
        if ($minCoverage > 0) {
            if ($coveragePercentage < $minCoverage) {
                $this->newLine();
                $this->components->error("Coverage is below the minimum required threshold of {$minCoverage}%");
                return 1;
            } else {
                $this->newLine();
                $this->components->info("Coverage meets or exceeds the minimum threshold of {$minCoverage}%");
            }
        }
        
        return 0;
    }
    
    /**
     * Get the paths of modules to analyze.
     */
    private function getModulePaths(?string $specificModule): array
    {
        $basePath = base_path('app-modules');
        
        if (!File::exists($basePath)) {
            return [];
        }
        
        if ($specificModule) {
            $modulePath = "{$basePath}/{$specificModule}";
            return File::exists($modulePath) ? [$modulePath] : [];
        }
        
        return collect(File::directories($basePath))
            ->filter(function ($path) {
                return File::exists("{$path}/tests") || 
                       File::exists("{$path}/" . basename($path) . ".spec.md") ||
                       File::exists("{$path}/" . basename($path) . ".spec.yaml") ||
                       File::exists("{$path}/" . basename($path) . ".spec.yml");
            })
            ->toArray();
    }
    
    /**
     * Find specification files in the given module path.
     */
    private function findSpecFiles(string $modulePath): array
    {
        $moduleName = basename($modulePath);
        $specFiles = [];
        
        // Check for module-name.spec.yaml/yml pattern first (prioritize YAML)
        $yamlSpecFile = "{$modulePath}/{$moduleName}.spec.yaml";
        $ymlSpecFile = "{$modulePath}/{$moduleName}.spec.yml";
        $mdSpecFile = "{$modulePath}/{$moduleName}.spec.md";
        
        if (File::exists($yamlSpecFile)) {
            $specFiles[] = ['path' => $yamlSpecFile, 'type' => 'yaml'];
            // If we find a YAML file, we'll use that instead of the Markdown file
            return $specFiles;
        } elseif (File::exists($ymlSpecFile)) {
            $specFiles[] = ['path' => $ymlSpecFile, 'type' => 'yaml'];
            // If we find a YAML file, we'll use that instead of the Markdown file
            return $specFiles;
        } elseif (File::exists($mdSpecFile)) {
            $specFiles[] = ['path' => $mdSpecFile, 'type' => 'markdown'];
        }
        
        // Check for any .spec.* files in the module directory
        $finder = new Finder();
        $finder->files()->name('*.spec.yaml')->name('*.spec.yml')->name('*.spec.md')->in($modulePath)->depth('< 2');
        
        foreach ($finder as $file) {
            $path = $file->getRealPath();
            $type = $this->getSpecFileType($path);
            
            $fileInfo = ['path' => $path, 'type' => $type];
            if (!in_array($fileInfo, $specFiles)) {
                $specFiles[] = $fileInfo;
            }
        }
        
        return $specFiles;
    }
    
    /**
     * Determine the type of specification file.
     */
    private function getSpecFileType(string $path): string
    {
        $extension = pathinfo($path, PATHINFO_EXTENSION);
        return in_array($extension, ['yaml', 'yml']) ? 'yaml' : 'markdown';
    }
    
    /**
     * Parse specification files to extract requirements.
     */
    private function parseSpecifications(array $specFiles): array
    {
        $requirements = [];
        
        foreach ($specFiles as $specFile) {
            $path = $specFile['path'];
            $type = $specFile['type'];
            
            if ($type === 'yaml') {
                $requirements = array_merge($requirements, $this->parseYamlSpecification($path));
            } else {
                $requirements = array_merge($requirements, $this->parseMarkdownSpecification($path));
            }
        }
        
        return $requirements;
    }
    
    /**
     * Parse YAML specification file.
     */
    private function parseYamlSpecification(string $path): array
    {
        $requirements = [];
        
        try {
            $content = File::get($path);
            $yaml = Yaml::parse($content);
            
            // Process requirements section
            if (isset($yaml['requirements']) && is_array($yaml['requirements'])) {
                foreach ($yaml['requirements'] as $req) {
                    if (isset($req['id']) && preg_match('/REQ-\d+/', $req['id'])) {
                        $reqId = $req['id'];
                        $description = $req['description'] ?? 'No description available';
                        
                        // Determine requirement type from the type field or from the description
                        $type = $req['type'] ?? 'unknown';
                        if ($type === 'unknown' && isset($req['description'])) {
                            if (preg_match('/shall/i', $req['description'])) {
                                $type = 'mandatory';
                            } elseif (preg_match('/should/i', $req['description'])) {
                                $type = 'recommended';
                            } elseif (preg_match('/may/i', $req['description'])) {
                                $type = 'optional';
                            }
                        }
                        
                        $requirements[$reqId] = [
                            'id' => $reqId,
                            'description' => $description,
                            'type' => $type,
                            'source' => basename($path),
                            'format' => 'yaml',
                            'priority' => $req['priority'] ?? 'Medium',
                            'acceptance_criteria' => $req['acceptance_criteria'] ?? '',
                            'tests' => $req['tests'] ?? [],
                            'related_requirements' => $req['related_requirements'] ?? [],
                        ];
                    }
                }
            }
            
            // Process interfaces section
            if (isset($yaml['interfaces']) && is_array($yaml['interfaces'])) {
                foreach ($yaml['interfaces'] as $interface) {
                    if (isset($interface['id']) && preg_match('/INT-\d+/', $interface['id'])) {
                        $intId = $interface['id'];
                        $description = $interface['description'] ?? 'No description available';
                        
                        $requirements[$intId] = [
                            'id' => $intId,
                            'description' => $description,
                            'type' => $interface['type'] ?? 'mandatory',
                            'source' => basename($path),
                            'format' => 'yaml',
                            'is_interface' => true,
                            'interface_source' => $interface['source'] ?? '',
                            'interface_destination' => $interface['destination'] ?? '',
                            'tests' => $interface['tests'] ?? [],
                        ];
                    }
                }
            }
        } catch (ParseException $e) {
            $this->error("Error parsing YAML file {$path}: " . $e->getMessage());
        }
        
        return $requirements;
    }
    
    /**
     * Parse Markdown specification file.
     */
    private function parseMarkdownSpecification(string $path): array
    {
        $requirements = [];
        $content = File::get($path);
        $lines = explode("\n", $content);
        
        // First pass: find all requirement definitions
        foreach ($lines as $lineNum => $line) {
            // Match requirement IDs in the format REQ-XXX
            // This pattern matches both formats:
            // - **REQ-001**: The system shall...
            // - REQ-001: The system shall...
            if (preg_match('/- (?:\*\*)?(REQ-\d+)(?:\*\*)?:?\s*(.+)/', $line, $matches)) {
                $reqId = $matches[1];
                $description = trim($matches[2]);
                
                // Determine requirement type (shall, should, may)
                $type = 'unknown';
                if (preg_match('/shall/i', $description)) {
                    $type = 'mandatory';
                } elseif (preg_match('/should/i', $description)) {
                    $type = 'recommended';
                } elseif (preg_match('/may/i', $description)) {
                    $type = 'optional';
                }
                
                $requirements[$reqId] = [
                    'id' => $reqId,
                    'description' => $description,
                    'type' => $type,
                    'source' => basename($path),
                    'format' => 'markdown',
                    'line_number' => $lineNum + 1,
                ];
                
                // Look ahead for priority and acceptance criteria
                if (isset($lines[$lineNum + 1]) && preg_match('/\*\*Priority\*\*:\s*(.+)/', $lines[$lineNum + 1], $priorityMatch)) {
                    $requirements[$reqId]['priority'] = trim($priorityMatch[1]);
                }
                
                if (isset($lines[$lineNum + 2]) && preg_match('/\*\*Acceptance Criteria\*\*:\s*(.+)/', $lines[$lineNum + 2], $acMatch)) {
                    $requirements[$reqId]['acceptance_criteria'] = trim($acMatch[1]);
                }
            }
        }
        
        // Second pass: find traceability matrix entries and related requirements
        foreach ($lines as $lineNum => $line) {
            // Check for traceability matrix entries (pattern: "- **REQ-001**:")
            if (preg_match('/^\s*-\s+\*\*(REQ-\d+)\*\*:$/', $line, $matches)) {
                $reqId = $matches[1];
                
                // If this requirement doesn't exist yet, create it
                if (!isset($requirements[$reqId])) {
                    // Look for the original requirement in the file
                    foreach ($lines as $searchLine) {
                        if (preg_match('/- (?:\*\*)?(REQ-\d+)(?:\*\*)?:?\s*(.+)/', $searchLine, $searchMatches) && 
                            $searchMatches[1] === $reqId) {
                            $description = trim($searchMatches[2]);
                            
                            // Determine requirement type
                            $type = 'unknown';
                            if (preg_match('/shall/i', $description)) {
                                $type = 'mandatory';
                            } elseif (preg_match('/should/i', $description)) {
                                $type = 'recommended';
                            } elseif (preg_match('/may/i', $description)) {
                                $type = 'optional';
                            }
                            
                            $requirements[$reqId] = [
                                'id' => $reqId,
                                'description' => $description,
                                'type' => $type,
                                'source' => basename($path),
                                'format' => 'markdown',
                                'line_number' => $lineNum + 1,
                                'is_traceability_entry' => true,
                            ];
                            break;
                        }
                    }
                    
                    // If still not found, create a placeholder
                    if (!isset($requirements[$reqId])) {
                        $requirements[$reqId] = [
                            'id' => $reqId,
                            'description' => 'Defined in traceability matrix',
                            'type' => 'unknown',
                            'source' => basename($path),
                            'format' => 'markdown',
                            'line_number' => $lineNum + 1,
                            'is_traceability_entry' => true,
                        ];
                    }
                } else {
                    // Mark existing requirement as also being in the traceability matrix
                    $requirements[$reqId]['is_traceability_entry'] = true;
                }
                
                // Look ahead for traceability information
                $i = $lineNum + 1;
                while ($i < count($lines) && !preg_match('/^\s*-\s+\*\*/', $lines[$i]) && trim($lines[$i]) !== '') {
                    if (preg_match('/\*\*(Test ID|Task ID|Interface ID)\*\*:\s*(.+)/', $lines[$i], $traceMatch)) {
                        $key = strtolower(str_replace(' ', '_', $traceMatch[1]));
                        $requirements[$reqId][$key] = trim($traceMatch[2]);
                    }
                    $i++;
                }
            }
            
            // Check for lines that start with "- **Related Requirements**: REQ-XXX, REQ-YYY"
            if (preg_match('/- \*\*Related Requirements\*\*:\s*(.+)/', $line, $matches)) {
                $relatedReqs = preg_split('/[,\s]+/', $matches[1]);
                foreach ($relatedReqs as $relatedReq) {
                    if (preg_match('/REQ-\d+/', $relatedReq)) {
                        // Make sure this requirement exists in our list
                        if (!isset($requirements[$relatedReq])) {
                            // Look for the original requirement in the file
                            foreach ($lines as $searchLine) {
                                if (preg_match('/- (?:\*\*)?(REQ-\d+)(?:\*\*)?:?\s*(.+)/', $searchLine, $searchMatches) && 
                                    $searchMatches[1] === $relatedReq) {
                                    $description = trim($searchMatches[2]);
                                    
                                    // Determine requirement type
                                    $type = 'unknown';
                                    if (preg_match('/shall/i', $description)) {
                                        $type = 'mandatory';
                                    } elseif (preg_match('/should/i', $description)) {
                                        $type = 'recommended';
                                    } elseif (preg_match('/may/i', $description)) {
                                        $type = 'optional';
                                    }
                                    
                                    $requirements[$relatedReq] = [
                                        'id' => $relatedReq,
                                        'description' => $description,
                                        'type' => $type,
                                        'source' => basename($path),
                                        'format' => 'markdown',
                                        'is_reference_only' => true,
                                    ];
                                    break;
                                }
                            }
                            
                            // If still not found, create a placeholder
                            if (!isset($requirements[$relatedReq])) {
                                $requirements[$relatedReq] = [
                                    'id' => $relatedReq,
                                    'description' => 'Referenced in related requirements',
                                    'type' => 'unknown',
                                    'source' => basename($path),
                                    'format' => 'markdown',
                                    'is_reference_only' => true,
                                ];
                            }
                        }
                    }
                }
            }
        }
        
        return $requirements;
    }
    
    /**
     * Find test files in the given module path.
     */
    private function findTestFiles(string $modulePath, ?string $dirFilter = null): array
    {
        $testPath = "{$modulePath}/tests";
        
        if (!File::exists($testPath)) {
            return [];
        }
        
        $finder = new Finder();
        $finder->files()->name('*.php');
        
        // Apply directory filter if specified
        if ($dirFilter) {
            $dirPath = "{$testPath}/{$dirFilter}";
            if (File::exists($dirPath)) {
                $finder->in($dirPath);
            } else {
                // Try to find the directory anywhere in the module
                $dirPath = "{$modulePath}/{$dirFilter}";
                if (File::exists($dirPath)) {
                    $finder->in($dirPath);
                } else {
                    // If directory not found, return empty array
                    return [];
                }
            }
        } else {
            $finder->in($testPath);
        }
        
        $testFiles = [];
        foreach ($finder as $file) {
            $testFiles[] = $file->getRealPath();
        }
        
        return $testFiles;
    }
    
    /**
     * Parse test files to extract coverage information.
     */
    private function parseTestCoverage(array $testFiles): array
    {
        $coverage = [];
        
        foreach ($testFiles as $testFile) {
            $content = File::get($testFile);
            
            // Find all test methods with @covers annotations
            // This regex matches both formats:
            // 1. @test on one line, @covers on the next
            // 2. Multiple @covers annotations for the same test
            preg_match_all('/\/\*\*\s*\n(?:.*\n)*?\s*\*\s*@test\s*\n(?:.*\n)*?\s*\*\s*@covers\s+([^*\n]+)/', $content, $matches, PREG_SET_ORDER);
            
            foreach ($matches as $match) {
                $coveredReqs = explode(',', $match[1]);
                
                foreach ($coveredReqs as $req) {
                    $reqId = trim($req);
                    
                    if (!isset($coverage[$reqId])) {
                        $coverage[$reqId] = [];
                    }
                    
                    $coverage[$reqId][] = [
                        'file' => basename($testFile),
                        'path' => $testFile,
                    ];
                }
            }
        }
        
        return $coverage;
    }
    
    /**
     * Compare requirements with test coverage.
     */
    private function compareRequirementsWithCoverage(array $requirements, array $testCoverage): array
    {
        $results = [];
        
        foreach ($requirements as $reqId => $requirement) {
            $covered = isset($testCoverage[$reqId]) && !empty($testCoverage[$reqId]);
            $testCount = $covered ? count($testCoverage[$reqId]) : 0;
            
            // For YAML specs, check if tests are defined in the spec
            $specTests = [];
            if (isset($requirement['tests']) && is_array($requirement['tests'])) {
                $specTests = $requirement['tests'];
            }
            
            $results[$reqId] = [
                'id' => $reqId,
                'description' => $requirement['description'],
                'type' => $requirement['type'],
                'covered' => $covered,
                'test_count' => $testCount,
                'tests' => $covered ? $testCoverage[$reqId] : [],
                'spec_tests' => $specTests,
                'source' => $requirement['source'],
                'format' => $requirement['format'] ?? 'markdown',
            ];
            
            // Copy additional properties
            foreach ($requirement as $key => $value) {
                if (!isset($results[$reqId][$key])) {
                    $results[$reqId][$key] = $value;
                }
            }
        }
        
        return $results;
    }
    
    /**
     * Output the results in the specified format.
     */
    private function outputResults(array $results, string $format, bool $onlyUncovered): void
    {
        switch ($format) {
            case 'json':
                $this->outputJson($results, $onlyUncovered);
                break;
                
            case 'csv':
                $this->outputCsv($results, $onlyUncovered);
                break;
                
            case 'table':
            default:
                $this->outputTable($results, $onlyUncovered);
                break;
        }
    }
    
    /**
     * Output results in table format.
     */
    private function outputTable(array $results, bool $onlyUncovered): void
    {
        foreach ($results as $moduleName => $moduleResults) {
            if ($onlyUncovered) {
                $moduleResults = array_filter($moduleResults, fn($item) => !$item['covered']);
            }
            
            if (empty($moduleResults)) {
                continue;
            }
            
            $this->components->twoColumnDetail("<fg=blue;options=bold>{$moduleName} Module Requirements</>", 
                count($moduleResults) . " requirements found");
            $this->newLine();
            
            $tableRows = [];
            foreach ($moduleResults as $result) {
                $status = $result['covered'] ? '<fg=green>Covered</>' : '<fg=red>Not Covered</>';
                
                // If this is a reference-only requirement, mark it differently
                $type = $result['type'];
                if (isset($result['is_reference_only']) && $result['is_reference_only']) {
                    $type = '<fg=yellow>Referenced Only</>';
                } else if (isset($result['is_traceability_entry']) && $result['is_traceability_entry']) {
                    $type = '<fg=blue>Traceability Entry</>';
                } else if (isset($result['is_interface']) && $result['is_interface']) {
                    $type = '<fg=magenta>Interface</>';
                }
                
                // Format description
                $description = $result['description'];
                if ($description === ':' || empty(trim($description))) {
                    if (isset($result['is_traceability_entry']) && $result['is_traceability_entry']) {
                        $description = 'Defined in traceability matrix';
                        if (isset($result['test_id'])) {
                            $description .= " (Test: {$result['test_id']})";
                        }
                    } else {
                        $description = 'No description available';
                    }
                }
                
                // Add format indicator
                $format = isset($result['format']) ? strtoupper($result['format'][0]) : 'M';
                
                $tableRows[] = [
                    $result['id'],
                    $type,
                    substr($description, 0, 60) . (strlen($description) > 60 ? '...' : ''),
                    $status,
                    $result['test_count'],
                    $format,
                ];
            }
            
            $this->table(
                ['Requirement ID', 'Type', 'Description', 'Status', 'Test Count', 'Format'],
                $tableRows
            );
        }
    }
    
    /**
     * Output results in JSON format.
     */
    private function outputJson(array $results, bool $onlyUncovered): void
    {
        if ($onlyUncovered) {
            foreach ($results as $moduleName => $moduleResults) {
                $results[$moduleName] = array_filter($moduleResults, fn($item) => !$item['covered']);
            }
        }
        
        $this->line(json_encode($results, JSON_PRETTY_PRINT));
    }
    
    /**
     * Output results in CSV format.
     */
    private function outputCsv(array $results, bool $onlyUncovered): void
    {
        $this->line('Module,Requirement ID,Type,Description,Status,Test Count,Test Files,Format');
        
        foreach ($results as $moduleName => $moduleResults) {
            if ($onlyUncovered) {
                $moduleResults = array_filter($moduleResults, fn($item) => !$item['covered']);
            }
            
            foreach ($moduleResults as $result) {
                $status = $result['covered'] ? 'Covered' : 'Not Covered';
                $testFiles = implode('|', array_column($result['tests'], 'file'));
                
                // Escape CSV fields
                $description = str_replace('"', '""', $result['description']);
                $format = $result['format'] ?? 'markdown';
                
                $this->line(sprintf(
                    '%s,"%s","%s","%s","%s",%d,"%s","%s"',
                    $moduleName,
                    $result['id'],
                    $result['type'],
                    $description,
                    $status,
                    $result['test_count'],
                    $testFiles,
                    $format
                ));
            }
        }
    }

    /**
     * Get a list of all available modules.
     */
    private function getAvailableModules(): array
    {
        $basePath = base_path('app-modules');
        
        if (!File::exists($basePath)) {
            return [];
        }
        
        return collect(File::directories($basePath))
            ->filter(function ($path) {
                return File::exists("{$path}/tests") || 
                       File::exists("{$path}/" . basename($path) . ".spec.md") ||
                       File::exists("{$path}/" . basename($path) . ".spec.yaml") ||
                       File::exists("{$path}/" . basename($path) . ".spec.yml");
            })
            ->toArray();
    }

    /**
     * Get subdirectories of a given path.
     */
    private function getSubdirectories(string $path): array
    {
        if (!File::exists($path)) {
            return [];
        }
        
        return collect(File::directories($path))
            ->filter(function ($dir) {
                return !in_array(basename($dir), ['.git', 'vendor', 'node_modules']);
            })
            ->toArray();
    }
} 