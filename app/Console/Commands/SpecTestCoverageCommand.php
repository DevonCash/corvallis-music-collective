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
                            {path? : Path to analyze (defaults to current directory)}
                            {--output=table : Output format (table, json, csv)}
                            {--uncovered : Show only uncovered requirements}
                            {--interactive : Run in interactive mode with prompts}
                            {--min-coverage=0 : Minimum required coverage percentage}
                            {--spec-file= : Filter by specific specification file}
                            {--type= : Filter by requirement type (mandatory, recommended, optional)}
                            {--min-priority= : Filter by minimum priority (High, Medium, Low)}
                            {--depth=5 : Maximum directory depth for finding spec and test files}';

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
        $path = $this->argument('path');
        $outputFormat = $this->option('output');
        $onlyUncovered = $this->option('uncovered');
        $interactive = $this->option('interactive');
        $minCoverage = (float) $this->option('min-coverage');
        $specFileFilter = $this->option('spec-file');
        $typeFilter = $this->option('type');
        $minPriorityFilter = $this->option('min-priority');
        $depth = (int) $this->option('depth');

        // If no path provided, use current directory
        if (!$path) {
            $path = getcwd();
        } elseif (!str_starts_with($path, '/')) {
            // If relative path, make it absolute from current directory
            $path = getcwd() . '/' . $path;
        }

        // Find all specification files
        $allSpecFiles = $this->findAllSpecFiles($path, $depth);
        
        if (empty($allSpecFiles)) {
            $this->error("No specification files found in {$path}");
            return 1;
        }
        
        // Interactive mode
        $selectedSpecFiles = $allSpecFiles;
        if ($interactive) {
            $specsMap = [];
            foreach ($allSpecFiles as $index => $specFile) {
                $relativePath = $this->getRelativePath($specFile['path'], $path);
                $specsMap[$index] = $relativePath;
            }
            
            // Allow selection of spec files
            $this->info("Found " . count($specsMap) . " specification files.");
            
            // If more than 10 files, ask if user wants to select specific ones
            if (count($specsMap) > 1) {
                if ($this->confirm('Would you like to select specific specification files to analyze?', false)) {
                    $selectedIndices = $this->choice(
                        'Select specification files to analyze (comma-separated list):',
                        $specsMap,
                        null,
                        null,
                        true
                    );
                    
                    $selectedSpecFiles = [];
                    foreach ($selectedIndices as $index) {
                        $selectedSpecFiles[] = $allSpecFiles[$index];
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
            
            // Offer priority filtering
            $priorityChoices = ['all', 'High', 'Medium', 'Low'];
            $selectedPriority = $this->choice(
                'Filter by minimum priority?',
                $priorityChoices,
                'all'
            );
            
            if ($selectedPriority !== 'all') {
                $minPriorityFilter = $selectedPriority;
            }
            
            $outputFormat = $this->choice(
                'Select output format',
                ['table', 'json', 'csv'],
                'table'
            );
            
            $onlyUncovered = $this->confirm('Show only uncovered requirements?', false);
        }
        
        // Filter by specific spec file if requested
        if ($specFileFilter) {
            $selectedSpecFiles = array_filter($selectedSpecFiles, function($spec) use ($specFileFilter) {
                return basename($spec['path']) === $specFileFilter;
            });
        }
        
        if (empty($selectedSpecFiles)) {
            $this->error('No specification files match the given filters.');
            return 1;
        }

        $results = [];
        $totalRequirements = 0;
        $totalCovered = 0;
        
        $this->newLine();
        $this->components->info('Starting specification analysis');
        $this->newLine();
        
        $bar = $this->output->createProgressBar(count($selectedSpecFiles));
        $bar->start();
        
        // Group spec files by directory for cleaner output
        $specsByDir = [];
        foreach ($selectedSpecFiles as $spec) {
            $dir = dirname($spec['path']);
            $dirName = basename($dir);
            
            if (!isset($specsByDir[$dirName])) {
                $specsByDir[$dirName] = [
                    'specs' => [],
                    'path' => $dir
                ];
            }
            
            $specsByDir[$dirName]['specs'][] = $spec;
        }
        
        // Process each directory's spec files
        foreach ($specsByDir as $dirName => $dirData) {
            $specs = $dirData['specs'];
            $dirPath = $dirData['path'];
            
            // Parse specifications
            $requirements = $this->parseSpecifications($specs);
            
            // Find test files in the same directory and parent
            $testFiles = $this->findAllTestFiles($dirPath, $depth);
            $testCoverage = $this->parseTestCoverage($testFiles);
            
            // Compare requirements with test coverage
            $dirResults = $this->compareRequirementsWithCoverage($requirements, $testCoverage);
            
            // Apply filters
            if ($typeFilter) {
                $dirResults = array_filter($dirResults, function($item) use ($typeFilter) {
                    return $item['type'] === $typeFilter;
                });
            }
            
            // Add priority filter
            if ($minPriorityFilter) {
                $priorityRanking = [
                    'High' => 3,
                    'Medium' => 2,
                    'Low' => 1
                ];
                
                $minPriorityRank = $priorityRanking[$minPriorityFilter] ?? 1;
                
                $dirResults = array_filter($dirResults, function($item) use ($priorityRanking, $minPriorityRank) {
                    // If no priority is set, default to Low
                    $itemPriority = isset($item['priority']) ? $item['priority'] : 'Low';
                    $itemPriorityRank = $priorityRanking[$itemPriority] ?? 1;
                    
                    return $itemPriorityRank >= $minPriorityRank;
                });
            }
            
            $dirTotal = count($dirResults);
            $dirCovered = count(array_filter($dirResults, fn($item) => $item['covered']));
            
            $totalRequirements += $dirTotal;
            $totalCovered += $dirCovered;
            
            if (!empty($dirResults)) {
                $results[$dirName] = $dirResults;
            }
            
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
        if ($specFileFilter) {
            $summaryItems[] = "Spec File Filter: {$specFileFilter}";
        }
        
        if ($typeFilter) {
            $summaryItems[] = "Type Filter: {$typeFilter}";
        }
        
        if ($minPriorityFilter) {
            $summaryItems[] = "Minimum Priority Filter: {$minPriorityFilter}";
        }
        
        $this->components->bulletList($summaryItems);
        
        // Check against minimum coverage requirement
        if ($minCoverage > 0) {
            if ($coveragePercentage < $minCoverage) {
                $this->error("Coverage requirement not met: {$coveragePercentage}% < {$minCoverage}%");
                return 1;
            } else {
                $this->info("Coverage requirement met: {$coveragePercentage}% >= {$minCoverage}%");
            }
        }
        
        return 0;
    }
    
    /**
     * Get the relative path from a base path.
     */
    private function getRelativePath(string $path, string $basePath): string
    {
        // Ensure paths are normalized
        $path = realpath($path);
        $basePath = realpath($basePath);
        
        if (str_starts_with($path, $basePath)) {
            $relativePath = substr($path, strlen($basePath));
            return ltrim($relativePath, '/');
        }
        
        return $path;
    }
    
    /**
     * Find all specification files in the given path and subdirectories.
     */
    private function findAllSpecFiles(string $path, int $depth = 5): array
    {
        $specFiles = [];
        
        try {
            $finder = new Finder();
            $finder->files()
                ->name('*.spec.yaml')
                ->name('*.spec.yml')
                ->in($path)
                ->depth('< ' . $depth);
            
            foreach ($finder as $file) {
                $specFiles[] = [
                    'path' => $file->getRealPath(),
                    'type' => 'yaml'
                ];
            }
        } catch (\Exception $e) {
            // If the path doesn't exist or isn't readable, just return an empty array
            $this->warn("Error scanning {$path} for spec files: " . $e->getMessage());
        }
        
        return $specFiles;
    }
    
    /**
     * Find all test files in the given path and subdirectories.
     */
    private function findAllTestFiles(string $path, int $depth = 5): array
    {
        $testFiles = [];
        
        try {
            // First look in the path itself and its subdirectories
            $finder = new Finder();
            $finder->files()
                ->name('*Test.php')
                ->in($path)
                ->depth('< ' . $depth);
                
            foreach ($finder as $file) {
                $testFiles[] = $file->getRealPath();
            }
            
            // If a tests directory exists at the same level as the spec directory, look there too
            $testsDir = dirname($path) . '/tests';
            if (is_dir($testsDir) && $testsDir !== $path) {
                try {
                    $testsFinder = new Finder();
                    $testsFinder->files()
                        ->name('*Test.php')
                        ->in($testsDir)
                        ->depth('< ' . $depth);
                        
                    foreach ($testsFinder as $file) {
                        $testFiles[] = $file->getRealPath();
                    }
                } catch (\Exception $e) {
                    // Ignore errors for this directory
                }
            }
        } catch (\Exception $e) {
            // If the path doesn't exist or isn't readable, just return an empty array
            $this->warn("Error scanning for test files: " . $e->getMessage());
        }
        
        return $testFiles;
    }
    
    /**
     * Parse specification files to extract requirements.
     */
    private function parseSpecifications(array $specFiles): array
    {
        $requirements = [];
        
        foreach ($specFiles as $specFile) {
            $path = $specFile['path'];
            $requirements = array_merge($requirements, $this->parseYamlSpecification($path));
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
     * Parse test files to extract coverage information.
     */
    private function parseTestCoverage(array $testFiles): array
    {
        $coverage = [];
        
        foreach ($testFiles as $testFile) {
            $content = File::get($testFile);
            
            // Find @covers annotations in class-level docblocks
            preg_match_all('/\/\*\*\s*\n(?:.*\n)*?\s*\*\s*@covers\s+([^*\n]+)(?:\n.*)*?class\s+(\w+)/', $content, $classMatches, PREG_SET_ORDER);
            
            foreach ($classMatches as $match) {
                $coveredReqs = explode(',', $match[1]);
                
                foreach ($coveredReqs as $req) {
                    $reqId = trim($req);
                    
                    if (!isset($coverage[$reqId])) {
                        $coverage[$reqId] = [];
                    }
                    
                    $coverage[$reqId][] = [
                        'file' => basename($testFile),
                        'path' => $testFile,
                        'class' => $match[2] ?? 'Unknown',
                    ];
                }
            }
            
            // Find @covers annotations in method-level docblocks
            // This matches both formats:
            // 1. @test on one line, @covers on the next
            // 2. Multiple @covers annotations for the same test
            preg_match_all('/\/\*\*\s*\n(?:.*\n)*?\s*\*\s*@test\s*\n(?:.*\n)*?\s*\*\s*@covers\s+([^*\n]+)/', $content, $methodMatches, PREG_SET_ORDER);
            
            foreach ($methodMatches as $match) {
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
            
            // Also find standalone @covers annotations (not requiring @test)
            preg_match_all('/\/\*\*\s*\n(?:.*\n)*?\s*\*\s*@covers\s+([^*\n]+)/', $content, $standaloneMatches, PREG_SET_ORDER);
            
            foreach ($standaloneMatches as $match) {
                // Skip if this is a class docblock (already processed above)
                if (preg_match('/class\s+\w+/', $content, $classMatch, 0, strpos($content, $match[0]) + strlen($match[0]))) {
                    continue;
                }
                
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
                'format' => 'yaml',
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
                
                $tableRows[] = [
                    $result['id'],
                    $type,
                    substr($description, 0, 60) . (strlen($description) > 60 ? '...' : ''),
                    $status,
                    $result['test_count'],
                ];
            }
            
            $this->table(
                ['Requirement ID', 'Type', 'Description', 'Status', 'Test Count'],
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
} 