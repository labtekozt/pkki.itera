<?php

namespace App\Filament\Forms;

use Filament\Forms;
use Filament\Forms\Get;

class BrandSubmissionForm
{
    public static function getFormSchema(): array
    {
        return [
            Forms\Components\Section::make('Trademark Details')
                ->schema([
                    Forms\Components\TextInput::make('brandDetail.brand_name')
                        ->label('Brand Name')
                        ->required()
                        ->helperText('Nama merek yang diajukan')
                        ->maxLength(255),
                        
                    Forms\Components\Select::make('brandDetail.brand_type')
                        ->label('Brand Type')
                        ->options([
                            'word' => 'Word Mark',
                            'logo' => 'Logo Mark',
                            'combined' => 'Combined Mark',
                            'sound' => 'Sound Mark',
                            'collective' => 'Collective Mark',
                        ])
                        ->helperText('Tipe merek: kata, logo, kombinasi, suara, dll')
                        ->required(),
                        
                    Forms\Components\Textarea::make('brandDetail.brand_description')
                        ->label('Brand Description')
                        ->helperText('Deskripsi rinci tentang merek yang diajukan')
                        ->required()
                        ->columnSpanFull(),
                        
                    Forms\Components\TextInput::make('brandDetail.inovators_name')
                        ->label('Innovators/Creators Name')
                        ->helperText('Nama inovator yang mengajukan permohonan merek')
                        ->required(),
                        
                    Forms\Components\Select::make('brandDetail.application_type')
                        ->label('Application Type')
                        ->options([
                            'trading' => 'Merek Dagang',
                            'service' => 'Merek Jasa',
                            'collective' => 'Merek Kolektif',
                            'trading_and_service' => 'Merek Dagang & Jasa',
                        ])
                        ->helperText('Tipe permohonan: merek dagang, jasa, kolektif, atau dagang&jasa')
                        ->required(),
                        
                    Forms\Components\DatePicker::make('brandDetail.application_date')
                        ->label('Application Date')
                        ->helperText('Tanggal pengajuan permohonan merek'),
                        
                    Forms\Components\Select::make('brandDetail.application_origin')
                        ->label('Application Origin')
                        ->options([
                            'indonesia' => 'Indonesia',
                            'foreign' => 'Foreign',
                        ])
                        ->helperText('Asal permohonan merek yang diajukan')
                        ->required(),
                        
                    Forms\Components\Select::make('brandDetail.application_category')
                        ->label('Application Category')
                        ->options([
                            'umkm' => 'UMKM',
                            'general' => 'UMUM',
                        ])
                        ->helperText('Kategori permohonan: UMKM atau UMUM')
                        ->required(),
                        
                    Forms\Components\TextInput::make('brandDetail.nice_classes')
                        ->label('Nice Classification')
                        ->placeholder('e.g., 9, 42')
                        ->helperText('Kelas klasifikasi Nice untuk merek'),
                        
                    Forms\Components\Textarea::make('brandDetail.goods_services_search')
                        ->label('Goods & Services')
                        ->helperText('Kata kunci untuk pencarian uraian barang/jasa')
                        ->columnSpanFull(),
                        
                    Forms\Components\TextInput::make('brandDetail.brand_label')
                        ->label('Brand Label')
                        ->helperText('Label merek yang diajukan')
                        ->required(),
                        
                    Forms\Components\TextInput::make('brandDetail.brand_label_reference')
                        ->label('Brand Label Reference')
                        ->helperText('Nama referensi dari label merek'),
                        
                    Forms\Components\Textarea::make('brandDetail.brand_label_description')
                        ->label('Brand Label Description')
                        ->helperText('Deskripsi rinci tentang label merek yang diajukan')
                        ->required(),
                        
                    Forms\Components\TextInput::make('brandDetail.brand_color_elements')
                        ->label('Brand Color Elements')
                        ->helperText('Elemen warna yang terdapat dalam label merek'),
                        
                    Forms\Components\TextInput::make('brandDetail.foreign_language_translation')
                        ->label('Foreign Language Translation')
                        ->helperText('Terjemahan Bahasa Indonesia jika merek menggunakan bahasa asing'),
                        
                    Forms\Components\TextInput::make('brandDetail.disclaimer')
                        ->label('Disclaimer')
                        ->helperText('Pernyataan penolakan hak eksklusif atas elemen tertentu dalam merek'),
                        
                    Forms\Components\TextInput::make('brandDetail.priority_number')
                        ->label('Priority Number')
                        ->helperText('Nomor prioritas jika mengklaim hak prioritas'),
                ])
                ->columns(2)
        ];
    }
}
