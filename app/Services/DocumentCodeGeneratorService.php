<?php

namespace App\Services;

use App\Models\SubmissionType;

class DocumentCodeGeneratorService
{
    /**
     * Generate standard document code
     * 
     * @param string $submissionTypeSlug The submission type slug (paten, brand, haki, industrial_design)
     * @param string $requirementName Short name for the requirement
     * @param int $sequence Sequential number within the submission type
     * @return string Generated standard code
     */
    public function generateStandardCode(string $submissionTypeSlug, string $requirementName, int $sequence): string
    {
        $prefix = $this->getSubmissionTypePrefix($submissionTypeSlug);
        $nameMnemonic = $this->createNameMnemonic($requirementName);
        $sequenceNumber = str_pad($sequence, 3, '0', STR_PAD_LEFT);
        
        return "ST{$prefix}-{$nameMnemonic}-{$sequenceNumber}";
    }
    
    /**
     * Get prefix for submission type
     * 
     * @param string $submissionTypeSlug
     * @return string
     */
    private function getSubmissionTypePrefix(string $submissionTypeSlug): string
    {
        return match ($submissionTypeSlug) {
            'paten' => 'PAT',
            'brand' => 'BRD',
            'haki' => 'HAK',
            'industrial_design' => 'IND',
            default => 'GEN',
        };
    }
    
    /**
     * Create a mnemonic (abbreviation) from requirement name
     * Takes first 4 characters, converts to uppercase
     * 
     * @param string $requirementName
     * @return string
     */
    private function createNameMnemonic(string $requirementName): string
    {
        // Extract words
        $words = explode(' ', preg_replace('/[^a-zA-Z0-9\s]/', '', $requirementName));
        
        // If single word, take first 4 characters
        if (count($words) === 1) {
            return strtoupper(substr($words[0], 0, 4));
        }
        
        // Otherwise, take first character from each word, up to 4 characters
        $mnemonic = '';
        foreach ($words as $word) {
            if (strlen($mnemonic) < 4 && !empty($word)) {
                $mnemonic .= strtoupper(substr($word, 0, 1));
            }
        }
        
        // If still less than 4 characters, pad with first word additional characters
        if (strlen($mnemonic) < 4 && !empty($words[0])) {
            $mnemonic .= strtoupper(substr($words[0], 1, 4 - strlen($mnemonic)));
        }
        
        return $mnemonic;
    }
    
    /**
     * Generate standard codes for a batch of requirements
     * 
     * @param SubmissionType $submissionType
     * @param array $requirements
     * @return array Updated requirements with standard codes
     */
    public function generateBatchCodes(SubmissionType $submissionType, array $requirements): array
    {
        $updatedRequirements = [];
        $sequence = 1;
        
        foreach ($requirements as $requirement) {
            if (!isset($requirement['standard_code'])) {
                $requirementName = isset($requirement['name']) ? $requirement['name'] : $requirement['code'];
                $requirement['standard_code'] = $this->generateStandardCode(
                    $submissionType->slug,
                    $requirementName,
                    $sequence
                );
            }
            
            $updatedRequirements[] = $requirement;
            $sequence++;
        }
        
        return $updatedRequirements;
    }
}
