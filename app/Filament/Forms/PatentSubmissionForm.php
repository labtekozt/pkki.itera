<?php

namespace App\Filament\Forms;

use Filament\Forms;
use Filament\Forms\Get;

class PatentSubmissionForm
{
    public static function getFormSchema(): array
    {
        return [
            Forms\Components\Section::make(__('resource.patent.details'))
                ->schema([
                    Forms\Components\Select::make('patentDetail.application_type')
                        ->label(__('resource.patent.application_type'))
                        ->options([
                            'simple_patent' => __('resource.patent.simple_patent'),
                            'patent' => __('resource.patent.standard_patent'),
                        ])
                        ->helperText(__('resource.patent.type_helper'))
                        ->required(),
                        
                    Forms\Components\TextInput::make('patentDetail.patent_title')
                        ->label(__('resource.patent.patent_title'))
                        ->helperText(__('resource.patent.title_helper'))
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull(),
                        
                    Forms\Components\Textarea::make('patentDetail.patent_description')
                        ->label(__('resource.patent.patent_description'))
                        ->helperText(__('resource.patent.description_helper'))
                        ->required()
                        ->columnSpanFull(),
                        
                    Forms\Components\Toggle::make('patentDetail.from_grant_research')
                        ->label(__('resource.patent.from_grant_research'))
                        ->helperText(__('resource.patent.grant_helper')),
                        
                    Forms\Components\Toggle::make('patentDetail.self_funded')
                        ->label(__('resource.patent.self_funded'))
                        ->helperText(__('resource.patent.self_funded_helper')),
                        
                    Forms\Components\TextInput::make('patentDetail.inventors_name')
                        ->label(__('resource.patent.inventors_name'))
                        ->helperText(__('resource.patent.inventors_helper'))
                        ->required(),
                        
                    Forms\Components\TextInput::make('patentDetail.media_link')
                        ->label(__('resource.patent.media_link'))
                        ->helperText(__('resource.patent.media_helper'))
                        ->url()
                        ->helperText('Link to video/poster and leaflet (must be accessible). Format: A3 poster containing invention advantages and price'),
                ])
                ->columns(2)
        ];
    }
}
