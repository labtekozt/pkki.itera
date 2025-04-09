<?php

namespace App\Filament\Forms;

use Filament\Forms;
use Filament\Forms\Get;

class PatentSubmissionForm
{
    public static function getFormSchema(): array
    {
        return [
            Forms\Components\Section::make('Patent Details')
                ->schema([
                    Forms\Components\Select::make('patentDetail.application_type')
                        ->label('Patent Type')
                        ->options([
                            'simple_patent' => 'Simple Patent',
                            'patent' => 'Standard Patent',
                        ])
                        ->helperText('Select either simple patent or standard patent')
                        ->required(),
                        
                    Forms\Components\TextInput::make('patentDetail.patent_title')
                        ->label('Patent Title')
                        ->helperText('Title of the patent application')
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull(),
                        
                    Forms\Components\Textarea::make('patentDetail.patent_description')
                        ->label('Patent Description')
                        ->helperText('Detailed description of the patent invention')
                        ->required()
                        ->columnSpanFull(),
                        
                    Forms\Components\Toggle::make('patentDetail.from_grant_research')
                        ->label('From Grant Research')
                        ->helperText('Whether the invention comes from research/community service that received grant funding'),
                        
                    Forms\Components\Toggle::make('patentDetail.self_funded')
                        ->label('Self Funded')
                        ->helperText('Whether self-funding will be used for this patent application'),
                        
                    Forms\Components\TextInput::make('patentDetail.inventors_name')
                        ->label('Inventors Name')
                        ->helperText('Names of the inventors involved in this patent')
                        ->required(),
                        
                    Forms\Components\TextInput::make('patentDetail.media_link')
                        ->label('Media Link')
                        ->url()
                        ->helperText('Link to video/poster and leaflet (must be accessible). Format: A3 poster containing invention advantages and price'),
                ])
                ->columns(2)
        ];
    }
}
