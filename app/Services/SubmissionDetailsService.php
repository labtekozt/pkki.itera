<?php

namespace App\Services;

use App\Models\Submission;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section as FormsSection;
use Filament\Infolists\Components\Placeholder as InfolistPlaceholder;
use Filament\Infolists\Components\Section as InfolistSection;
use Filament\Infolists\Components\TextEntry;

/**
 * Service to generate submission details components for use in multiple views
 * This follows the Single Responsibility Principle by centralizing the submission details logic
 */
class SubmissionDetailsService
{
    /**
     * Get general information section for a submission as Infolist components
     */
    public function getGeneralInfoSection(Submission $submission): InfolistSection
    {
        return InfolistSection::make('General Information')
            ->schema([
                TextEntry::make('title')
                    ->label('Title')
                    ->getStateUsing(fn() => $submission->title),
                    
                TextEntry::make('submissionType')
                    ->label('Submission Type')
                    ->getStateUsing(fn() => $submission->submissionType->name ?? 'N/A'),
                    
                TextEntry::make('submitter')
                    ->label('Submitted By')
                    ->getStateUsing(fn() => $submission->user->fullname ?? 'Unknown'),
                    
                TextEntry::make('created_at')
                    ->label('Submission Date')
                    ->dateTime()
                    ->getStateUsing(fn() => $submission->created_at),
                    
                TextEntry::make('updated_at')
                    ->label('Last Updated')
                    ->dateTime()
                    ->getStateUsing(fn() => $submission->updated_at)
                    ->visible(fn() => $submission->updated_at->ne($submission->created_at)),
                    
                TextEntry::make('certificate')
                    ->label('Certificate')
                    ->getStateUsing(fn() => $submission->certificate ?? 'Not issued yet'),
                    
                TextEntry::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'draft' => 'gray',
                        'submitted' => 'info',
                        'in_review' => 'warning',
                        'revision_needed' => 'danger',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        'completed' => 'success',
                        'cancelled' => 'gray',
                        default => 'gray',
                    })
                    ->getStateUsing(fn() => $submission->status),
            ])
            ->columns(2);
    }
    
    /**
     * Get general information section for a submission as Form components (for display only)
     */
    public function getGeneralInfoFormSection(Submission $submission): FormsSection
    {
        return FormsSection::make('General Information')
            ->schema([
                Placeholder::make('title')
                    ->label('Title')
                    ->content(fn() => $submission->title),
                    
                Placeholder::make('submissionType')
                    ->label('Submission Type')
                    ->content(fn() => $submission->submissionType->name ?? 'N/A'),
                    
                Placeholder::make('submitter')
                    ->label('Submitted By')
                    ->content(fn() => $submission->user->fullname ?? 'Unknown'),
                    
                Placeholder::make('created_at')
                    ->label('Submission Date')
                    ->content(fn() => $submission->created_at ? $submission->created_at->format('F j, Y H:i') : 'N/A'),
                    
                Placeholder::make('updated_at')
                    ->label('Last Updated')
                    ->content(fn() => $submission->updated_at ? $submission->updated_at->format('F j, Y H:i') : 'N/A')
                    ->visible(fn() => $submission->updated_at->ne($submission->created_at)),
                    
                Placeholder::make('certificate')
                    ->label('Certificate')
                    ->content(fn() => $submission->certificate ?? 'Not issued yet'),
                    
                Placeholder::make('status')
                    ->label('Status')
                    ->content(function() use ($submission) {
                        $status = $submission->status;
                        $statusColor = match ($status) {
                            'draft' => 'gray',
                            'submitted' => 'blue',
                            'in_review' => 'orange',
                            'revision_needed' => 'red',
                            'approved' => 'green',
                            'rejected' => 'red',
                            'completed' => 'green',
                            'cancelled' => 'gray',
                            default => 'gray',
                        };
                        
                        return new \Illuminate\Support\HtmlString("<span class='inline-flex items-center rounded-full bg-{$statusColor}-100 px-2.5 py-0.5 text-xs font-medium text-{$statusColor}-800'>" . 
                               ucfirst(str_replace('_', ' ', $status)) . 
                               "</span>");
                    }),
            ])
            ->columns(2);
    }

    /**
     * Get type-specific details section for a submission
     */
    public function getTypeDetailsSection(Submission $submission): ?InfolistSection
    {
        if (!$submission->submissionType) {
            return null;
        }
        
        $typeSlug = $submission->submissionType->slug;
        $details = $submission->getDetailsAttribute();
        
        if (!$details) {
            return InfolistSection::make('Type-Specific Details')
                ->schema([
                    InfolistPlaceholder::make('no_details')
                        ->content('No specific details available for this submission')
                        ->columnSpanFull(),
                ])
                ->columns(2);
        }
        
        $schema = match ($typeSlug) {
            'paten' => $this->getPatentSchema($details),
            'brand' => $this->getBrandSchema($details),
            'haki' => $this->getHakiSchema($details),
            'industrial_design' => $this->getIndustrialDesignSchema($details),
            default => [],
        };
        
        return InfolistSection::make('Type-Specific Details')
            ->schema($schema)
            ->columns(2)
            ->collapsible();
    }
    
    /**
     * Get type-specific details section for a submission as Form components (for display only)
     */
    public function getTypeDetailsFormSection(Submission $submission): ?FormsSection
    {
        if (!$submission->submissionType) {
            return null;
        }
        
        $typeSlug = $submission->submissionType->slug;
        $details = $submission->getDetailsAttribute();
        
        if (!$details) {
            return FormsSection::make('Type-Specific Details')
                ->schema([
                    Placeholder::make('no_details')
                        ->content('No specific details available for this submission')
                        ->columnSpanFull(),
                ])
                ->columns(2);
        }
        
        $schema = match ($typeSlug) {
            'paten' => $this->getPatentFormSchema($details),
            'brand' => $this->getBrandFormSchema($details),
            'haki' => $this->getHakiFormSchema($details),
            'industrial_design' => $this->getIndustrialDesignFormSchema($details),
            default => [],
        };
        
        return FormsSection::make('Type-Specific Details')
            ->schema($schema)
            ->columns(2)
            ->collapsible();
    }

    /**
     * Get schema for patent submissions
     */
    private function getPatentSchema($details): array
    {
        return [
            TextEntry::make('patent_type')
                ->label('Patent Type')
                ->formatStateUsing(fn(string $state): string => match ($state) {
                    'utility' => 'Utility Patent',
                    'design' => 'Design Patent',
                    'plant' => 'Plant Patent',
                    'process' => 'Process Patent',
                    default => $state,
                })
                ->getStateUsing(fn() => $details->patent_type ?? ''),
                
            TextEntry::make('application_type')
                ->label('Application Type')
                ->formatStateUsing(fn(string $state): string => match ($state) {
                    'simple_patent' => 'Simple Patent',
                    'patent' => 'Standard Patent',
                    default => $state,
                })
                ->getStateUsing(fn() => $details->application_type ?? '')
                ->visible(fn() => isset($details->application_type)),
                
            TextEntry::make('patent_title')
                ->label('Patent Title')
                ->getStateUsing(fn() => $details->patent_title ?? $details->invention_description ?? '')
                ->columnSpanFull(),
                
            TextEntry::make('technical_field')
                ->label('Technical Field')
                ->getStateUsing(fn() => $details->technical_field ?? 'N/A')
                ->columnSpanFull()
                ->visible(fn() => isset($details->technical_field)),
                
            TextEntry::make('inventors_name')
                ->label('Inventors')
                ->getStateUsing(fn() => $details->inventors_name ?? $details->inventor_details ?? 'N/A')
                ->columnSpanFull(),
                
            TextEntry::make('from_grant_research')
                ->label('From Grant Research')
                ->formatStateUsing(fn($state) => $state ? 'Yes' : 'No')
                ->getStateUsing(fn() => $details->from_grant_research ?? null)
                ->visible(fn() => isset($details->from_grant_research)),
                
            TextEntry::make('self_funded')
                ->label('Self Funded')
                ->formatStateUsing(fn($state) => $state ? 'Yes' : 'No')
                ->getStateUsing(fn() => $details->self_funded ?? null)
                ->visible(fn() => isset($details->self_funded)),
        ];
    }
    
    /**
     * Get form schema for patent submissions
     */
    private function getPatentFormSchema($details): array
    {
        return [
            Placeholder::make('application_type')
                ->label('Application Type')
                ->content(match($details->application_type ?? '') {
                    'simple_patent' => 'Simple Patent',
                    'patent' => 'Standard Patent',
                    default => $details->application_type ?? 'N/A'
                }),
                
            Placeholder::make('patent_title')
                ->label('Patent Title')
                ->content($details->patent_title ?? 'N/A')
                ->columnSpanFull(),
                
            Placeholder::make('patent_description')
                ->label('Patent Description')
                ->content($details->patent_description ?? $details->invention_description ?? 'N/A')
                ->columnSpanFull(),

            Placeholder::make('technical_field')
                ->label('Technical Field')
                ->content($details->technical_field ?? 'N/A')
                ->columnSpanFull()
                ->visible(fn() => isset($details->technical_field)),
                
            Placeholder::make('inventors_name')
                ->label('Inventors')
                ->content($details->inventors_name ?? 'N/A'),
                
            Placeholder::make('from_grant_research')
                ->label('From Grant Research')
                ->content($details->from_grant_research ? 'Yes' : 'No')
                ->visible(fn() => isset($details->from_grant_research)),
                
            Placeholder::make('self_funded')
                ->label('Self Funded')
                ->content($details->self_funded ? 'Yes' : 'No')
                ->visible(fn() => isset($details->self_funded)),
                
            Placeholder::make('media_link')
                ->label('Media Link')
                ->content(function() use ($details) {
                    if (!isset($details->media_link) || empty($details->media_link)) {
                        return 'N/A';
                    }
                    
                    return new \Illuminate\Support\HtmlString(
                        '<a href="' . $details->media_link . '" target="_blank" class="text-primary-600 hover:underline">' . 
                        $details->media_link . 
                        ' <svg class="inline-block h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>' .
                        '</a>'
                    );
                })
                ->visible(fn() => isset($details->media_link) && !empty($details->media_link)),
        ];
    }

    /**
     * Get schema for brand/trademark submissions
     */
    private function getBrandSchema($details): array
    {
        return [
            TextEntry::make('trademark_type')
                ->label('Trademark Type')
                ->formatStateUsing(fn(string $state): string => match ($state) {
                    'word' => 'Word Mark',
                    'design' => 'Design Mark',
                    'combined' => 'Combined Mark',
                    'sound' => 'Sound Mark',
                    'collective' => 'Collective Mark',
                    'certification' => 'Certification Mark',
                    default => $state,
                })
                ->getStateUsing(fn() => $details->trademark_type ?? $details->brand_type ?? ''),
                
            TextEntry::make('brand_name')
                ->label('Brand Name')
                ->getStateUsing(fn() => $details->brand_name ?? '')
                ->visible(fn() => isset($details->brand_name)),
                
            TextEntry::make('description')
                ->label('Description')
                ->getStateUsing(fn() => $details->description ?? $details->brand_description ?? 'N/A')
                ->columnSpanFull(),
                
            TextEntry::make('goods_services')
                ->label('Goods & Services')
                ->getStateUsing(fn() => $details->goods_services_description ?? $details->goods_services ?? 'N/A')
                ->columnSpanFull(),
                
            TextEntry::make('nice_classes')
                ->label('Nice Classes')
                ->getStateUsing(fn() => $details->nice_classes ?? 'N/A'),
                
            TextEntry::make('inovators_name')
                ->label('Innovators')
                ->getStateUsing(fn() => $details->inovators_name ?? '')
                ->visible(fn() => isset($details->inovators_name)),
        ];
    }
    
    /**
     * Get form schema for brand/trademark submissions
     */
    private function getBrandFormSchema($details): array
    {
        return [
            Placeholder::make('brand_name')
                ->label('Brand Name')
                ->content($details->brand_name ?? 'N/A'),
                
            Placeholder::make('brand_type')
                ->label('Brand Type')
                ->content(match($details->trademark_type ?? $details->brand_type ?? '') {
                    'word' => 'Word Mark',
                    'design' => 'Design Mark',
                    'combined' => 'Combined Mark',
                    'sound' => 'Sound Mark',
                    'collective' => 'Collective Mark',
                    'certification' => 'Certification Mark',
                    default => $details->trademark_type ?? $details->brand_type ?? 'N/A'
                }),
                
            Placeholder::make('brand_description')
                ->label('Description')
                ->content($details->description ?? $details->brand_description ?? 'N/A')
                ->columnSpanFull(),
                
            Placeholder::make('inovators_name')
                ->label('Innovators')
                ->content($details->inovators_name ?? 'N/A'),
                
            Placeholder::make('application_type')
                ->label('Application Type')
                ->content($details->application_type ?? 'N/A'),
                
            Placeholder::make('application_date')
                ->label('Application Date')
                ->content(fn() => isset($details->application_date) ? $details->application_date->format('F j, Y') : 'N/A'),
                
            Placeholder::make('application_origin')
                ->label('Application Origin')
                ->content($details->application_origin ?? 'N/A')
                ->visible(fn() => isset($details->application_origin)),
                
            Placeholder::make('application_category')
                ->label('Application Category')
                ->content($details->application_category ?? 'N/A')
                ->visible(fn() => isset($details->application_category)),
                
            Placeholder::make('brand_label')
                ->label('Brand Label')
                ->content($details->brand_label ?? 'N/A')
                ->visible(fn() => isset($details->brand_label)),
                
            Placeholder::make('brand_label_reference')
                ->label('Brand Label Reference')
                ->content($details->brand_label_reference ?? 'N/A')
                ->visible(fn() => isset($details->brand_label_reference)),
                
            Placeholder::make('brand_label_description')
                ->label('Brand Label Description')
                ->content($details->brand_label_description ?? 'N/A')
                ->columnSpanFull()
                ->visible(fn() => isset($details->brand_label_description)),
                
            Placeholder::make('brand_color_elements')
                ->label('Brand Color Elements')
                ->content($details->brand_color_elements ?? 'N/A')
                ->visible(fn() => isset($details->brand_color_elements)),
                
            Placeholder::make('foreign_language_translation')
                ->label('Foreign Language Translation')
                ->content($details->foreign_language_translation ?? 'N/A')
                ->columnSpanFull()
                ->visible(fn() => isset($details->foreign_language_translation)),
                
            Placeholder::make('disclaimer')
                ->label('Disclaimer')
                ->content($details->disclaimer ?? 'N/A')
                ->columnSpanFull()
                ->visible(fn() => isset($details->disclaimer)),
                
            Placeholder::make('priority_number')
                ->label('Priority Number')
                ->content($details->priority_number ?? 'N/A')
                ->visible(fn() => isset($details->priority_number)),
                
            Placeholder::make('nice_classes')
                ->label('Nice Classification')
                ->content($details->nice_classes ?? 'N/A')
                ->columnSpanFull(),
                
            Placeholder::make('goods_services_search')
                ->label('Goods & Services')
                ->content($details->goods_services_search ?? $details->goods_services ?? 'N/A')
                ->columnSpanFull(),
        ];
    }

    /**
     * Get schema for copyright/HAKI submissions
     */
    private function getHakiSchema($details): array
    {
        return [
            TextEntry::make('work_type')
                ->label('Work Type')
                ->formatStateUsing(fn(string $state): string => match ($state) {
                    'literary' => 'Literary Work',
                    'musical' => 'Musical Work',
                    'dramatic' => 'Dramatic Work',
                    'artistic' => 'Artistic Work',
                    'audiovisual' => 'Audiovisual Work',
                    'sound_recording' => 'Sound Recording',
                    'architectural' => 'Architectural Work',
                    'computer_program' => 'Computer Program',
                    default => $state,
                })
                ->getStateUsing(fn() => $details->work_type ?? ''),
                
            TextEntry::make('haki_title')
                ->label('Work Title')
                ->getStateUsing(fn() => $details->haki_title ?? '')
                ->visible(fn() => isset($details->haki_title))
                ->columnSpanFull(),
                
            TextEntry::make('work_description')
                ->label('Work Description')
                ->getStateUsing(fn() => $details->work_description ?? 'N/A')
                ->columnSpanFull(),
                
            TextEntry::make('creation_year')
                ->label('Year of Creation')
                ->getStateUsing(fn() => $details->creation_year ?? 'N/A'),
                
            TextEntry::make('is_published')
                ->label('Publication Status')
                ->formatStateUsing(fn($state) => $state ? 'Published' : 'Unpublished')
                ->getStateUsing(fn() => $details->is_published ?? false),
                
            TextEntry::make('inventors_name')
                ->label('Creators/Authors')
                ->getStateUsing(fn() => $details->inventors_name ?? '')
                ->visible(fn() => isset($details->inventors_name)),
        ];
    }
    
    /**
     * Get form schema for copyright/HAKI submissions
     */
    private function getHakiFormSchema($details): array
    {
        return [
            Placeholder::make('work_type')
                ->label('Work Type')
                ->content(match($details->work_type ?? '') {
                    'literary' => 'Literary Work',
                    'musical' => 'Musical Work',
                    'dramatic' => 'Dramatic Work',
                    'artistic' => 'Artistic Work',
                    'audiovisual' => 'Audiovisual Work',
                    'sound_recording' => 'Sound Recording',
                    'architectural' => 'Architectural Work',
                    'computer_program' => 'Computer Program',
                    default => $details->work_type ?? 'N/A'
                }),
                
            Placeholder::make('work_subtype')
                ->label('Work Subtype')
                ->content($details->work_subtype ?? 'N/A')
                ->visible(fn() => isset($details->work_subtype)),
                
            Placeholder::make('haki_category')
                ->label('HAKI Category')
                ->content($details->haki_category ?? 'N/A')
                ->visible(fn() => isset($details->haki_category)),
                
            Placeholder::make('haki_title')
                ->label('Work Title')
                ->content($details->haki_title ?? 'N/A')
                ->columnSpanFull(),
                
            Placeholder::make('work_description')
                ->label('Work Description')
                ->content($details->work_description ?? 'N/A')
                ->columnSpanFull(),
                
            Placeholder::make('first_publication_date')
                ->label('First Publication Date')
                ->content(fn() => isset($details->first_publication_date) ? $details->first_publication_date->format('F j, Y') : 'N/A'),
                
            Placeholder::make('first_publication_place')
                ->label('First Publication Place')
                ->content($details->first_publication_place ?? 'N/A')
                ->visible(fn() => isset($details->first_publication_place)),
                
            Placeholder::make('is_kkn_output')
                ->label('KKN Output')
                ->content(fn() => isset($details->is_kkn_output) ? ($details->is_kkn_output ? 'Yes' : 'No') : 'N/A'),
                
            Placeholder::make('from_grant_research')
                ->label('From Grant Research')
                ->content(fn() => isset($details->from_grant_research) ? ($details->from_grant_research ? 'Yes' : 'No') : 'N/A'),
                
            Placeholder::make('self_funded')
                ->label('Self Funded')
                ->content(fn() => isset($details->self_funded) ? ($details->self_funded ? 'Yes' : 'No') : 'N/A'),
                
            Placeholder::make('registration_number')
                ->label('Registration Number')
                ->content($details->registration_number ?? 'N/A')
                ->visible(fn() => isset($details->registration_number)),
                
            Placeholder::make('registration_date')
                ->label('Registration Date')
                ->content(fn() => isset($details->registration_date) ? $details->registration_date->format('F j, Y') : 'N/A')
                ->visible(fn() => isset($details->registration_date)),
                
            Placeholder::make('inventors_name')
                ->label('Creators/Authors')
                ->content($details->inventors_name ?? 'N/A')
                ->columnSpanFull(),
        ];
    }

    /**
     * Get schema for industrial design submissions
     */
    private function getIndustrialDesignSchema($details): array
    {
        return [
            TextEntry::make('design_title')
                ->label('Design Title')
                ->getStateUsing(fn() => $details->design_title ?? '')
                ->visible(fn() => isset($details->design_title))
                ->columnSpanFull(),
                
            TextEntry::make('design_type')
                ->label('Design Type')
                ->getStateUsing(fn() => $details->design_type ?? 'N/A'),
                
            TextEntry::make('design_description')
                ->label('Design Description')
                ->getStateUsing(fn() => $details->design_description ?? 'N/A')
                ->columnSpanFull(),
                
            TextEntry::make('novelty_statement')
                ->label('Novelty Statement')
                ->getStateUsing(fn() => $details->novelty_statement ?? 'N/A')
                ->columnSpanFull(),
                
            TextEntry::make('designer_information')
                ->label('Designer Information')
                ->getStateUsing(fn() => $details->designer_information ?? 'N/A')
                ->columnSpanFull(),
                
            TextEntry::make('inventors_name')
                ->label('Inventors')
                ->getStateUsing(fn() => $details->inventors_name ?? '')
                ->visible(fn() => isset($details->inventors_name)),
                
            TextEntry::make('locarno_class')
                ->label('Locarno Classification')
                ->getStateUsing(fn() => $details->locarno_class ?? 'N/A')
                ->visible(fn() => isset($details->locarno_class)),
        ];
    }
    
    /**
     * Get form schema for industrial design submissions
     */
    private function getIndustrialDesignFormSchema($details): array
    {
        return [
            Placeholder::make('design_title')
                ->label('Design Title')
                ->content($details->design_title ?? 'N/A')
                ->columnSpanFull(),
                
            Placeholder::make('design_type')
                ->label('Design Type')
                ->content($details->design_type ?? 'N/A'),
                
            Placeholder::make('design_description')
                ->label('Design Description')
                ->content($details->design_description ?? 'N/A')
                ->columnSpanFull(),
                
            Placeholder::make('novelty_statement')
                ->label('Novelty Statement')
                ->content($details->novelty_statement ?? 'N/A')
                ->columnSpanFull(),
                
            Placeholder::make('designer_information')
                ->label('Designer Information')
                ->content($details->designer_information ?? 'N/A')
                ->columnSpanFull(),
                
            Placeholder::make('inventors_name')
                ->label('Inventors')
                ->content($details->inventors_name ?? 'N/A'),
                
            Placeholder::make('locarno_class')
                ->label('Locarno Classification')
                ->content($details->locarno_class ?? 'N/A'),
        ];
    }
}