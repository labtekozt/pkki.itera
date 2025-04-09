<?php

namespace App\Console\Commands;

use App\Services\SubmissionTypeService;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class CreateSubmissionTypeCommand extends Command
{
    protected $signature = 'pkki:create-submission-type
                            {name : The name of the submission type}
                            {--description= : A description of the submission type}
                            {--config= : Path to a JSON configuration file}';

    protected $description = 'Create a new submission type with stages and requirements';

    public function handle(SubmissionTypeService $submissionTypeService)
    {
        $name = $this->argument('name');
        $description = $this->option('description');
        $configPath = $this->option('config');
        
        $typeData = [
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => $description ?? "Submission type for {$name}",
        ];
        
        $stages = [];
        $requirements = [];
        
        // Load from config file if provided
        if ($configPath && file_exists($configPath)) {
            $config = json_decode(file_get_contents($configPath), true);
            
            if (json_last_error() === JSON_ERROR_NONE) {
                $typeData = array_merge($typeData, $config['type'] ?? []);
                $stages = $config['stages'] ?? [];
                $requirements = $config['requirements'] ?? [];
            } else {
                $this->error('Invalid JSON configuration file.');
                return 1;
            }
        } else {
            // Interactive mode
            if ($this->confirm('Would you like to add document requirements interactively?', true)) {
                $requirements = $this->collectRequirements();
            }
            
            if ($this->confirm('Would you like to add workflow stages interactively?', true)) {
                $stages = $this->collectStages($requirements);
            }
        }
        
        try {
            $submissionType = $submissionTypeService->createSubmissionType(
                $typeData, 
                $stages, 
                $requirements
            );
            
            $this->info("Submission type '{$submissionType->name}' created successfully!");
            $this->info("Document Requirements: " . $submissionType->documentRequirements->count());
            $this->info("Workflow Stages: " . $submissionType->workflowStages->count());
            
            return 0;
        } catch (\Exception $e) {
            $this->error("Error: " . $e->getMessage());
            return 1;
        }
    }
    
    protected function collectRequirements(): array
    {
        $requirements = [];
        $continue = true;
        $order = 1;
        
        while ($continue) {
            $name = $this->ask("Requirement name");
            $code = $this->ask("Requirement code", Str::slug($name, '_'));
            $description = $this->ask("Description (optional)");
            $required = $this->confirm("Is this requirement mandatory?", true);
            
            $requirements[] = [
                'name' => $name,
                'code' => $code,
                'description' => $description,
                'required' => $required,
                'order' => $order++,
            ];
            
            $continue = $this->confirm("Add another requirement?", false);
        }
        
        return $requirements;
    }
    
    protected function collectStages(array $requirements): array
    {
        $stages = [];
        $continue = true;
        $order = 1;
        
        while ($continue) {
            $name = $this->ask("Stage name");
            $code = $this->ask("Stage code", Str::slug($name, '_'));
            $description = $this->ask("Description (optional)");
            
            $stageRequirements = [];
            
            if (count($requirements) > 0 && $this->confirm("Attach requirements to this stage?", true)) {
                $reqCodes = collect($requirements)->pluck('code', 'name')->toArray();
                
                $selectedReqs = $this->choice(
                    "Select requirements for this stage (comma separated names)",
                    array_keys($reqCodes),
                    null,
                    null,
                    true
                );
                
                foreach ($selectedReqs as $index => $reqName) {
                    $stageRequirements[] = [
                        'code' => $reqCodes[$reqName],
                        'is_required' => $this->confirm("Is '{$reqName}' mandatory in this stage?", true),
                        'order' => $index + 1,
                    ];
                }
            }
            
            $stages[] = [
                'name' => $name,
                'code' => $code,
                'description' => $description,
                'order' => $order++,
                'requirements' => $stageRequirements,
            ];
            
            $continue = $this->confirm("Add another stage?", false);
        }
        
        return $stages;
    }
}
