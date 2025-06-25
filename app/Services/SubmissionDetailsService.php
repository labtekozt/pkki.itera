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
        return InfolistSection::make(__('resource.submission.general_information'))
            ->schema([
                TextEntry::make('title')
                    ->label(__('resource.submission.fields.title'))
                    ->getStateUsing(fn() => $submission->title),
                    
                TextEntry::make('submissionType')
                    ->label(__('resource.submission.fields.submission_type'))
                    ->getStateUsing(fn() => $submission->submissionType->name ?? __('resource.general.not_available')),
                    
                TextEntry::make('submitter')
                    ->label(__('resource.submission.fields.submitted_by'))
                    ->getStateUsing(fn() => $submission->user->fullname ?? __('resource.general.unknown')),
                    
                TextEntry::make('created_at')
                    ->label(__('resource.submission.fields.submission_date'))
                    ->dateTime()
                    ->getStateUsing(fn() => $submission->created_at),
                    
                TextEntry::make('updated_at')
                    ->label(__('resource.general.updated_at'))
                    ->dateTime()
                    ->getStateUsing(fn() => $submission->updated_at)
                    ->visible(fn() => $submission->updated_at->ne($submission->created_at)),
                    
                TextEntry::make('certificate')
                    ->label(__('resource.submission.fields.certificate'))
                    ->getStateUsing(fn() => $submission->certificate ?? __('resource.submission.certificate_not_issued')),
                    
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
        return FormsSection::make(__('resource.submission.general_information'))
            ->schema([
                Placeholder::make('title')
                    ->label(__('resource.submission.fields.title'))
                    ->content(fn() => $submission->title),
                    
                Placeholder::make('submissionType')
                    ->label(__('resource.submission.fields.submission_type'))
                    ->content(fn() => $submission->submissionType->name ?? __('resource.general.not_available')),
                    
                Placeholder::make('submitter')
                    ->label(__('resource.submission.fields.submitted_by'))
                    ->content(fn() => $submission->user->fullname ?? __('resource.general.unknown')),
                    
                Placeholder::make('created_at')
                    ->label(__('resource.submission.fields.submission_date'))
                    ->content(fn() => $submission->created_at ? $submission->created_at->format('F j, Y H:i') : __('resource.general.not_available')),
                    
                Placeholder::make('updated_at')
                    ->label(__('resource.general.updated_at'))
                    ->content(fn() => $submission->updated_at ? $submission->updated_at->format('F j, Y H:i') : __('resource.general.not_available'))
                    ->visible(fn() => $submission->updated_at->ne($submission->created_at)),
                    
                Placeholder::make('certificate')
                    ->label(__('resource.submission.fields.certificate'))
                    ->content(fn() => $submission->certificate ?? __('resource.submission.certificate_not_issued')),
                    
                Placeholder::make('status')
                    ->label(__('resource.submission.fields.status'))
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
            return InfolistSection::make(__('resource.general.type_specific_details'))
                ->schema([
                    InfolistPlaceholder::make('no_details')
                        ->content(__('resource.general.no_details_available'))
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
        
        return InfolistSection::make(__('resource.general.type_specific_details'))
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
            return FormsSection::make(__('resource.general.type_specific_details'))
                ->schema([
                    Placeholder::make('no_details')
                        ->content(__('resource.general.no_details_available'))
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
        
        return FormsSection::make(__('resource.general.type_specific_details'))
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
                ->label(__('resource.patent.patent_type'))
                ->formatStateUsing(fn(string $state): string => match ($state) {
                    'utility' => __('resource.patent.types.utility'),
                    'design' => __('resource.patent.types.design'),
                    'plant' => __('resource.patent.types.plant'),
                    'process' => __('resource.patent.types.process'),
                    default => $state,
                })
                ->getStateUsing(fn() => $details->patent_type ?? ''),
                
            TextEntry::make('application_type')
                ->label(__('resource.patent.application_type'))
                ->formatStateUsing(fn(string $state): string => match ($state) {
                    'simple_patent' => __('resource.patent.application_types.simple'),
                    'patent' => __('resource.patent.application_types.standard'),
                    default => $state,
                })
                ->getStateUsing(fn() => $details->application_type ?? '')
                ->visible(fn() => isset($details->application_type)),
                
            TextEntry::make('patent_title')
                ->label(__('resource.patent.patent_title'))
                ->getStateUsing(fn() => $details->patent_title ?? $details->invention_description ?? '')
                ->columnSpanFull(),
                
            TextEntry::make('technical_field')
                ->label(__('resource.patent.technical_field'))
                ->getStateUsing(fn() => $details->technical_field ?? __('resource.general.not_available'))
                ->columnSpanFull()
                ->visible(fn() => isset($details->technical_field)),
                
            TextEntry::make('inventors_name')
                ->label(__('resource.patent.inventors'))
                ->getStateUsing(fn() => $details->inventors_name ?? $details->inventor_details ?? __('resource.general.not_available'))
                ->columnSpanFull(),
                
            TextEntry::make('from_grant_research')
                ->label(__('resource.patent.from_grant_research'))
                ->formatStateUsing(fn($state) => $state ? __('resource.general.yes') : __('resource.general.no'))
                ->getStateUsing(fn() => $details->from_grant_research ?? null)
                ->visible(fn() => isset($details->from_grant_research)),
                
            TextEntry::make('self_funded')
                ->label(__('resource.patent.self_funded'))
                ->formatStateUsing(fn($state) => $state ? __('resource.general.yes') : __('resource.general.no'))
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
                ->label(__('resource.patent.application_type'))
                ->content(match($details->application_type ?? '') {
                    'simple_patent' => __('resource.patent.application_types.simple'),
                    'patent' => __('resource.patent.application_types.standard'),
                    default => $details->application_type ?? __('resource.general.not_available')
                }),
                
            Placeholder::make('patent_title')
                ->label(__('resource.patent.patent_title'))
                ->content($details->patent_title ?? __('resource.general.not_available'))
                ->columnSpanFull(),
                
            Placeholder::make('patent_description')
                ->label(__('resource.patent.patent_description'))
                ->content($details->patent_description ?? $details->invention_description ?? __('resource.general.not_available'))
                ->columnSpanFull(),

            Placeholder::make('technical_field')
                ->label(__('resource.patent.technical_field'))
                ->content($details->technical_field ?? __('resource.general.not_available'))
                ->columnSpanFull()
                ->visible(fn() => isset($details->technical_field)),
                
            Placeholder::make('inventors_name')
                ->label(__('resource.patent.inventors'))
                ->content($details->inventors_name ?? __('resource.general.not_available')),
                
            Placeholder::make('from_grant_research')
                ->label(__('resource.patent.from_grant_research'))
                ->content($details->from_grant_research ? __('resource.general.yes') : __('resource.general.no'))
                ->visible(fn() => isset($details->from_grant_research)),
                
            Placeholder::make('self_funded')
                ->label(__('resource.patent.self_funded'))
                ->content($details->self_funded ? __('resource.general.yes') : __('resource.general.no'))
                ->visible(fn() => isset($details->self_funded)),
                
            Placeholder::make('media_link')
                ->label(__('resource.patent.media_link'))
                ->content(function() use ($details) {
                    if (!isset($details->media_link) || empty($details->media_link)) {
                        return __('resource.general.not_available');
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
                ->label(__('resource.brand.trademark_type'))
                ->formatStateUsing(fn(string $state): string => match ($state) {
                    'word' => __('resource.brand.types.word'),
                    'design' => __('resource.brand.types.design'),
                    'combined' => __('resource.brand.types.combined'),
                    'sound' => __('resource.brand.types.sound'),
                    'collective' => __('resource.brand.types.collective'),
                    'certification' => __('resource.brand.types.certification'),
                    default => $state,
                })
                ->getStateUsing(fn() => $details->trademark_type ?? $details->brand_type ?? ''),
                
            TextEntry::make('brand_name')
                ->label(__('resource.brand.brand_name'))
                ->getStateUsing(fn() => $details->brand_name ?? '')
                ->visible(fn() => isset($details->brand_name)),
                
            TextEntry::make('description')
                ->label(__('resource.brand.description'))
                ->getStateUsing(fn() => $details->description ?? $details->brand_description ?? __('resource.general.not_available'))
                ->columnSpanFull(),
                
            TextEntry::make('goods_services')
                ->label(__('resource.brand.goods_services'))
                ->getStateUsing(fn() => $details->goods_services_description ?? $details->goods_services ?? __('resource.general.not_available'))
                ->columnSpanFull(),
                
            TextEntry::make('nice_classes')
                ->label(__('resource.brand.nice_classes'))
                ->getStateUsing(fn() => $details->nice_classes ?? __('resource.general.not_available')),
                
            TextEntry::make('inovators_name')
                ->label(__('resource.brand.innovators'))
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
                ->label(__('resource.brand.brand_name'))
                ->content($details->brand_name ?? __('resource.general.not_available')),
                
            Placeholder::make('brand_type')
                ->label(__('resource.brand.brand_type'))
                ->content(match($details->trademark_type ?? $details->brand_type ?? '') {
                    'word' => __('resource.brand.types.word'),
                    'design' => __('resource.brand.types.design'),
                    'combined' => __('resource.brand.types.combined'),
                    'sound' => __('resource.brand.types.sound'),
                    'collective' => __('resource.brand.types.collective'),
                    'certification' => __('resource.brand.types.certification'),
                    default => $details->trademark_type ?? $details->brand_type ?? __('resource.general.not_available')
                }),
                
            Placeholder::make('brand_description')
                ->label(__('resource.brand.description'))
                ->content($details->description ?? $details->brand_description ?? __('resource.general.not_available'))
                ->columnSpanFull(),
                
            Placeholder::make('inovators_name')
                ->label(__('resource.brand.innovators'))
                ->content($details->inovators_name ?? __('resource.general.not_available')),
                
            Placeholder::make('application_type')
                ->label(__('resource.brand.application_type'))
                ->content($details->application_type ?? __('resource.general.not_available')),
                
            Placeholder::make('application_date')
                ->label(__('resource.brand.application_date'))
                ->content(fn() => isset($details->application_date) ? $details->application_date->format('F j, Y') : __('resource.general.not_available')),
                
            Placeholder::make('application_origin')
                ->label(__('resource.brand.application_origin'))
                ->content($details->application_origin ?? __('resource.general.not_available'))
                ->visible(fn() => isset($details->application_origin)),
                
            Placeholder::make('application_category')
                ->label(__('resource.brand.application_category'))
                ->content($details->application_category ?? __('resource.general.not_available'))
                ->visible(fn() => isset($details->application_category)),
                
            Placeholder::make('brand_label')
                ->label(__('resource.brand.brand_label'))
                ->content($details->brand_label ?? __('resource.general.not_available'))
                ->visible(fn() => isset($details->brand_label)),
                
            Placeholder::make('brand_label_reference')
                ->label(__('resource.brand.brand_label_reference'))
                ->content($details->brand_label_reference ?? __('resource.general.not_available'))
                ->visible(fn() => isset($details->brand_label_reference)),
                
            Placeholder::make('brand_label_description')
                ->label(__('resource.brand.brand_label_description'))
                ->content($details->brand_label_description ?? __('resource.general.not_available'))
                ->columnSpanFull()
                ->visible(fn() => isset($details->brand_label_description)),
                
            Placeholder::make('brand_color_elements')
                ->label(__('resource.brand.brand_color_elements'))
                ->content($details->brand_color_elements ?? __('resource.general.not_available'))
                ->visible(fn() => isset($details->brand_color_elements)),
                
            Placeholder::make('foreign_language_translation')
                ->label(__('resource.brand.foreign_language_translation'))
                ->content($details->foreign_language_translation ?? __('resource.general.not_available'))
                ->columnSpanFull()
                ->visible(fn() => isset($details->foreign_language_translation)),
                
            Placeholder::make('disclaimer')
                ->label(__('resource.brand.disclaimer'))
                ->content($details->disclaimer ?? __('resource.general.not_available'))
                ->columnSpanFull()
                ->visible(fn() => isset($details->disclaimer)),
                
            Placeholder::make('priority_number')
                ->label(__('resource.brand.priority_number'))
                ->content($details->priority_number ?? __('resource.general.not_available'))
                ->visible(fn() => isset($details->priority_number)),
                
            Placeholder::make('nice_classes')
                ->label(__('resource.brand.nice_classification'))
                ->content($details->nice_classes ?? __('resource.general.not_available'))
                ->columnSpanFull(),
                
            Placeholder::make('goods_services_search')
                ->label(__('resource.brand.goods_services'))
                ->content($details->goods_services_search ?? $details->goods_services ?? __('resource.general.not_available'))
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
                ->label(__('resource.haki.work_type'))
                ->formatStateUsing(fn(string $state): string => match ($state) {
                    'literary' => __('resource.haki.types.literary'),
                    'musical' => __('resource.haki.types.musical'),
                    'dramatic' => __('resource.haki.types.dramatic'),
                    'artistic' => __('resource.haki.types.artistic'),
                    'audiovisual' => __('resource.haki.types.audiovisual'),
                    'sound_recording' => __('resource.haki.types.sound_recording'),
                    'architectural' => __('resource.haki.types.architectural'),
                    'computer_program' => __('resource.haki.types.computer_program'),
                    default => $state,
                })
                ->getStateUsing(fn() => $details->work_type ?? ''),
                
            TextEntry::make('haki_title')
                ->label(__('resource.haki.work_title'))
                ->getStateUsing(fn() => $details->haki_title ?? '')
                ->visible(fn() => isset($details->haki_title))
                ->columnSpanFull(),
                
            TextEntry::make('work_description')
                ->label(__('resource.haki.work_description'))
                ->getStateUsing(fn() => $details->work_description ?? __('resource.general.not_available'))
                ->columnSpanFull(),
                
            TextEntry::make('creation_year')
                ->label(__('resource.haki.creation_year'))
                ->getStateUsing(fn() => $details->creation_year ?? __('resource.general.not_available')),
                
            TextEntry::make('is_published')
                ->label(__('resource.haki.publication_status'))
                ->formatStateUsing(fn($state) => $state ? __('resource.haki.published') : __('resource.haki.unpublished'))
                ->getStateUsing(fn() => $details->is_published ?? false),
                
            TextEntry::make('inventors_name')
                ->label(__('resource.haki.creators_authors'))
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
                ->label(__('resource.haki.work_type'))
                ->content(match($details->work_type ?? '') {
                    'literary' => __('resource.haki.types.literary'),
                    'musical' => __('resource.haki.types.musical'),
                    'dramatic' => __('resource.haki.types.dramatic'),
                    'artistic' => __('resource.haki.types.artistic'),
                    'audiovisual' => __('resource.haki.types.audiovisual'),
                    'sound_recording' => __('resource.haki.types.sound_recording'),
                    'architectural' => __('resource.haki.types.architectural'),
                    'computer_program' => __('resource.haki.types.computer_program'),
                    default => $details->work_type ?? __('resource.general.not_available')
                }),
                
            Placeholder::make('work_subtype')
                ->label(__('resource.haki.work_subtype'))
                ->content($details->work_subtype ?? __('resource.general.not_available'))
                ->visible(fn() => isset($details->work_subtype)),
                
            Placeholder::make('haki_category')
                ->label(__('resource.haki.haki_category'))
                ->content($details->haki_category ?? __('resource.general.not_available'))
                ->visible(fn() => isset($details->haki_category)),
                
            Placeholder::make('haki_title')
                ->label(__('resource.haki.work_title'))
                ->content($details->haki_title ?? __('resource.general.not_available'))
                ->columnSpanFull(),
                
            Placeholder::make('work_description')
                ->label(__('resource.haki.work_description'))
                ->content($details->work_description ?? __('resource.general.not_available'))
                ->columnSpanFull(),
                
            Placeholder::make('first_publication_date')
                ->label(__('resource.haki.first_publication_date'))
                ->content(fn() => isset($details->first_publication_date) ? $details->first_publication_date->format('F j, Y') : __('resource.general.not_available')),
                
            Placeholder::make('first_publication_place')
                ->label(__('resource.haki.first_publication_place'))
                ->content($details->first_publication_place ?? __('resource.general.not_available'))
                ->visible(fn() => isset($details->first_publication_place)),
                
            Placeholder::make('is_kkn_output')
                ->label(__('resource.haki.kkn_output'))
                ->content(fn() => isset($details->is_kkn_output) ? ($details->is_kkn_output ? __('resource.general.yes') : __('resource.general.no')) : __('resource.general.not_available')),
                
            Placeholder::make('from_grant_research')
                ->label(__('resource.haki.from_grant_research'))
                ->content(fn() => isset($details->from_grant_research) ? ($details->from_grant_research ? __('resource.general.yes') : __('resource.general.no')) : __('resource.general.not_available')),
                
            Placeholder::make('self_funded')
                ->label(__('resource.haki.self_funded'))
                ->content(fn() => isset($details->self_funded) ? ($details->self_funded ? __('resource.general.yes') : __('resource.general.no')) : __('resource.general.not_available')),
                
            Placeholder::make('registration_number')
                ->label(__('resource.haki.registration_number'))
                ->content($details->registration_number ?? __('resource.general.not_available'))
                ->visible(fn() => isset($details->registration_number)),
                
            Placeholder::make('registration_date')
                ->label(__('resource.haki.registration_date'))
                ->content(fn() => isset($details->registration_date) ? $details->registration_date->format('F j, Y') : __('resource.general.not_available'))
                ->visible(fn() => isset($details->registration_date)),
                
            Placeholder::make('inventors_name')
                ->label(__('resource.haki.creators_authors'))
                ->content($details->inventors_name ?? __('resource.general.not_available'))
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
                ->label(__('resource.industrial_design.design_title'))
                ->getStateUsing(fn() => $details->design_title ?? '')
                ->visible(fn() => isset($details->design_title))
                ->columnSpanFull(),
                
            TextEntry::make('design_type')
                ->label(__('resource.industrial_design.design_type'))
                ->getStateUsing(fn() => $details->design_type ?? __('resource.general.not_available')),
                
            TextEntry::make('design_description')
                ->label(__('resource.industrial_design.design_description'))
                ->getStateUsing(fn() => $details->design_description ?? __('resource.general.not_available'))
                ->columnSpanFull(),
                
            TextEntry::make('novelty_statement')
                ->label(__('resource.industrial_design.novelty_statement'))
                ->getStateUsing(fn() => $details->novelty_statement ?? __('resource.general.not_available'))
                ->columnSpanFull(),
                
            TextEntry::make('designer_information')
                ->label(__('resource.industrial_design.designer_information'))
                ->getStateUsing(fn() => $details->designer_information ?? __('resource.general.not_available'))
                ->columnSpanFull(),
                
            TextEntry::make('inventors_name')
                ->label(__('resource.industrial_design.inventors'))
                ->getStateUsing(fn() => $details->inventors_name ?? '')
                ->visible(fn() => isset($details->inventors_name)),
                
            TextEntry::make('locarno_class')
                ->label(__('resource.industrial_design.locarno_classification'))
                ->getStateUsing(fn() => $details->locarno_class ?? __('resource.general.not_available'))
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
                ->label(__('resource.industrial_design.design_title'))
                ->content($details->design_title ?? __('resource.general.not_available'))
                ->columnSpanFull(),
                
            Placeholder::make('design_type')
                ->label(__('resource.industrial_design.design_type'))
                ->content($details->design_type ?? __('resource.general.not_available')),
                
            Placeholder::make('design_description')
                ->label(__('resource.industrial_design.design_description'))
                ->content($details->design_description ?? __('resource.general.not_available'))
                ->columnSpanFull(),
                
            Placeholder::make('novelty_statement')
                ->label(__('resource.industrial_design.novelty_statement'))
                ->content($details->novelty_statement ?? __('resource.general.not_available'))
                ->columnSpanFull(),
                
            Placeholder::make('designer_information')
                ->label(__('resource.industrial_design.designer_information'))
                ->content($details->designer_information ?? __('resource.general.not_available'))
                ->columnSpanFull(),
                
            Placeholder::make('inventors_name')
                ->label(__('resource.industrial_design.inventors'))
                ->content($details->inventors_name ?? __('resource.general.not_available')),
                
            Placeholder::make('locarno_class')
                ->label(__('resource.industrial_design.locarno_classification'))
                ->content($details->locarno_class ?? __('resource.general.not_available')),
        ];
    }
}