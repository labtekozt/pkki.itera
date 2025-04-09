<?php

namespace App\Filament\Forms;

use App\Models\SubmissionType;
use Filament\Forms\Components\Component;

class SubmissionFormFactory
{
    /**
     * Get the appropriate form schema based on submission type
     */
    public static function getFormForSubmissionType(string $submissionTypeSlug): array
    {
        return match($submissionTypeSlug) {
            'paten' => PatentSubmissionForm::getFormSchema(),
            'brand' => BrandSubmissionForm::getFormSchema(),
            'haki' => HakiSubmissionForm::getFormSchema(),
            'industrial_design' => IndustrialDesignSubmissionForm::getFormSchema(),
            default => []
        };
    }
    
    /**
     * Get form component for the selected submission type
     */
    public static function makeTypeSpecificForm(): Component
    {
        return \Filament\Forms\Components\Grid::make()
            ->schema([
                \Filament\Forms\Components\Placeholder::make('type_specific_form_placeholder')
                    ->content(function (\Filament\Forms\Get $get) {
                        $submissionTypeId = $get('submission_type_id');
                        
                        if (!$submissionTypeId) {
                            return 'Please select a submission type first';
                        }
                        
                        return '';
                    })
                    ->visible(function (\Filament\Forms\Get $get) {
                        return !$get('submission_type_id');
                    }),
            ])
            ->columnSpanFull()
            ->visible(function (\Filament\Forms\Get $get) {
                if (!$get('submission_type_id')) {
                    return false;
                }
                
                $submissionType = SubmissionType::find($get('submission_type_id'));
                
                return (bool)$submissionType;
            })
            ->collapsible();
    }
}
