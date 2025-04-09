<?php

namespace App\Filament\Forms;

use Filament\Forms;
use Filament\Forms\Get;

class HakiSubmissionForm
{
    public static function getFormSchema(): array
    {
        return [
            Forms\Components\Section::make('Copyright Details')
                ->schema([
                    Forms\Components\TextInput::make('hakiDetail.haki_title')
                        ->label('Work Title')
                        ->helperText('Title of the work being applied for')
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull(),
                        
                    Forms\Components\Select::make('hakiDetail.work_type')
                        ->label('Work Type')
                        ->options([
                            'literary' => 'Literary Work',
                            'musical' => 'Musical Work',
                            'dramatic' => 'Dramatic Work',
                            'artistic' => 'Artistic Work',
                            'audiovisual' => 'Audiovisual Work',
                            'sound_recording' => 'Sound Recording',
                            'computer_program' => 'Computer Program',
                        ])
                        ->helperText('Type of creation (Jenis Ciptaan)')
                        ->required(),
                        
                    Forms\Components\TextInput::make('hakiDetail.work_subtype')
                        ->label('Work Subtype')
                        ->helperText('Sub-type of creation (Sub Jenis Ciptaan)'),
                        
                    Forms\Components\Select::make('hakiDetail.haki_category')
                        ->label('HAKI Category')
                        ->options([
                            'computer' => 'Computer',
                            'non_computer' => 'Non-Computer',
                        ])
                        ->helperText('Computer or Non-Computer Copyright')
                        ->required(),
                        
                    Forms\Components\Textarea::make('hakiDetail.work_description')
                        ->label('Work Description')
                        ->helperText('Brief description of the work')
                        ->required()
                        ->columnSpanFull(),
                        
                    Forms\Components\DatePicker::make('hakiDetail.first_publication_date')
                        ->label('First Publication Date')
                        ->helperText('When the work was first published in print/mass media'),
                        
                    Forms\Components\TextInput::make('hakiDetail.first_publication_place')
                        ->label('Place of Publication')
                        ->helperText('Where the work was first published'),
                        
                    Forms\Components\Toggle::make('hakiDetail.is_kkn_output')
                        ->label('KKN Output')
                        ->helperText('Whether it\'s a KKN (community service) output'),
                        
                    Forms\Components\Toggle::make('hakiDetail.from_grant_research')
                        ->label('From Grant Research')
                        ->helperText('Whether it\'s from research/community service with grant funding'),
                        
                    Forms\Components\Toggle::make('hakiDetail.self_funded')
                        ->label('Self Funded')
                        ->helperText('Whether it will use self-funding'),
                        
                    Forms\Components\TextInput::make('hakiDetail.inventors_name')
                        ->label('Authors/Creators')
                        ->helperText('Names of the inventors/authors')
                        ->required(),
                        
                    Forms\Components\TextInput::make('hakiDetail.registration_number')
                        ->label('Registration Number')
                        ->helperText('Official registration number if already registered'),
                        
                    Forms\Components\DatePicker::make('hakiDetail.registration_date')
                        ->label('Registration Date')
                        ->helperText('Date when the work was officially registered'),
                ])
                ->columns(2)
        ];
    }
}
