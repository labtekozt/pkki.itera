<?php

namespace App\Filament\Forms;

use Filament\Forms;
use Filament\Forms\Get;

class IndustrialDesignSubmissionForm
{
    public static function getFormSchema(): array
    {
        return [
            Forms\Components\Section::make('Industrial Design Details')
                ->schema([
                    Forms\Components\TextInput::make('industrialDesignDetail.design_title')
                        ->label('Design Title')
                        ->helperText('Title of the industrial design')
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull(),
                        
                    Forms\Components\Select::make('industrialDesignDetail.design_type')
                        ->label('Design Type')
                        ->options([
                            'product' => 'Product Design',
                            'packaging' => 'Packaging Design',
                            'graphical_user_interface' => 'Graphical User Interface',
                            'typography' => 'Typography',
                            'other' => 'Other',
                        ])
                        ->helperText('Type of industrial design (e.g., product, packaging)')
                        ->required(),
                        
                    Forms\Components\Textarea::make('industrialDesignDetail.design_description')
                        ->label('Design Description')
                        ->helperText('Description of the design')
                        ->required()
                        ->columnSpanFull(),
                        
                    Forms\Components\Textarea::make('industrialDesignDetail.novelty_statement')
                        ->label('Novelty Statement')
                        ->helperText('Statement of novelty - explain what makes this design new and original')
                        ->required()
                        ->columnSpanFull(),
                        
                    Forms\Components\TextInput::make('industrialDesignDetail.inventors_name')
                        ->label('Inventors Name')
                        ->helperText('Name of the inventor(s)')
                        ->required(),
                        
                    Forms\Components\Textarea::make('industrialDesignDetail.designer_information')
                        ->label('Designer Information')
                        ->helperText('Information about the designer(s)')
                        ->required(),
                        
                    Forms\Components\TextInput::make('industrialDesignDetail.locarno_class')
                        ->label('Locarno Classification')
                        ->helperText('Locarno Classification number for this industrial design'),
                ])
                ->columns(2)
        ];
    }
}
