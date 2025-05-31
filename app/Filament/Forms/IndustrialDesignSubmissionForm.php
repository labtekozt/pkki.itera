<?php

namespace App\Filament\Forms;

use Filament\Forms;
use Filament\Forms\Get;

class IndustrialDesignSubmissionForm
{
    public static function getFormSchema(): array
    {
        return [
            Forms\Components\Section::make(__('resource.industrial_design.details'))
                ->schema([
                    Forms\Components\TextInput::make('industrialDesignDetail.design_title')
                        ->label(__('resource.industrial_design.design_title'))
                        ->helperText(__('resource.industrial_design.design_title_helper'))
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull(),
                        
                    Forms\Components\Select::make('industrialDesignDetail.design_type')
                        ->label(__('resource.industrial_design.design_type'))
                        ->options([
                            'product' => __('resource.industrial_design.product_design'),
                            'packaging' => __('resource.industrial_design.packaging_design'),
                            'graphical_user_interface' => __('resource.industrial_design.gui_design'),
                            'typography' => __('resource.industrial_design.typography'),
                            'other' => __('resource.industrial_design.other'),
                        ])
                        ->helperText(__('resource.industrial_design.design_type_helper'))
                        ->required(),
                        
                    Forms\Components\Textarea::make('industrialDesignDetail.design_description')
                        ->label(__('resource.industrial_design.design_description'))
                        ->helperText(__('resource.industrial_design.description_helper'))
                        ->required()
                        ->columnSpanFull(),
                        
                    Forms\Components\Textarea::make('industrialDesignDetail.novelty_statement')
                        ->label(__('resource.industrial_design.novelty_statement'))
                        ->helperText(__('resource.industrial_design.novelty_helper'))
                        ->required()
                        ->columnSpanFull(),
                        
                    Forms\Components\TextInput::make('industrialDesignDetail.inventors_name')
                        ->label(__('resource.industrial_design.inventors_name'))
                        ->helperText(__('resource.industrial_design.inventors_helper'))
                        ->required(),
                        
                    Forms\Components\Textarea::make('industrialDesignDetail.designer_information')
                        ->label(__('resource.industrial_design.designer_information'))
                        ->helperText(__('resource.industrial_design.designer_helper'))
                        ->required(),
                        
                    Forms\Components\TextInput::make('industrialDesignDetail.locarno_class')
                        ->label(__('resource.industrial_design.locarno_class'))
                        ->helperText(__('resource.industrial_design.locarno_helper')),
                ])
                ->columns(2)
        ];
    }
}
