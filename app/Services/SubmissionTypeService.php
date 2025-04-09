<?php

namespace App\Services;

use App\Models\DocumentRequirement;
use App\Models\SubmissionType;
use App\Models\WorkflowStage;
use App\Models\WorkflowStageRequirement;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SubmissionTypeService
{
    /**
     * Create a new submission type with workflow stages and document requirements.
     *
     * @param array $typeData Basic submission type data (name, description)
     * @param array $stages Workflow stages with their requirements
     * @param array $requirements Document requirements
     * @return SubmissionType
     */
    public function createSubmissionType(array $typeData, array $stages = [], array $requirements = []): SubmissionType
    {
        return DB::transaction(function () use ($typeData, $stages, $requirements) {
            // Create the submission type
            $submissionType = SubmissionType::create([
                'name' => $typeData['name'],
                'slug' => $typeData['slug'] ?? Str::slug($typeData['name']),
                'description' => $typeData['description'] ?? null,
            ]);

            // Create document requirements
            $this->createDocumentRequirements($submissionType, $requirements);

            // Create workflow stages with requirements
            $this->createWorkflowStages($submissionType, $stages);

            return $submissionType->fresh(['documentRequirements', 'workflowStages']);
        });
    }

    /**
     * Create document requirements for a submission type.
     *
     * @param SubmissionType $submissionType
     * @param array $requirements
     * @return array
     */
    protected function createDocumentRequirements(SubmissionType $submissionType, array $requirements): array
    {
        $createdRequirements = [];

        foreach ($requirements as $index => $req) {
            $requirement = $submissionType->documentRequirements()->create([
                'code' => $req['code'] ?? Str::slug($req['name'], '_'),
                'name' => $req['name'],
                'description' => $req['description'] ?? null,
                'required' => $req['required'] ?? true,
                'order' => $req['order'] ?? ($index + 1),
            ]);

            $createdRequirements[] = $requirement;
        }

        return $createdRequirements;
    }

    /**
     * Create workflow stages with requirements for a submission type.
     *
     * @param SubmissionType $submissionType
     * @param array $stages
     * @return array
     */
    protected function createWorkflowStages(SubmissionType $submissionType, array $stages): array
    {
        $createdStages = [];

        foreach ($stages as $index => $stageData) {
            $stage = $submissionType->workflowStages()->create([
                'code' => $stageData['code'] ?? Str::slug($stageData['name'], '_'),
                'name' => $stageData['name'],
                'description' => $stageData['description'] ?? null,
                'order' => $stageData['order'] ?? ($index + 1),
            ]);

            // Create requirements for this stage if provided
            if (isset($stageData['requirements']) && is_array($stageData['requirements'])) {
                $this->attachRequirementsToStage($stage, $stageData['requirements']);
            }

            $createdStages[] = $stage;
        }

        return $createdStages;
    }

    /**
     * Attach requirements to a workflow stage.
     *
     * @param WorkflowStage $stage
     * @param array $requirementCodes
     */
    protected function attachRequirementsToStage(WorkflowStage $stage, array $requirementCodes): void
    {
        $submissionType = $stage->submissionType;
        $requirements = $submissionType->documentRequirements()
            ->whereIn('code', array_column($requirementCodes, 'code'))
            ->get();

        foreach ($requirementCodes as $index => $reqData) {
            $requirement = $requirements->firstWhere('code', $reqData['code']);
            
            if ($requirement) {
                WorkflowStageRequirement::create([
                    'workflow_stage_id' => $stage->id,
                    'document_requirement_id' => $requirement->id,
                    'is_required' => $reqData['is_required'] ?? true,
                    'order' => $reqData['order'] ?? ($index + 1),
                ]);
            }
        }
    }

    /**
     * Get all available submission types.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAllSubmissionTypes()
    {
        return SubmissionType::with(['documentRequirements', 'workflowStages'])->get();
    }

    /**
     * Find a submission type by slug.
     *
     * @param string $slug
     * @return SubmissionType|null
     */
    public function findBySlug(string $slug): ?SubmissionType
    {
        return SubmissionType::where('slug', $slug)
            ->with(['documentRequirements', 'workflowStages.documentRequirements'])
            ->first();
    }

    /**
     * Delete a submission type and all related records.
     *
     * @param string $typeId
     * @return bool
     */
    public function deleteSubmissionType(string $typeId): bool
    {
        return DB::transaction(function () use ($typeId) {
            $type = SubmissionType::findOrFail($typeId);
            
            // Delete related records first
            $type->documentRequirements()->delete();
            
            // Delete workflow stages and their requirements
            foreach ($type->workflowStages as $stage) {
                $stage->stageRequirements()->delete();
                $stage->delete();
            }
            
            return $type->delete();
        });
    }
}
