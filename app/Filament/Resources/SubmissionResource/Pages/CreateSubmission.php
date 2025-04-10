<?php

namespace App\Filament\Resources\SubmissionResource\Pages;

use App\Filament\Forms\SubmissionFormFactory;
use App\Filament\Resources\SubmissionResource;
use App\Models\SubmissionType;
use App\Repositories\SubmissionRepository;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Components\Wizard\Step;
use Filament\Forms\Get;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Pages\CreateRecord\Concerns\HasWizard;
use Illuminate\Support\Facades\Auth;

class CreateSubmission extends CreateRecord
{
    use HasWizard;

    protected static string $resource = SubmissionResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }

    protected function getSteps(): array
    {
        return [
            Step::make('Basic Information')
                ->description('Enter submission basic details')
                ->icon('heroicon-o-document-text')
                ->schema([
                    Hidden::make('user_id')
                        ->default(fn() => Auth::id()),

                    Hidden::make('status')
                        ->default('draft'),

                    Select::make('submission_type_id')
                        ->relationship('submissionType', 'name')
                        ->required()
                        ->helperText('Choose the type of intellectual property you want to submit')
                        ->reactive()
                        ->afterStateUpdated(fn(callable $set) => $set('current_stage_id', null))
                        ->columnSpanFull(),

                    TextInput::make('title')
                        ->required()
                        ->helperText('Provide a clear and concise title for your submission')
                        ->maxLength(255)
                        ->columnSpanFull()
                ]),

            Step::make('Type Details')
                ->description('Enter type-specific information')
                ->icon('heroicon-o-clipboard-document-list')
                ->schema(function (Get $get) {
                    $submissionTypeId = $get('submission_type_id');

                    if (!$submissionTypeId) {
                        return [
                            \Filament\Forms\Components\Placeholder::make('select_type')
                                ->content('Please select a submission type first')
                                ->columnSpanFull(),
                        ];
                    }

                    $submissionType = SubmissionType::find($submissionTypeId);

                    if (!$submissionType) {
                        return [];
                    }

                    return SubmissionFormFactory::getFormForSubmissionType($submissionType->slug);
                })
                ->visible(fn(Get $get): bool => (bool) $get('submission_type_id')),

            Step::make('Documents')
                ->description('Upload required documents')
                ->icon('heroicon-o-document-arrow-up')
                ->schema([
                    \Filament\Forms\Components\Placeholder::make('documents_info')
                        ->content('You can add required supporting documents after creating the submission. Each submission type has specific document requirements that will be shown there.')
                        ->columnSpanFull(),

                    \Filament\Forms\Components\Section::make('Document Requirements')
                        ->schema(function (Get $get) {
                            $submissionTypeId = $get('submission_type_id');

                            if (!$submissionTypeId) {
                                return [];
                            }

                            $submissionType = SubmissionType::find($submissionTypeId);

                            if (!$submissionType) {
                                return [];
                            }

                            $content = "### Required Documents for {$submissionType->name}\n\n";
                            $content .= "You will be able to upload these documents in the next step after submission is created:\n\n";

                            // This could be dynamically populated from document requirements
                            switch ($submissionType->slug) {
                                case 'paten':
                                    $content .= "- Patent application form\n";
                                    $content .= "- Invention description document\n";
                                    $content .= "- Claim document\n";
                                    $content .= "- Abstract\n";
                                    $content .= "- Drawings (if applicable)\n";
                                    break;

                                case 'brand':
                                    $content .= "- Brand registration form\n";
                                    $content .= "- Brand label/logo\n";
                                    $content .= "- Owner ID\n";
                                    break;

                                case 'haki':
                                    $content .= "- Copyright registration form\n";
                                    $content .= "- Copy of the work\n";
                                    $content .= "- Author's statement\n";
                                    break;

                                case 'industrial_design':
                                    $content .= "- Design registration form\n";
                                    $content .= "- Design representations\n";
                                    $content .= "- Design description\n";
                                    break;

                                default:
                                    $content .= "- Submission form\n";
                                    $content .= "- Supporting documents\n";
                            }

                            return [
                                \Filament\Forms\Components\Placeholder::make('document_requirements')
                                    ->content(new \Illuminate\Support\HtmlString(\Illuminate\Support\Str::markdown($content)))
                                    ->columnSpanFull(),
                            ];
                        })
                        ->columnSpanFull(),
                ])
                ->visible(fn(Get $get): bool => (bool) $get('submission_type_id')),

            Step::make('Review & Submit')
                ->description('Review submission information')
                ->icon('heroicon-o-check-circle')
                ->schema([
                    \Filament\Forms\Components\Placeholder::make('submission_review')
                        ->content(function (Get $get) {
                            $typeId = $get('submission_type_id');
                            if (!$typeId) return 'Please select a submission type first.';

                            $type = SubmissionType::find($typeId);

                            $content = "## Submission Summary\n\n";
                            $content .= "**Type:** " . ($type ? $type->name : 'Unknown') . "\n\n";
                            $content .= "**Title:** " . $get('title') . "\n\n";
                            $content .= "**Description:** " . $get('description') . "\n\n";
                            $content .= "---\n\n";
                            $content .= "Please review your information carefully before submitting. You can go back to previous steps to make changes.";

                            return new \Illuminate\Support\HtmlString(
                                \Illuminate\Support\Str::markdown($content)
                            );
                        })
                        ->columnSpanFull(),

                    Select::make('status')
                        ->options([
                            'draft' => 'Save as Draft',
                        ])
                        ->default('draft')
                        ->required()
                        ->helperText('Save as draft to edit later, or submit for immediate review. Once submitted, you cannot edit without requesting a revision.')
                        ->columnSpanFull(),
                ]),
        ];
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Make sure user_id is set if not already
        if (!isset($data['user_id'])) {
            $data['user_id'] = Auth::id();
        }

        return $data;
    }

    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        // Use the SubmissionRepository to create the submission
        $submissionRepo = app(SubmissionRepository::class);

        // Extract documents data if present
        $documents = $data['documents'] ?? [];
        unset($data['documents']);

        return $submissionRepo->createSubmission($data, $documents);
    }
}
