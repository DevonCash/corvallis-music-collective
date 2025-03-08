<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Permission;
class SyncPermissions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'permissions:sync';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync all module permissions';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Syncing permissions from all modules...');

        // First, ensure all policies are registered
        $this->registerPolicies();

        foreach (Gate::policies() as $model => $policy) {
            $this->info("Policy for {$model}: {$policy}");

            if(!method_exists($policy, 'PERMISSIONS')) continue;
            $permissions = $policy::PERMISSIONS;

            foreach ($permissions as $permission) {
                Permission::firstOrCreate(['name' => $permission]);
            }
        }

        $this->info('All permissions have been synced!');
    }

    /**
     * Get all module service providers
     */
    private function getModuleServiceProviders(): array
    {
        $providers = [];
        
        // Scan the Modules directory for service providers
        $modulesPath = app_path('Modules');
        
        if (File::exists($modulesPath)) {
            foreach (File::directories($modulesPath) as $moduleDir) {
                $moduleName = basename($moduleDir);
                $providerClass = "App\\Modules\\{$moduleName}\\Providers\\{$moduleName}ServiceProvider";
                
                if (class_exists($providerClass) && is_subclass_of($providerClass, ModuleServiceProvider::class)) {
                    $providers[] = $providerClass;
                }
            }
        }

        return $providers;
    }

    /**
     * Register all policies from the Modules directory
     */
    private function registerPolicies(): void
    {
        $modulesPath = app_path('Modules');
        
        if (File::exists($modulesPath)) {
            foreach (File::directories($modulesPath) as $moduleDir) {
                $policiesDir = $moduleDir . '/Policies';
                
                if (File::exists($policiesDir)) {
                    foreach (File::allFiles($policiesDir) as $file) {
                        $policyClass = sprintf(
                            'App\\Modules\\%s\\Policies\\%s',
                            basename($moduleDir),
                            $file->getBasename('.php')
                        );

                        if (class_exists($policyClass)) {
                            // Get the model class from the policy name
                            $modelClass = $this->getModelClassFromPolicy($policyClass);
                            if (class_exists($modelClass)) {
                                $this->info("Registering policy {$policyClass} for model {$modelClass}");
                                Gate::policy($modelClass, $policyClass);
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * Get the model class name from a policy class
     */
    private function getModelClassFromPolicy(string $policyClass): string
    {
        // Remove 'Policy' from the end and get the base name
        $modelName = Str::beforeLast(class_basename($policyClass), 'Policy');
        
        // Try different common model locations
        $possibilities = [
            "App\\Modules\\{$modelName}\\Models\\{$modelName}",
            "App\\Models\\{$modelName}",
            "App\\{$modelName}",
            "Spatie\\Permission\\Models\\{$modelName}" // For Role and Permission models
        ];

        foreach ($possibilities as $class) {
            if (class_exists($class)) {
                return $class;
            }
        }

        return "App\\Models\\{$modelName}"; // Return a default
    }
} 