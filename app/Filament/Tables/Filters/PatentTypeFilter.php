<?php

namespace App\Filament\Tables\Filters;

use App\Models\SubmissionType;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;

class PatentTypeFilter extends SelectFilter
{
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->label(__('resource.patent_form.patent_type'));
        
        $this->options([
            'utility' => __('resource.patent_form.types.utility'),
            'design' => __('resource.patent_form.types.design'),
            'plant' => __('resource.patent_form.types.plant'),
            'process' => __('resource.patent_form.types.process'),
        ]);
        
        $this->query(function (Builder $query, array $data) {
            if (!isset($data['value']) || $data['value'] === '') {
                return $query;
            }
            
            // First, ensure we're only looking at Patent submissions
            $patentType = SubmissionType::where('slug', 'paten')->first();
            
            if (!$patentType) {
                return $query;
            }
            
            return $query
                ->where('submission_type_id', $patentType->id)
                ->whereHas('patentDetail', function (Builder $query) use ($data) {
                    $query->where('patent_type', $data['value']);
                });
        });
    }
}
