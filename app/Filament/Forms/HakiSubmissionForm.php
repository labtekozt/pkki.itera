<?php

namespace App\Filament\Forms;

use Filament\Forms;
use Filament\Forms\Get;

class HakiSubmissionForm
{
    public static function getFormSchema(): array
    {
        return [
            Forms\Components\Section::make(__('resource.haki.details'))
                ->schema([
                    Forms\Components\TextInput::make('hakiDetail.haki_title')
                        ->label(__('resource.haki.work_title'))
                        ->helperText(__('resource.haki.work_title_helper'))
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull(),
                        
                    Forms\Components\Select::make('hakiDetail.work_type')
                        ->label(__('resource.haki.work_type'))
                        ->options([
                            'literary' => __('resource.haki.literary_work'),
                            'musical' => __('resource.haki.musical_work'),
                            'dramatic' => __('resource.haki.dramatic_work'),
                            'artistic' => __('resource.haki.artistic_work'),
                            'audiovisual' => __('resource.haki.audiovisual_work'),
                            'sound_recording' => __('resource.haki.sound_recording'),
                            'computer_program' => __('resource.haki.computer_program'),
                        ])
                        ->helperText(__('resource.haki.work_type_helper'))
                        ->required(),
                        
                    Forms\Components\TextInput::make('hakiDetail.work_subtype')
                        ->label(__('resource.haki.work_subtype'))
                        ->helperText(__('resource.haki.work_subtype_helper')),
                        
                    Forms\Components\Select::make('hakiDetail.haki_category')
                        ->label(__('resource.haki.category'))
                        ->options([
                            'computer' => __('resource.haki.computer'),
                            'non_computer' => __('resource.haki.non_computer'),
                        ])
                        ->helperText(__('resource.haki.category_helper'))
                        ->required(),
                        
                    Forms\Components\Textarea::make('hakiDetail.work_description')
                        ->label(__('resource.haki.work_description'))
                        ->helperText(__('resource.haki.description_helper'))
                        ->required()
                        ->columnSpanFull(),
                        
                    Forms\Components\DatePicker::make('hakiDetail.first_publication_date')
                        ->label(__('resource.haki.first_publication_date'))
                        ->helperText(__('resource.haki.publication_date_helper')),
                        
                    Forms\Components\TextInput::make('hakiDetail.first_publication_place')
                        ->label(__('resource.haki.publication_place'))
                        ->helperText(__('resource.haki.publication_place_helper')),
                        
                    Forms\Components\Toggle::make('hakiDetail.is_kkn_output')
                        ->label(__('resource.haki.is_kkn_output'))
                        ->helperText(__('resource.haki.kkn_helper')),
                        
                    Forms\Components\Toggle::make('hakiDetail.from_grant_research')
                        ->label(__('resource.haki.from_grant_research'))
                        ->helperText(__('resource.haki.grant_helper')),
                        
                    Forms\Components\Toggle::make('hakiDetail.self_funded')
                        ->label(__('resource.haki.self_funded'))
                        ->helperText(__('resource.haki.self_funded_helper')),
                        
                    Forms\Components\TextInput::make('hakiDetail.inventors_name')
                        ->label(__('resource.haki.authors_creators'))
                        ->helperText(__('resource.haki.authors_helper'))
                        ->required(),
                        
                    Forms\Components\TextInput::make('hakiDetail.registration_number')
                        ->label(__('resource.haki.registration_number'))
                        ->helperText(__('resource.haki.registration_number_helper')),
                        
                    Forms\Components\DatePicker::make('hakiDetail.registration_date')
                        ->label(__('resource.haki.registration_date'))
                        ->helperText(__('resource.haki.registration_date_helper')),
                ])
                ->columns(2)
        ];
    }
}
