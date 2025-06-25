<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class UserDetailRelationManager extends RelationManager
{
    protected static string $relationship = 'detail';

    protected static ?string $recordTitleAttribute = 'alamat';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Contact Information')
                    ->schema([
                        Forms\Components\TextInput::make('phonenumber')
                            ->label(__('resource.user_detail.phone_number'))
                            ->tel()
                            ->required()
                            ->telRegex('/^[+]*[(]{0,1}[0-9]{1,4}[)]{0,1}[-\s\.\/0-9]*$/')
                            ->placeholder('+62 812 3456 7890')
                            ->maxLength(255),
                            
                        Forms\Components\Textarea::make('alamat')
                            ->label('Address')
                            ->required()
                            ->placeholder('Enter complete address')
                            ->rows(3)
                            ->columnSpanFull()
                            ->maxLength(255),
                    ])
                    ->columns(1),
                    
                Forms\Components\Section::make('Academic Information')
                    ->schema([
                        Forms\Components\TextInput::make('jurusan')
                            ->label('Department')
                            ->required()
                            ->placeholder('e.g., Computer Science')
                            ->maxLength(255),
                            
                        Forms\Components\TextInput::make('prodi')
                            ->label('Program Studi')
                            ->required()
                            ->placeholder('e.g., Software Engineering')
                            ->maxLength(255),
                    ])
                    ->columns(2),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('alamat'),
                Tables\Columns\TextColumn::make('phonenumber'),
                Tables\Columns\TextColumn::make('prodi')
                    ->label('Program Studi'),
                Tables\Columns\TextColumn::make('jurusan'),
            ])
            ->defaultSort('id') // Add default sort by 'id'
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
