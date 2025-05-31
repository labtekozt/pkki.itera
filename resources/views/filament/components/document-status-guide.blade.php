<div class="space-y-6">
    <div class="prose prose-sm max-w-none">
        <p class="text-gray-600">
            This guide explains what each document status means and what actions you may need to take.
        </p>
    </div>

    {{-- Status Definitions --}}
    <div class="space-y-4">
        {{-- Pending Status --}}
        <div class="flex items-start space-x-3 p-4 bg-gray-50 rounded-lg border border-gray-200">
            <div class="flex-shrink-0">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                    Pending
                </span>
            </div>
            <div class="flex-1">
                <h4 class="text-sm font-medium text-gray-900">Pending Review</h4>
                <p class="mt-1 text-sm text-gray-600">
                    Your document has been uploaded and is waiting for reviewer assessment. No action needed from you at this time.
                </p>
            </div>
        </div>

        {{-- Approved Status --}}
        <div class="flex items-start space-x-3 p-4 bg-green-50 rounded-lg border border-green-200">
            <div class="flex-shrink-0">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                    Approved
                </span>
            </div>
            <div class="flex-1">
                <h4 class="text-sm font-medium text-green-900">Document Approved</h4>
                <p class="mt-1 text-sm text-green-700">
                    Your document meets all requirements and has been approved. No further action needed for this document.
                </p>
            </div>
        </div>

        {{-- Revision Needed Status --}}
        <div class="flex items-start space-x-3 p-4 bg-yellow-50 rounded-lg border border-yellow-200">
            <div class="flex-shrink-0">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                    Revision Needed
                </span>
            </div>
            <div class="flex-1">
                <h4 class="text-sm font-medium text-yellow-900">Needs Revision</h4>
                <p class="mt-1 text-sm text-yellow-700">
                    Your document has minor issues that need to be corrected. Review the feedback and upload a revised version.
                </p>
                <div class="mt-2">
                    <p class="text-xs font-medium text-yellow-800">Action Required:</p>
                    <ul class="mt-1 text-xs text-yellow-700 list-disc list-inside">
                        <li>Review the specific feedback provided</li>
                        <li>Make the requested changes</li>
                        <li>Upload the revised document</li>
                    </ul>
                </div>
            </div>
        </div>

        {{-- Rejected Status --}}
        <div class="flex items-start space-x-3 p-4 bg-red-50 rounded-lg border border-red-200">
            <div class="flex-shrink-0">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                    Rejected
                </span>
            </div>
            <div class="flex-1">
                <h4 class="text-sm font-medium text-red-900">Document Rejected</h4>
                <p class="mt-1 text-sm text-red-700">
                    Your document has significant issues and needs to be completely replaced. Review the feedback carefully.
                </p>
                <div class="mt-2">
                    <p class="text-xs font-medium text-red-800">Action Required:</p>
                    <ul class="mt-1 text-xs text-red-700 list-disc list-inside">
                        <li>Carefully review all rejection reasons</li>
                        <li>Create a new document addressing all issues</li>
                        <li>Upload the corrected document</li>
                    </ul>
                </div>
            </div>
        </div>

        {{-- Replaced Status --}}
        <div class="flex items-start space-x-3 p-4 bg-blue-50 rounded-lg border border-blue-200">
            <div class="flex-shrink-0">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                    Replaced
                </span>
            </div>
            <div class="flex-1">
                <h4 class="text-sm font-medium text-blue-900">Document Replaced</h4>
                <p class="mt-1 text-sm text-blue-700">
                    This document has been superseded by a newer version. It's kept for historical reference.
                </p>
            </div>
        </div>

        {{-- Final Status --}}
        <div class="flex items-start space-x-3 p-4 bg-green-50 rounded-lg border border-green-200">
            <div class="flex-shrink-0">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                    Final
                </span>
            </div>
            <div class="flex-1">
                <h4 class="text-sm font-medium text-green-900">Final Version</h4>
                <p class="mt-1 text-sm text-green-700">
                    This is the final approved version of the document. No further changes will be accepted.
                </p>
            </div>
        </div>
    </div>

    {{-- Tips Section --}}
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
        <h4 class="text-sm font-medium text-blue-900 mb-3 flex items-center">
            @svg('heroicon-o-light-bulb', 'h-4 w-4 mr-2')
            Tips for Successful Document Review
        </h4>
        <ul class="space-y-2 text-sm text-blue-800">
            <li class="flex items-start">
                <span class="flex-shrink-0 w-1.5 h-1.5 bg-blue-600 rounded-full mt-2 mr-3"></span>
                Click "View Feedback" to see detailed reviewer comments
            </li>
            <li class="flex items-start">
                <span class="flex-shrink-0 w-1.5 h-1.5 bg-blue-600 rounded-full mt-2 mr-3"></span>
                Ensure documents meet all format and content requirements
            </li>
            <li class="flex items-start">
                <span class="flex-shrink-0 w-1.5 h-1.5 bg-blue-600 rounded-full mt-2 mr-3"></span>
                Address all feedback points before resubmitting
            </li>
            <li class="flex items-start">
                <span class="flex-shrink-0 w-1.5 h-1.5 bg-blue-600 rounded-full mt-2 mr-3"></span>
                Contact support if you need clarification on feedback
            </li>
        </ul>
    </div>
</div>
