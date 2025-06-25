<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserFeedbackResource\Pages;
use App\Models\UserFeedback;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\BulkAction;
use Filament\Support\Colors\Color;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Collection;

class UserFeedbackResource extends Resource
{
    protected static ?string $model = UserFeedback::class;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';
    
    protected static ?string $navigationLabel = 'Feedback Pengguna';
    
    protected static ?string $modelLabel = 'Feedback Pengguna';
    
    protected static ?string $pluralModelLabel = 'Feedback Pengguna';
    
    protected static ?string $navigationGroup = 'Analitik & Laporan';
    
    protected static ?int $navigationSort = 30;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Status Pemrosesan')
                    ->schema([
                        Forms\Components\Textarea::make('admin_notes')
                            ->label('Catatan Admin')
                            ->placeholder('Tambahkan catatan pemrosesan...')
                            ->rows(3),
                            
                        Forms\Components\Toggle::make('is_critical')
                            ->label('Tandai sebagai Kritis')
                            ->helperText('Feedback yang memerlukan perhatian segera'),
                    ])
                    ->columns(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('rating')
                    ->label('Rating')
                    ->formatStateUsing(fn ($state) => str_repeat('⭐', $state))
                    ->color(fn ($state) => match(true) {
                        $state <= 2 => Color::Red,
                        $state <= 3 => Color::Yellow,
                        default => Color::Green,
                    })
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('user.fullname')
                    ->label('Pengguna')
                    ->searchable()
                    ->sortable()
                    ->placeholder('Anonim'),
                    
                Tables\Columns\TextColumn::make('page_title')
                    ->label('Halaman')
                    ->searchable()
                    ->limit(50)
                    ->tooltip(fn ($record) => $record->page_url),
                    
                Tables\Columns\TextColumn::make('age_range_label')
                    ->label('Usia')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('tech_comfort_label')
                    ->label('Kemampuan Teknologi')
                    ->sortable(),
                    
                Tables\Columns\IconColumn::make('is_critical')
                    ->label('Kritis')
                    ->boolean()
                    ->trueIcon('heroicon-o-exclamation-triangle')
                    ->falseIcon('heroicon-o-check-circle')
                    ->trueColor(Color::Red)
                    ->falseColor(Color::Green),
                    
                Tables\Columns\IconColumn::make('processed_at')
                    ->label(__('resource.submission.fields.status'))
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-clock')
                    ->trueColor('success')
                    ->falseColor('warning')
                    ->tooltip(fn ($record) => $record->processed_at 
                        ? 'Diproses pada ' . $record->processed_at->format('d/m/Y H:i')
                        : 'Belum diproses'),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Tanggal')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('rating')
                    ->label('Rating')
                    ->options([
                        1 => '⭐ (1 bintang)',
                        2 => '⭐⭐ (2 bintang)',
                        3 => '⭐⭐⭐ (3 bintang)',
                        4 => '⭐⭐⭐⭐ (4 bintang)',
                        5 => '⭐⭐⭐⭐⭐ (5 bintang)',
                    ]),
                    
                SelectFilter::make('is_critical')
                    ->label('Status Kritis')
                    ->options([
                        1 => 'Kritis',
                        0 => 'Normal',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['value'],
                            fn (Builder $query) => $query->where('is_critical', (bool) $data['value'])
                        );
                    }),
                    
                Filter::make('unprocessed')
                    ->label('Belum Diproses')
                    ->query(fn (Builder $query) => $query->whereNull('processed_at'))
                    ->toggle(),
                    
                SelectFilter::make('age_range')
                    ->label('Rentang Usia')
                    ->options([
                        'under_30' => 'Di bawah 30 tahun',
                        '30_45' => '30-45 tahun',
                        '46_60' => '46-60 tahun',
                        '61_70' => '61-70 tahun',
                        'over_70' => 'Di atas 70 tahun',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Lihat Detail'),
                    
                Action::make('process')
                    ->label('Proses')
                    ->icon('heroicon-o-check')
                    ->color(Color::Green)
                    ->visible(fn ($record) => !$record->processed_at)
                    ->form([
                        Textarea::make('admin_notes')
                            ->label('Catatan Pemrosesan')
                            ->placeholder('Tambahkan catatan tentang bagaimana feedback ini ditindaklanjuti...')
                            ->required(),
                    ])
                    ->action(function ($record, array $data) {
                        $record->markAsProcessed(auth()->user(), $data['admin_notes']);
                        
                        Notification::make()
                            ->title('Feedback berhasil diproses')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                BulkAction::make('mark_as_processed')
                    ->label('Tandai Sudah Diproses')
                    ->icon('heroicon-o-check-circle')
                    ->color(Color::Green)
                    ->form([
                        Textarea::make('admin_notes')
                            ->label('Catatan Pemrosesan')
                            ->placeholder('Catatan untuk semua feedback yang dipilih...')
                            ->required(),
                    ])
                    ->action(function (Collection $records, array $data) {
                        $records->each(function ($record) use ($data) {
                            if (!$record->processed_at) {
                                $record->markAsProcessed(auth()->user(), $data['admin_notes']);
                            }
                        });
                        
                        Notification::make()
                            ->title($records->count() . ' feedback berhasil diproses')
                            ->success()
                            ->send();
                    }),
            ])
            ->defaultSort('created_at', 'desc')
            ->poll('30s'); // Auto-refresh every 30 seconds
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Rating & Penilaian')
                    ->schema([
                        TextEntry::make('rating')
                            ->label('Rating')
                            ->formatStateUsing(fn ($state) => str_repeat('⭐', $state) . " ($state/5)")
                            ->color(fn ($state) => match(true) {
                                $state <= 2 => Color::Red,
                                $state <= 3 => Color::Yellow,
                                default => Color::Green,
                            }),
                            
                        TextEntry::make('formatted_difficulty_areas')
                            ->label('Area Kesulitan')
                            ->placeholder('Tidak ada kesulitan yang dilaporkan'),
                    ])
                    ->columns(2),
                    
                Section::make('Informasi Pengguna')
                    ->schema([
                        TextEntry::make('user.fullname')
                            ->label('Nama Pengguna')
                            ->placeholder('Pengguna Anonim'),
                            
                        TextEntry::make('user.email')
                            ->label('Email')
                            ->placeholder('Tidak tersedia'),
                            
                        TextEntry::make('age_range_label')
                            ->label('Rentang Usia'),
                            
                        TextEntry::make('tech_comfort_label')
                            ->label('Kemampuan Teknologi'),
                            
                        TextEntry::make('device_type_label')
                            ->label('Jenis Perangkat'),
                            
                        TextEntry::make('contact_permission')
                            ->label('Izin Kontak')
                            ->formatStateUsing(fn ($state) => $state ? 'Ya, boleh dihubungi' : 'Tidak ingin dihubungi')
                            ->color(fn ($state) => $state ? Color::Green : Color::Gray),
                    ])
                    ->columns(2),
                    
                Section::make('Detail Halaman')
                    ->schema([
                        TextEntry::make('page_title')
                            ->label('Judul Halaman'),
                            
                        TextEntry::make('page_url')
                            ->label('URL Halaman')
                            ->copyable(),
                    ])
                    ->columns(1),
                    
                Section::make('Komentar Pengguna')
                    ->schema([
                        TextEntry::make('comments')
                            ->label('Komentar')
                            ->placeholder('Tidak ada komentar')
                            ->columnSpanFull(),
                    ])
                    ->columns(1),
                    
                Section::make('Informasi Teknis')
                    ->schema([
                        KeyValueEntry::make('browser_info')
                            ->label('Informasi Browser'),
                            
                        KeyValueEntry::make('session_data')
                            ->label('Data Sesi'),
                    ])
                    ->columns(1)
                    ->collapsible(),
                    
                Section::make('Status Pemrosesan')
                    ->schema([
                        TextEntry::make('is_critical')
                            ->label('Status Kritis')
                            ->formatStateUsing(fn ($state) => $state ? 'Kritis' : 'Normal')
                            ->color(fn ($state) => $state ? Color::Red : Color::Green)
                            ->weight(FontWeight::Bold),
                            
                        TextEntry::make('processed_at')
                            ->label('Tanggal Diproses')
                            ->dateTime('d/m/Y H:i')
                            ->placeholder('Belum diproses'),
                            
                        TextEntry::make('processedBy.fullname')
                            ->label('Diproses oleh')
                            ->placeholder('Belum diproses'),
                            
                        TextEntry::make('admin_notes')
                            ->label('Catatan Admin')
                            ->placeholder('Tidak ada catatan')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }
    
    public static function getRelations(): array
    {
        return [
            //
        ];
    }
    
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUserFeedback::route('/'),
            'view' => Pages\ViewUserFeedback::route('/{record}'),
            'edit' => Pages\EditUserFeedback::route('/{record}/edit'),
        ];
    }
    
    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::whereNull('processed_at')->count();
    }
     public static function getNavigationBadgeColor(): ?string
    {
        $unprocessedCount = static::getModel()::whereNull('processed_at')->count();
        $criticalCount = static::getModel()::where('is_critical', true)->whereNull('processed_at')->count();

        if ($criticalCount > 0) {
            return 'danger';
        }
        
        if ($unprocessedCount > 0) {
            return 'warning';
        }
        
        return 'success';
    }
}
