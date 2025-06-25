<?php

namespace App\Filament\Forms;

use Filament\Forms;
use Filament\Forms\Get;

class BrandSubmissionForm
{
    public static function getFormSchema(): array
    {
        return [
            Forms\Components\Section::make(__('resource.brand.details'))
                ->schema([
                    Forms\Components\TextInput::make('brandDetail.brand_name')
                        ->label(__('resource.brand.brand_name'))
                        ->required()
                        ->helperText('Nama merek yang diajukan')
                        ->maxLength(255),
                        
                    Forms\Components\Select::make('brandDetail.brand_type')
                        ->label(__('resource.brand.brand_type'))
                        ->options([
                            'word' => 'Merek Kata',
                            'logo' => 'Merek Logo',
                            'combined' => 'Merek Kombinasi',
                            'sound' => 'Merek Suara',
                            'collective' => 'Merek Kolektif',
                        ])
                        ->helperText('Tipe merek: kata, logo, kombinasi, suara, dll')
                        ->required(),
                        
                    Forms\Components\Textarea::make('brandDetail.brand_description')
                        ->label(__('resource.brand.brand_description'))
                        ->helperText('Deskripsi rinci tentang merek yang diajukan')
                        ->required()
                        ->columnSpanFull(),
                        
                    Forms\Components\TextInput::make('brandDetail.inovators_name')
                        ->label(__('resource.brand.innovators_name'))
                        ->helperText('Nama inovator yang mengajukan permohonan merek')
                        ->required(),
                        
                    Forms\Components\Select::make('brandDetail.application_type')
                        ->label(__('resource.brand.application_type'))
                        ->options([
                            'trading' => 'Merek Dagang',
                            'service' => 'Merek Jasa',
                            'collective' => 'Merek Kolektif',
                            'trading_and_service' => 'Merek Dagang & Jasa',
                        ])
                        ->helperText('Tipe permohonan: merek dagang, jasa, kolektif, atau dagang&jasa')
                        ->required(),
                        
                    Forms\Components\DatePicker::make('brandDetail.application_date')
                        ->label(__('resource.brand.application_date'))
                        ->helperText('Tanggal pengajuan permohonan merek'),
                        
                    Forms\Components\Select::make('brandDetail.application_origin')
                        ->label(__('resource.brand.application_origin'))
                        ->options([
                            'indonesia' => 'Indonesia',
                            'foreign' => 'Foreign',
                        ])
                        ->helperText('Asal permohonan merek yang diajukan')
                        ->required(),
                        
                    Forms\Components\Select::make('brandDetail.application_category')
                        ->label(__('resource.brand.application_category'))
                        ->options([
                            'umkm' => 'UMKM',
                            'general' => 'UMUM',
                        ])
                        ->helperText('Kategori permohonan: UMKM atau UMUM')
                        ->required(),
                        
                    Forms\Components\TextInput::make('brandDetail.nice_classes')
                        ->label(__('resource.brand.nice_classes'))
                        ->placeholder('e.g., 9, 42')
                        ->helperText('Kelas klasifikasi Nice untuk merek'),
                        
                    Forms\Components\Textarea::make('brandDetail.goods_services_search')
                        ->label(__('resource.brand.goods_services_search'))
                        ->helperText('Kata kunci untuk pencarian uraian barang/jasa')
                        ->columnSpanFull(),
                        
                    Forms\Components\TextInput::make('brandDetail.brand_label')
                        ->label(__('resource.brand_form.brand_label'))
                        ->helperText('Label merek yang diajukan')
                        ->required(),
                        
                    Forms\Components\TextInput::make('brandDetail.brand_label_reference')
                        ->label(__('resource.brand.brand_label_reference'))
                        ->helperText('Nama referensi dari label merek'),
                        
                    Forms\Components\Textarea::make('brandDetail.brand_label_description')
                        ->label(__('resource.brand.brand_label_description'))
                        ->helperText('Deskripsi rinci tentang label merek yang diajukan')
                        ->required(),
                        
                    Forms\Components\TextInput::make('brandDetail.brand_color_elements')
                        ->label(__('resource.brand.brand_color_elements'))
                        ->helperText('Elemen warna yang terdapat dalam label merek'),
                        
                    Forms\Components\TextInput::make('brandDetail.foreign_language_translation')
                        ->label(__('resource.brand.foreign_language_translation'))
                        ->helperText('Terjemahan Bahasa Indonesia jika merek menggunakan bahasa asing'),
                        
                    Forms\Components\TextInput::make('brandDetail.disclaimer')
                        ->label(__('resource.brand.disclaimer'))
                        ->helperText('Pernyataan penolakan hak eksklusif atas elemen tertentu dalam merek'),
                        
                    Forms\Components\TextInput::make('brandDetail.priority_number')
                        ->label(__('resource.brand_form.priority_number'))
                        ->helperText('Nomor prioritas jika mengklaim hak prioritas'),
                ])
                ->columns(2)
        ];
    }
}
