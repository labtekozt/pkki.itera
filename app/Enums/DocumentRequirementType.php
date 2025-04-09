<?php

namespace App\Enums;

enum DocumentRequirementType: string
{
    case PDF = 'pdf';
    case WORD = 'word';
    case IMAGE = 'image';
    case SPREADSHEET = 'spreadsheet';
    case OTHER = 'other';
    
    public function label(): string
    {
        return match($this) {
            self::PDF => 'PDF Document',
            self::WORD => 'Word Document',
            self::IMAGE => 'Image File',
            self::SPREADSHEET => 'Spreadsheet',
            self::OTHER => 'Other Document Type',
        };
    }
    
    public function allowedExtensions(): array
    {
        return match($this) {
            self::PDF => ['pdf'],
            self::WORD => ['doc', 'docx'],
            self::IMAGE => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
            self::SPREADSHEET => ['xls', 'xlsx', 'csv'],
            self::OTHER => ['*'],
        };
    }
}
