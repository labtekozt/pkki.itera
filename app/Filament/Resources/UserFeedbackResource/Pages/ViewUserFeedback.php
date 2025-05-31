<?php

namespace App\Filament\Resources\UserFeedbackResource\Pages;

use App\Filament\Resources\UserFeedbackResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\Fieldset;
use Filament\Support\Colors\Color;

class ViewUserFeedback extends ViewRecord
{
    protected static string $resource = UserFeedbackResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('mark_processed')
                ->label('Mark as Processed')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn () => !$this->record->processed)
                ->action(function () {
                    $this->record->update([
                        'processed' => true,
                        'processed_at' => now(),
                    ]);
                    $this->notify('success', 'Feedback marked as processed');
                }),
                
            Actions\Action::make('add_note')
                ->label('Add Admin Note')
                ->icon('heroicon-o-chat-bubble-left-right')
                ->color('primary')
                ->form([
                    \Filament\Forms\Components\Textarea::make('admin_notes')
                        ->label('Admin Notes')
                        ->placeholder('Add internal notes about this feedback...')
                        ->rows(4)
                        ->required(),
                ])
                ->action(function (array $data) {
                    $currentNotes = $this->record->admin_notes;
                    $newNote = "[" . now()->format('Y-m-d H:i') . "] " . auth()->user()->fullname . ": " . $data['admin_notes'];
                    
                    $this->record->update([
                        'admin_notes' => $currentNotes ? $currentNotes . "\n\n" . $newNote : $newNote,
                    ]);
                    
                    $this->notify('success', 'Admin note added successfully');
                }),
                
            Actions\EditAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Feedback Overview')
                    ->schema([
                        TextEntry::make('user.fullname')
                            ->label('User')
                            ->icon('heroicon-o-user')
                            ->color('primary'),
                            
                        TextEntry::make('overall_rating')
                            ->label('Overall Rating')
                            ->formatStateUsing(fn ($state) => str_repeat('⭐', $state) . " ({$state}/5)")
                            ->color(fn ($state) => match(true) {
                                $state >= 4 => 'success',
                                $state >= 3 => 'warning',
                                default => 'danger'
                            }),
                            
                        TextEntry::make('difficulty_level')
                            ->label('Difficulty Level')
                            ->badge()
                            ->color(fn ($state) => match($state) {
                                'very_easy' => 'success',
                                'easy' => 'success',
                                'moderate' => 'warning',
                                'difficult' => 'danger',
                                'very_difficult' => 'danger',
                                default => 'gray'
                            }),
                            
                        IconEntry::make('processed')
                            ->label('Status')
                            ->boolean()
                            ->trueIcon('heroicon-o-check-circle')
                            ->falseIcon('heroicon-o-clock')
                            ->trueColor('success')
                            ->falseColor('warning'),
                    ])->columns(2),

                Section::make('User Demographics')
                    ->schema([
                        TextEntry::make('age_group')
                            ->label('Age Group')
                            ->badge(),
                            
                        TextEntry::make('tech_comfort_level')
                            ->label('Tech Comfort')
                            ->badge()
                            ->color(fn ($state) => match($state) {
                                'expert' => 'success',
                                'advanced' => 'info',
                                'intermediate' => 'warning',
                                'beginner' => 'danger',
                                default => 'gray'
                            }),
                            
                        TextEntry::make('primary_device')
                            ->label('Primary Device')
                            ->icon(fn ($state) => match($state) {
                                'desktop' => 'heroicon-o-computer-desktop',
                                'laptop' => 'heroicon-o-computer-desktop',
                                'mobile' => 'heroicon-o-device-phone-mobile',
                                'tablet' => 'heroicon-o-device-tablet',
                                default => 'heroicon-o-device-phone-mobile'
                            }),
                    ])->columns(3),

                Section::make('Feedback Details')
                    ->schema([
                        TextEntry::make('feedback_text')
                            ->label('Feedback')
                            ->markdown()
                            ->columnSpanFull(),
                            
                        TextEntry::make('suggestions')
                            ->label('User Suggestions')
                            ->markdown()
                            ->visible(fn ($record) => !empty($record->suggestions))
                            ->columnSpanFull(),
                    ]),

                Section::make('Specific Ratings')
                    ->schema([
                        Fieldset::make('Usability Ratings')
                            ->schema([
                                TextEntry::make('ease_of_use_rating')
                                    ->label('Ease of Use')
                                    ->formatStateUsing(fn ($state) => $state ? str_repeat('⭐', $state) . " ({$state}/5)" : 'Not rated'),
                                    
                                TextEntry::make('navigation_rating')
                                    ->label('Navigation')
                                    ->formatStateUsing(fn ($state) => $state ? str_repeat('⭐', $state) . " ({$state}/5)" : 'Not rated'),
                                    
                                TextEntry::make('visual_clarity_rating')
                                    ->label('Visual Clarity')
                                    ->formatStateUsing(fn ($state) => $state ? str_repeat('⭐', $state) . " ({$state}/5)" : 'Not rated'),
                                    
                                TextEntry::make('response_time_rating')
                                    ->label('Response Time')
                                    ->formatStateUsing(fn ($state) => $state ? str_repeat('⭐', $state) . " ({$state}/5)" : 'Not rated'),
                            ])->columns(2),
                    ]),

                Section::make('Technical Information')
                    ->schema([
                        TextEntry::make('browser_info.browser')
                            ->label('Browser')
                            ->visible(fn ($record) => !empty($record->browser_info['browser'])),
                            
                        TextEntry::make('browser_info.version')
                            ->label('Browser Version')
                            ->visible(fn ($record) => !empty($record->browser_info['version'])),
                            
                        TextEntry::make('browser_info.platform')
                            ->label('Platform')
                            ->visible(fn ($record) => !empty($record->browser_info['platform'])),
                            
                        TextEntry::make('created_at')
                            ->label('Submitted At')
                            ->dateTime()
                            ->since(),
                    ])->columns(2),

                Section::make('Admin Notes')
                    ->schema([
                        TextEntry::make('admin_notes')
                            ->label('Internal Notes')
                            ->markdown()
                            ->placeholder('No admin notes yet')
                            ->columnSpanFull(),
                    ])
                    ->visible(fn ($record) => !empty($record->admin_notes)),
            ]);
    }
}
