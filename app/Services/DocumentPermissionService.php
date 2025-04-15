<?php

namespace App\Services;

use Illuminate\Support\Facades\Auth;

/**
 * Document Permission Service
 * 
 * This service handles permission checks for document-related operations
 * following the Single Responsibility Principle.
 */
class DocumentPermissionService
{
    /**
     * Check if the current user can edit documents.
     * Only administrators and super admins can edit documents.
     *
     * @return bool
     */
    public function canEditDocuments(): bool
    {
        $user = Auth::user();
        
        if (!$user) {
            return false;
        }
        
        // Check if user has admin or super_admin role
        return $user->hasRole('admin') || 
               $user->hasRole('super_admin') || 
               $user->isSuperAdmin();
    }
    
    /**
     * Check if the current user can review documents.
     * Only administrators and super admins can review documents.
     *
     * @return bool
     */
    public function canReviewDocuments(): bool
    {
        $user = Auth::user();
        
        if (!$user) {
            return false;
        }
        
        // Check if user has permission to review submissions
        // This could be a specific permission or based on roles
        return $user->can('review_submissions') || 
               $user->hasRole('admin') || 
               $user->hasRole('super_admin') || 
               $user->isSuperAdmin();
    }
    
    /**
     * Check if the current user owns a submission.
     * Used to determine if they can update their own documents.
     *
     * @param int|string $submissionUserId
     * @return bool
     */
    public function isSubmissionOwner($submissionUserId): bool
    {
        $user = Auth::user();
        
        if (!$user) {
            return false;
        }
        
        return $user->id === $submissionUserId;
    }
    
    /**
     * Check if the current user can view document details.
     * All authenticated users can view documents.
     *
     * @return bool
     */
    public function canViewDocuments(): bool
    {
        return Auth::check();
    }
}