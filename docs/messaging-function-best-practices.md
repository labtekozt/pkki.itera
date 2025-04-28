# Messaging and Function Best Practices Guide

## Overview
This document outlines standardized patterns for messaging and function designs in our Laravel application. Following these guidelines will ensure consistency, maintainability, and clarity throughout the codebase.

## Function Design Best Practices

### Default Parameters

1. **Use Nullable Types with Defaults**
   ```php
   public function processSomething(?User $user = null, ?string $comment = null): Result
   ```

2. **Set Sensible Defaults**
   - Use the authenticated user as default for user parameters:
     ```php
     $user = $user ?? Auth::user();
     ```
   - Set meaningful default messages:
     ```php
     $comment = $comment ?? 'Default explanatory message';
     ```

3. **Use Parameter Arrays for Optional Settings**
   ```php
   public function doSomething(
       RequiredType $required,
       array $options = []
   ) {
       $options = array_merge([
           'setting1' => 'default1',
           'setting2' => 'default2',
       ], $options);
   }
   ```

### Return Types

1. **Always Specify Return Types**
   ```php
   public function getSubmission(int $id): ?Submission
   ```

2. **For Collection Methods**
   ```php
   public function getSubmissions(): \Illuminate\Support\Collection
   ```

3. **For Paginated Results**
   ```php
   public function paginateSubmissions(): \Illuminate\Pagination\LengthAwarePaginator
   ```

### Documentation

1. **Use Full PHPDoc Blocks**
   ```php
   /**
    * Process a submission for review.
    *
    * @param Submission $submission The submission to process
    * @param User|null $reviewer The reviewer (defaults to authenticated user)
    * @param string|null $notes Additional notes about the review
    * @param array $options Optional configuration parameters
    * @return Submission The processed submission
    * @throws \RuntimeException When submission cannot be processed
    */
   ```

2. **Document Each Parameter**
   - Describe what each parameter does
   - Note default values in the documentation
   - Document expected formats for complex parameters

3. **Document Exceptions**
   - Always specify what exceptions might be thrown
   - Explain the conditions that trigger exceptions

## Messaging Best Practices

### Notification Messages

1. **Success Messages**
   - Should be positive and specific
   - Format: "[Action] successfully [completed]"
   - Examples:
     - "Submission successfully approved"
     - "Document successfully uploaded"

2. **Error Messages**
   - Should explain what went wrong and suggest a solution
   - Format: "Cannot [action]: [reason]."
   - Examples:
     - "Cannot approve submission: Required documents missing."
     - "Cannot proceed: Permission denied."

3. **Warning Messages**
   - For non-blocking issues that need attention
   - Format: "[Attention needed]: [description]"
   - Examples: 
     - "Attention needed: This action will remove all existing assignments."

4. **Information Messages**
   - For neutral information sharing
   - Format: Clear, concise statement
   - Examples:
     - "Your submission is currently under review."

### Email Notifications

1. **Subjects**
   - Should clearly identify the topic
   - Format: "[System Name] - [Action/Status] - [Object]"
   - Example: "PKKI ITERA - Revision Needed - Patent Application"

2. **Email Bodies**
   - Begin with greeting using recipient's name
   - Clearly state the purpose in the first paragraph
   - Provide action steps if applicable
   - End with contact information

3. **Template Structure**
   ```
   Hello [Name],

   [Main message - one sentence summary]

   [Details paragraph - 2-3 sentences explaining context]

   [Action paragraph - what to do next, if applicable]

   If you have any questions, please contact [contact info].

   Regards,
   PKKI ITERA Team
   ```

## Database Event Tracking

1. **Event Types**
   - Use consistent event types: 'state_change', 'document_update', 'review_decision', etc.

2. **Action Verbs**
   - Use precise action verbs in present tense: 'approve', 'reject', 'submit', 'request_revision'

3. **Status Terms**
   - Use consistent status terms: 'pending', 'in_review', 'approved', 'rejected', etc.

4. **Timestamps**
   - Use the 'event_timestamp' field to record when events actually happened
   - Use 'created_at' for when records were created

## Examples

### Well-Designed Function Example

```php
/**
 * Request revisions for a submission.
 *
 * @param Submission $submission The submission requiring revisions
 * @param User|null $processor The user requesting revisions (defaults to authenticated user)
 * @param string|null $comment Explanation for the revision request
 * @param array $metadata Additional context data for the tracking event
 * @return Submission The updated submission
 * @throws \RuntimeException When submission has no current stage
 */
public function requestRevisions(
    Submission $submission,
    ?User $processor = null, 
    ?string $comment = null,
    array $metadata = []
): Submission {
    // Implementation here
}
```

### Well-Structured Error Handling

```php
try {
    // Attempt something
} catch (\Exception $e) {
    Log::error('Failed to process submission: ' . $e->getMessage(), [
        'submission_id' => $submission->id,
        'user_id' => auth()->id(),
        'exception' => $e
    ]);
    
    throw new \RuntimeException(
        'Cannot process submission: ' . $this->getUserFriendlyMessage($e),
        0,
        $e
    );
}
```