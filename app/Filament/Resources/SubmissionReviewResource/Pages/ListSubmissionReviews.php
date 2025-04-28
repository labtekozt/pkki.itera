<?php

namespace App\Filament\Resources\SubmissionReviewResource\Pages;

use App\Filament\Resources\SubmissionReviewResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSubmissionReviews extends ListRecords
{
    protected static string $resource = SubmissionReviewResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // No create action needed for review listing
        ];
    }
}