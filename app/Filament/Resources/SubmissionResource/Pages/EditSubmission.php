<?php

namespace App\Filament\Resources\SubmissionResource\Pages;

use App\Filament\Forms\SubmissionFormFactory;
use App\Filament\Resources\SubmissionResource;
use App\Models\SubmissionType;
use App\Models\Document;
use App\Models\SubmissionDocument;
use App\Services\SubmissionService;
use App\Services\WorkflowService;
use Filament\Actions;
use Filament\Forms;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class EditSubmission extends EditRecord
{
    protected static string $resource = SubmissionResource::class;

    public $uploadedDocuments = [];
    public $documentValidationStatus = [];
    public $isDocumentComplete = false;
    public $stageRequirementsSatisfied = false;

    /**
     * Generate a properly formatted URI for document storage
     * 
     * @param string|int $requirementId The requirement ID
     * @param string $filename The filename to use
     * @return string The formatted URI path
     */
    protected function formatDocumentUri($requirementId, $filename): string
    {
        return "submissions/{$this->record->id}/{$requirementId}/{$filename}";
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Initialize document validation status
        $this->uploadedDocuments = [];
        $this->documentValidationStatus = [];
        $this->isDocumentComplete = false;
        $this->stageRequirementsSatisfied = false;

        // Check document requirements immediately
        $this->checkDocumentRequirements();

        // Process type-specific details
        $submissionType = $this->record->submissionType;

        if ($submissionType) {
            switch ($submissionType->slug) {
                case 'paten':
                    if ($this->record->patentDetail) {
                        $data['patentDetail'] = $this->record->patentDetail->toArray();
                    }
                    break;
                case 'brand':
                    if ($this->record->brandDetail) {
                        $data['brandDetail'] = $this->record->brandDetail->toArray();
                    }
                    break;
                case 'haki':
                    if ($this->record->hakiDetail) {
                        $data['hakiDetail'] = $this->record->hakiDetail->toArray();
                    }
                    break;
                case 'industrial_design':
                    if ($this->record->industrialDesignDetail) {
                        $data['industrialDesignDetail'] = $this->record->industrialDesignDetail->toArray();
                    }
                    break;
            }
        }

        return $data;
    }

    /**
     * Check if all required documents have been uploaded
     */
    public function checkDocumentRequirements(): void
    {
        if (!$this->record || !$this->record->submissionType) {
            $this->isDocumentComplete = false;
            return;
        }

        $requirements = $this->record->submissionType->documentRequirements()
            ->where('required', true)
            ->get();

        if ($requirements->isEmpty()) {
            $this->isDocumentComplete = true;
            return;
        }

        $missingDocuments = [];
        $this->documentValidationStatus = [];


        foreach ($requirements as $requirement) {
            $document = $this->record->submissionDocuments()
                ->where('requirement_id', $requirement->id)
                ->where('status', '!=', 'replaced')
                ->latest()
                ->first();

            if (!$document) {
                $missingDocuments[] = $requirement->name;
                $this->documentValidationStatus[$requirement->id] = [
                    'status' => 'missing',
                    'message' => 'Required document is missing'
                ];
            } else {
                $this->documentValidationStatus[$requirement->id] = [
                    'status' => 'uploaded',
                    'document_status' => $document->status,
                    'document_id' => $document->id
                ];
            }
        }

        $this->isDocumentComplete = empty($missingDocuments);
    }

    /**
     * Process document uploads when form is saved
     */
    protected function processDocumentUploads(array $formData): void
    {
        // Check if there are documents to process
        if (isset($formData['documents']) && is_array($formData['documents'])) {
            foreach ($formData['documents'] as $requirementId => $uploadedFile) {
                if (!$uploadedFile) continue;

                $filename = $uploadedFile instanceof TemporaryUploadedFile ?
                    $uploadedFile->getClientOriginalName() : basename($uploadedFile);

                $document = Document::create([
                    'title' => $filename,
                    'uri' => $this->formatDocumentUri($requirementId, $filename),
                    'mimetype' => $uploadedFile instanceof TemporaryUploadedFile ? $uploadedFile->getMimeType() : Storage::disk('public')->mimeType($uploadedFile),
                    'size' => $uploadedFile instanceof TemporaryUploadedFile ? $uploadedFile->getSize() : Storage::disk('public')->size($uploadedFile),
                ]);

                // Move file from temporary storage to permanent storage
                if ($uploadedFile instanceof TemporaryUploadedFile) {
                    // Get the proper URI for storage
                    $uri = $this->formatDocumentUri($requirementId, $uploadedFile->getClientOriginalName());

                    // Store the file using public disk
                    Storage::disk('public')->put(
                        $uri,
                        file_get_contents($uploadedFile->getRealPath())
                    );

                    // Update document record with correct URI
                    $document->update(['uri' => $uri]);
                }

                // Create the submission document record
                $this->record->submissionDocuments()->create([
                    'document_id' => $document->id,
                    'requirement_id' => $requirementId,
                    'status' => 'pending',
                ]);
            }

            // Update document validation status
            $this->checkDocumentRequirements();
        }
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                // Progress indicator for better user orientation
                Placeholder::make('progress_indicator')
                    ->content(function () {
                        // Determine progress based on completion and status
                        $progress = 0;
                        
                        if ($this->record->title) {
                            $progress += 33;
                        }
                        
                        if ($this->isDocumentComplete) {
                            $progress += 33;
                        }
                        
                        if ($this->record->status !== 'draft') {
                            $progress += 34;
                        }
                        
                        // Generate user-friendly progress display
                        $content = '
                        <div class="bg-gradient-to-r from-blue-50 to-purple-50 p-4 rounded-xl border border-blue-200 mb-6">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-lg font-semibold text-gray-800">Progress Pengajuan Anda</span>
                                <span class="text-sm text-blue-600 font-medium">' . $progress . '%</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-4">
                                <div class="bg-blue-500 h-4 rounded-full" style="width: ' . $progress . '%"></div>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mt-4">
                                <div class="flex items-center">
                                    <span class="text-xl mr-2">' . ($this->record->title ? '✅' : '⭕') . '</span>
                                    <span class="' . ($this->record->title ? 'text-green-600' : 'text-gray-500') . ' font-medium">Informasi Dasar</span>
                                </div>
                                <div class="flex items-center">
                                    <span class="text-xl mr-2">' . ($this->isDocumentComplete ? '✅' : '⭕') . '</span>
                                    <span class="' . ($this->isDocumentComplete ? 'text-green-600' : 'text-gray-500') . ' font-medium">Dokumen Lengkap</span>
                                </div>
                                <div class="flex items-center">
                                    <span class="text-xl mr-2">' . ($this->record->status !== 'draft' ? '✅' : '⭕') . '</span>
                                    <span class="' . ($this->record->status !== 'draft' ? 'text-green-600' : 'text-gray-500') . ' font-medium">Pengajuan Terkirim</span>
                                </div>
                            </div>
                        </div>';
                        
                        return new \Illuminate\Support\HtmlString($content);
                    })
                    ->columnSpanFull(),

                // Simplified submission information section with better readability
                Section::make('📋 Informasi Pengajuan')
                    ->description('Detail dasar tentang pengajuan kekayaan intelektual Anda')
                    ->icon('heroicon-o-document-text')
                    ->collapsible(false) // Always expanded for better visibility
                    ->schema([
                        Hidden::make('user_id')
                            ->default(fn() => $this->record->user_id),

                        Select::make('submission_type_id')
                            ->relationship('submissionType', 'name')
                            ->label('Jenis Pengajuan')
                            ->required()
                            ->disabled()
                            ->helperText('Jenis pengajuan tidak dapat diubah setelah dibuat')
                            ->default(fn() => $this->record->submission_type_id)
                            ->dehydrated(false)
                            ->extraAttributes(['class' => 'text-lg']),

                        TextInput::make('title')
                            ->label('Judul Pengajuan')
                            ->required()
                            ->helperText('Berikan judul yang jelas dan mudah dimengerti')
                            ->placeholder('Contoh: Aplikasi Mobile untuk Deteksi Penyakit Tanaman')
                            ->maxLength(255)
                            ->columnSpanFull()
                            ->extraAttributes([
                                'class' => 'text-lg',
                                'style' => 'font-size: 16px; padding: 12px;',
                            ]),
                    ])
                    ->extraAttributes(['class' => 'bg-white shadow-sm rounded-xl border border-gray-200']),

                // Type-specific details section with improved visual hierarchy
                Section::make('📝 Detail Khusus')
                    ->description('Informasi khusus sesuai jenis pengajuan')
                    ->icon('heroicon-o-document-duplicate')
                    ->schema(function () {
                        $submissionType = $this->record->submissionType;

                        if (!$submissionType) {
                            return [
                                Placeholder::make('no_type')
                                    ->content('⚠️ Tidak ada jenis pengajuan yang terkait dengan pengajuan ini')
                                    ->columnSpanFull(),
                            ];
                        }

                        return SubmissionFormFactory::getFormForSubmissionType($submissionType->slug);
                    })
                    ->collapsible()
                    ->collapsed(false)
                    ->extraAttributes(['class' => 'bg-white shadow-sm rounded-xl border border-gray-200']),

                // Document section with improved visual cues and guidance
                Section::make('📎 Dokumen Persyaratan')
                    ->description('Upload semua dokumen yang diperlukan untuk pengajuan Anda')
                    ->icon('heroicon-o-paper-clip')
                    ->schema(function () {
                        if (!$this->record->submissionType) {
                            return [
                                Placeholder::make('no_type')
                                    ->content('⚠️ Tidak ada jenis pengajuan yang terkait dengan pengajuan ini')
                                    ->columnSpanFull(),
                            ];
                        }

                        $requirements = $this->record->submissionType->documentRequirements()->get();

                        if ($requirements->isEmpty()) {
                            return [
                                Placeholder::make('no_requirements')
                                    ->content('ℹ️ Tidak ada persyaratan dokumen untuk jenis pengajuan ini')
                                    ->columnSpanFull(),
                            ];
                        }

                        $fields = [];

                        // Document upload guidance for elderly users
                        $fields[] = Placeholder::make('document_guidance')
                            ->content(new \Illuminate\Support\HtmlString(
                                '<div class="p-4 bg-blue-50 border border-blue-200 rounded-xl mb-4">
                                    <div class="flex items-start">
                                        <span class="text-2xl mr-3">💡</span>
                                        <div>
                                            <h4 class="text-lg font-medium text-blue-800 mb-2">Panduan Upload Dokumen:</h4>
                                            <ul class="text-blue-700 space-y-2 text-base">
                                                <li class="flex items-center">
                                                    <span class="mr-2">✅</span>
                                                    Gunakan format PDF, DOC, atau DOCX
                                                </li>
                                                <li class="flex items-center">
                                                    <span class="mr-2">✅</span>
                                                    Ukuran maksimal file adalah 10MB
                                                </li>
                                                <li class="flex items-center">
                                                    <span class="mr-2">✅</span>
                                                    Pastikan dokumen terbaca dengan jelas
                                                </li>
                                                <li class="flex items-center">
                                                    <span class="mr-2">✅</span>
                                                    Dokumen bertanda <span class="text-primary-500 font-medium mx-1">Wajib</span> harus diupload
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>'
                            ))
                            ->columnSpanFull();

                        // Add document validation status at the top of the section
                        $fields[] = Placeholder::make('document_validation')
                            ->content(function () {
                                $this->checkDocumentRequirements();

                                if ($this->isDocumentComplete) {
                                    return new \Illuminate\Support\HtmlString(
                                        '<div class="p-4 bg-green-50 border border-green-200 rounded-xl mb-4">
                                            <div class="flex items-center">
                                                <span class="text-2xl mr-3">🎉</span>
                                                <div>
                                                    <span class="text-lg font-medium text-green-800">Semua dokumen wajib sudah diupload!</span>
                                                    <p class="text-green-700 mt-1">Anda dapat melanjutkan ke tahap berikutnya</p>
                                                </div>
                                            </div>
                                        </div>'
                                    );
                                }

                                $requirements = $this->record->submissionType->documentRequirements()
                                    ->where('required', true)
                                    ->get();

                                $content = '<div class="p-4 bg-yellow-50 border border-yellow-200 rounded-xl mb-4">
                                    <div class="flex items-start">
                                        <span class="text-2xl mr-3">⚠️</span>
                                        <div>
                                            <span class="text-lg font-medium text-yellow-800">Dokumen Wajib yang Belum Diupload:</span>
                                            <p class="text-yellow-700 mt-1">Mohon upload dokumen-dokumen berikut:</p>
                                            <ul class="list-disc list-inside mt-2 space-y-1 text-base ml-2">';

                                foreach ($requirements as $requirement) {
                                    if (
                                        !isset($this->documentValidationStatus[$requirement->id]) ||
                                        $this->documentValidationStatus[$requirement->id]['status'] === 'missing'
                                    ) {
                                        $content .= "<li class='text-yellow-700'>{$requirement->name}</li>";
                                    }
                                }

                                $content .= '</ul></div>
                                    </div>
                                </div>';

                                return new \Illuminate\Support\HtmlString($content);
                            })
                            ->columnSpanFull()
                            ->visible(fn() => $this->record->status === 'draft');

                        // Document Feedback Summary with clearer formatting
                        $fields[] = Placeholder::make('document_feedback_summary')
                            ->content(function () {
                                $documentsWithFeedback = $this->record->submissionDocuments()
                                    ->whereNotNull('notes')
                                    ->whereIn('status', ['approved', 'rejected', 'revision_needed'])
                                    ->with(['document', 'requirement'])
                                    ->latest()
                                    ->get();

                                if ($documentsWithFeedback->isEmpty()) {
                                    return '';
                                }

                                $content = '<div class="space-y-4 mb-6">';
                                $content .= '<div class="flex items-center space-x-2 mb-2">';
                                $content .= '<span class="text-2xl">💬</span>';
                                $content .= '<h3 class="text-lg font-semibold text-gray-900">Feedback Reviewer</h3>';
                                $content .= '</div>';

                                foreach ($documentsWithFeedback as $docItem) {
                                    $statusColor = match ($docItem->status) {
                                        'approved' => 'green',
                                        'rejected' => 'red',
                                        'revision_needed' => 'yellow',
                                        default => 'gray',
                                    };

                                    $statusIcon = match ($docItem->status) {
                                        'approved' => '✅',
                                        'rejected' => '❌',
                                        'revision_needed' => '⚠️',
                                        default => '📄',
                                    };

                                    $statusText = match ($docItem->status) {
                                        'approved' => 'Disetujui',
                                        'rejected' => 'Ditolak',
                                        'revision_needed' => 'Perlu Revisi',
                                        default => 'Sedang Ditinjau',
                                    };

                                    $content .= "<div class='border border-{$statusColor}-200 bg-{$statusColor}-50 rounded-lg p-4 mb-3'>";
                                    $content .= "<div class='flex items-start space-x-3'>";
                                    $content .= "<span class='text-2xl'>{$statusIcon}</span>";
                                    $content .= "<div class='flex-1'>";
                                    $requirementName = $docItem->requirement->name ?? 'Dokumen';
                                    $content .= "<h4 class='text-lg font-medium text-{$statusColor}-800'>{$requirementName}</h4>";
                                    $content .= "<div class='flex items-center text-sm text-{$statusColor}-600 mb-2'>";
                                    $content .= "<span class='mr-2'>{$docItem->document->title}</span>";
                                    $content .= "<span class='px-2 py-0.5 rounded-full bg-{$statusColor}-100 text-{$statusColor}-800 text-xs font-medium'>{$statusText}</span>";
                                    $content .= "</div>";
                                    
                                    $content .= "<div class='bg-white p-4 rounded border border-{$statusColor}-200'>";
                                    $content .= "<div class='flex items-center mb-1'>";
                                    $content .= "<span class='text-lg mr-2'>💬</span>";
                                    $content .= "<span class='font-medium text-gray-700'>Catatan Reviewer:</span>";
                                    $content .= "</div>";
                                    $content .= "<p class='text-base text-gray-700 pl-7'>" . nl2br(e($docItem->notes)) . "</p>";
                                    $content .= "</div>";
                                    
                                    if ($docItem->status === 'revision_needed') {
                                        $content .= "<div class='mt-3 p-2 bg-blue-50 border border-blue-200 rounded flex items-start'>";
                                        $content .= "<span class='text-lg mr-2'>💡</span>";
                                        $content .= "<p class='text-sm text-blue-700'>Silakan tinjau feedback di atas dan upload versi baru dari dokumen ini.</p>";
                                        $content .= "</div>";
                                    } else if ($docItem->status === 'rejected') {
                                        $content .= "<div class='mt-3 p-2 bg-red-50 border border-red-200 rounded flex items-start'>";
                                        $content .= "<span class='text-lg mr-2'>🔄</span>";
                                        $content .= "<p class='text-sm text-red-700'>Dokumen ini perlu diganti. Silakan upload versi baru sesuai dengan catatan di atas.</p>";
                                        $content .= "</div>";
                                    } else if ($docItem->status === 'approved') {
                                        $content .= "<div class='mt-3 p-2 bg-green-50 border border-green-200 rounded flex items-start'>";
                                        $content .= "<span class='text-lg mr-2'>✨</span>";
                                        $content .= "<p class='text-sm text-green-700'>Dokumen ini sudah disetujui dan memenuhi semua persyaratan.</p>";
                                        $content .= "</div>";
                                    }
                                    
                                    $content .= "</div>";
                                    $content .= "</div>";
                                    $content .= "</div>";
                                }

                                $content .= '</div>';
                                return new \Illuminate\Support\HtmlString($content);
                            })
                            ->columnSpanFull()
                            ->visible(function () {
                                return $this->record->submissionDocuments()
                                    ->whereNotNull('notes')
                                    ->whereIn('status', ['approved', 'rejected', 'revision_needed'])
                                    ->exists();
                            });

                        // Document requirements with improved visual styling
                        foreach ($requirements as $requirement) {
                            $existingDocs = $this->record->submissionDocuments()
                                ->where('requirement_id', $requirement->id)
                                ->with('document')
                                ->latest()
                                ->get();

                            // Use colorful, distinctive sections for each document type
                            $fields[] = Section::make($requirement->name)
                                ->description($requirement->description)
                                ->extraAttributes([
                                    'class' => $requirement->required ? 'border-l-4 border-primary-500 bg-white rounded-lg shadow-sm' : 'bg-white rounded-lg shadow-sm',
                            ])
                            ->schema([
                                Placeholder::make('requirement_info_' . $requirement->id)
                                    ->content(function () use ($requirement) {
                                        return new \Illuminate\Support\HtmlString(
                                            $requirement->required 
                                            ? '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-primary-100 text-primary-800 mb-2">Wajib</span>'
                                            : '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 mb-2">Opsional</span>'
                                        );
                                    }),
                                
                                // Show existing documents with improved layout
                                Placeholder::make('existing_docs_' . $requirement->id)
                                    ->content(function () use ($existingDocs) {
                                        if ($existingDocs->isEmpty()) {
                                            return new \Illuminate\Support\HtmlString(
                                                '<div class="text-sm italic text-gray-500 mb-4">Belum ada dokumen yang diupload</div>'
                                            );
                                        }

                                        $content = '<div class="space-y-3 mb-4">';
                                        $content .= '<div class="text-base font-medium text-gray-900">Dokumen yang sudah diupload:</div>';
                                        
                                        foreach ($existingDocs as $docItem) {
                                            $statusColor = match ($docItem->status) {
                                                'pending' => 'gray',
                                                'approved' => 'green',
                                                'rejected' => 'red',
                                                'revision_needed' => 'yellow',
                                                default => 'gray',
                                            };
                                            
                                            $statusText = match ($docItem->status) {
                                                'pending' => 'Sedang Ditinjau',
                                                'approved' => 'Disetujui',
                                                'rejected' => 'Ditolak',
                                                'revision_needed' => 'Perlu Revisi',
                                                default => 'Sedang Diproses',
                                            };

                                            $content .= "<div class='border border-{$statusColor}-200 rounded-lg overflow-hidden'>";
                                            $content .= "<div class='flex items-center justify-between p-3 bg-{$statusColor}-50'>";
                                            $content .= "<div class='flex items-center space-x-3'>";
                                            $content .= "<span class='text-xl'>📄</span>";
                                            $content .= "<div>";
                                            $content .= "<div class='font-medium text-gray-900'>{$docItem->document->title}</div>";
                                            $content .= "<div class='text-xs text-gray-500'>" . $this->formatFileSize($docItem->document->size) . " • " . strtoupper(pathinfo($docItem->document->title, PATHINFO_EXTENSION)) . "</div>";
                                            $content .= "</div>";
                                            $content .= "</div>";
                                            
                                            // Status badge
                                            $content .= "<span class='px-2.5 py-1 rounded-full text-xs font-medium bg-{$statusColor}-100 text-{$statusColor}-800'>{$statusText}</span>";
                                            $content .= "</div>";
                                            
                                            // Add download button and upload date
                                            $content .= "<div class='p-3 bg-white flex justify-between items-center'>";
                                            $content .= "<span class='text-xs text-gray-500'>Diupload: " . $docItem->created_at->format('d/m/Y H:i') . "</span>";
                                            
                                            $downloadUrl = route('filament.admin.documents.download', $docItem->document_id);
                                            $content .= "<a href='{$downloadUrl}' target='_blank' class='inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500'>";
                                            $content .= "<span class='mr-1'>📥</span> Download";
                                            $content .= "</a>";
                                            $content .= "</div>";
                                            $content .= "</div>";
                                        }
                                        
                                        $content .= '</div>';
                                        return new \Illuminate\Support\HtmlString($content);
                                    }),
                                
                                // File upload with improved UI for elderly users
                                FileUpload::make('documents.' . $requirement->id)
                                    ->label('Upload Dokumen')
                                    ->disk('public')
                                    ->directory('temp')
                                    ->acceptedFileTypes(['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'])
                                    ->maxSize(10240)
                                    ->helperText('Format yang diterima: PDF, DOC, atau DOCX. Maksimal 10MB')
                                    ->loadingIndicatorPosition('left')
                                    ->removeUploadedFileButtonPosition('right')
                                    ->uploadButtonPosition('left')
                                    ->uploadProgressIndicatorPosition('left')
                                    ->panelLayout('compact')
                                    ->extraAttributes([
                                        'class' => 'elderly-friendly-upload',
                                    ])
                                    ->downloadable(),
                            ])
                            ->collapsible(true)
                            ->collapsed(false);
                        }

                        return $fields;
                    })
                    ->collapsible()
                    ->collapsed(false)
                    ->extraAttributes(['class' => 'bg-white shadow-sm rounded-xl border border-gray-200']),

                // Status management section with clearer call-to-actions
                Section::make('📊 Status Pengajuan')
                    ->description('Atur status pengajuan Anda')
                    ->icon('heroicon-o-document-check')
                    ->schema([
                        // Current status indicator
                        Placeholder::make('current_status')
                            ->content(function () {
                                $status = $this->record->status;
                                $statusText = match ($status) {
                                    'draft' => 'Draft (Belum Dikirim)',
                                    'submitted' => 'Terkirim (Sedang Ditinjau)',
                                    'in_review' => 'Dalam Peninjauan',
                                    'revision_needed' => 'Perlu Revisi',
                                    'approved' => 'Disetujui',
                                    'rejected' => 'Ditolak',
                                    'cancelled' => 'Dibatalkan',
                                    'completed' => 'Selesai',
                                    default => ucfirst($status),
                                };
                                
                                $statusIcon = match ($status) {
                                    'draft' => '📝',
                                    'submitted' => '📤',
                                    'in_review' => '👁️',
                                    'revision_needed' => '✏️',
                                    'approved' => '✅',
                                    'rejected' => '❌',
                                    'cancelled' => '🚫',
                                    'completed' => '🏆',
                                    default => '📄',
                                };
                                
                                $statusColor = match ($status) {
                                    'draft' => 'gray',
                                    'submitted', 'in_review' => 'blue',
                                    'revision_needed' => 'yellow',
                                    'approved', 'completed' => 'green',
                                    'rejected', 'cancelled' => 'red',
                                    default => 'gray',
                                };

                                $content = "<div class='p-4 bg-{$statusColor}-50 border border-{$statusColor}-200 rounded-xl flex items-start space-x-4'>";
                                $content .= "<div class='text-3xl'>{$statusIcon}</div>";
                                $content .= "<div>";
                                $content .= "<div class='text-lg font-medium text-{$statusColor}-800 mb-1'>Status: {$statusText}</div>";
                                
                                // Add status-specific guidance
                                $guidance = match ($status) {
                                    'draft' => 'Pengajuan ini belum dikirim dan masih dalam tahap persiapan. Lengkapi informasi dan dokumen yang diperlukan sebelum mengirimkan.',
                                    'submitted' => 'Pengajuan sudah dikirim dan sedang menunggu untuk ditinjau oleh tim kami. Anda akan mendapat notifikasi saat ada update.',
                                    'in_review' => 'Tim kami sedang meninjau pengajuan Anda. Proses ini mungkin membutuhkan waktu 3-7 hari kerja.',
                                    'revision_needed' => 'Ada bagian yang perlu diperbaiki. Silakan tinjau catatan reviewer dan lakukan revisi yang diperlukan.',
                                    'approved' => 'Selamat! Pengajuan Anda telah disetujui.',
                                    'rejected' => 'Maaf, pengajuan Anda tidak dapat diproses. Silakan tinjau catatan dari reviewer.',
                                    'cancelled' => 'Pengajuan ini telah dibatalkan.',
                                    'completed' => 'Pengajuan ini telah selesai diproses dan sertifikat telah diterbitkan.',
                                    default => 'Status pengajuan saat ini.',
                                };
                                
                                $content .= "<p class='text-base text-{$statusColor}-700'>{$guidance}</p>";
                                
                                // Next steps
                                if ($status === 'draft') {
                                    $nextStep = $this->isDocumentComplete 
                                        ? 'Anda dapat mengirim pengajuan ini untuk ditinjau.' 
                                        : 'Lengkapi semua dokumen yang diperlukan sebelum mengirim.';
                                    $content .= "<p class='text-sm bg-blue-50 p-2 mt-2 rounded border border-blue-200 flex items-center'>";
                                    $content .= "<span class='mr-1'>💡</span> Langkah selanjutnya: {$nextStep}";
                                    $content .= "</p>";
                                } else if ($status === 'revision_needed') {
                                    $content .= "<p class='text-sm bg-yellow-50 p-2 mt-2 rounded border border-yellow-200 flex items-center'>";
                                    $content .= "<span class='mr-1'>💡</span> Langkah selanjutnya: Tinjau feedback, lakukan revisi yang diperlukan, lalu kirim ulang.";
                                    $content .= "</p>";
                                }
                                
                                $content .= "</div>";
                                $content .= "</div>";

                                return new \Illuminate\Support\HtmlString($content);
                            })
                            ->columnSpanFull(),
                            
                        // Status selector with clear options
                        Select::make('status')
                            ->options(function () {
                                $options = [
                                    'draft' => '💾 Simpan sebagai Draft - Lanjutkan nanti',
                            ];

                            if (($this->isDocumentComplete || $this->record->status !== 'draft') &&
                                in_array($this->record->status, ['draft', 'cancelled'])
                            ) {
                                $options['submitted'] = '🚀 Kirim Pengajuan - Mulai proses peninjauan';
                            }
                            
                            // Add resubmit option when status is revision_needed
                            if ($this->record->status === 'revision_needed' && $this->isDocumentComplete) {
                                $options['submitted'] = '🔄 Kirim Ulang Pengajuan - Setelah revisi';
                            }
                            
                            if (in_array($this->record->status, ['draft', 'revision_needed'])) {
                                $options['cancelled'] = '❌ Batalkan Pengajuan - Tidak lanjut proses';
                            }

                            return $options;
                        })
                        ->default(fn() => $this->record->status)
                        ->required()
                        ->reactive()
                        ->disabled(function () {
                            // Allow status change if status is draft, cancelled, or revision_needed
                            return !in_array($this->record->status, ['draft', 'cancelled', 'revision_needed']);
                        })
                        ->afterStateUpdated(function (string $state, Set $set) {
                            if ($state === 'submitted' && $this->record->status === 'draft') {
                                if ($this->record->submissionType && !$this->record->current_stage_id) {
                                    $firstStage = $this->record->submissionType->firstStage();
                                    if ($firstStage) {
                                        $set('current_stage_id', $firstStage->id);
                                    }
                                }

                                $set('status_notes', 'Pengajuan siap untuk ditinjau');
                            } else if ($state === 'submitted' && $this->record->status === 'revision_needed') {
                                $set('status_notes', 'Pengajuan sudah diperbarui dan dikirim ulang untuk ditinjau');
                            }
                        })
                        ->helperText(function () {
                            if (!in_array($this->record->status, ['draft', 'cancelled', 'revision_needed'])) {
                                return 'Status tidak dapat diubah karena pengajuan sudah dalam proses';
                            }

                            if ($this->record->status === 'draft' && !$this->isDocumentComplete) {
                                return '⚠️ Anda harus mengupload semua dokumen wajib sebelum dapat mengirim pengajuan';
                            }
                            
                            if ($this->record->status === 'revision_needed') {
                                return '🔄 Setelah melakukan revisi yang diminta, Anda dapat mengirim ulang pengajuan untuk ditinjau';
                            }
                            
                            return 'Pilih tindakan yang ingin Anda lakukan';
                        })
                        ->extraAttributes([
                            'class' => 'text-lg',
                            'style' => 'font-size: 16px; padding: 12px;',
                        ]),
                    
                        // Reviewer notes with clear formatting
                        Textarea::make('reviewer_notes')
                            ->label('Catatan Reviewer')
                            ->placeholder('Catatan tentang pengajuan ini')
                            ->helperText('Catatan ini akan terlihat oleh pengaju saat revisi diperlukan atau pengajuan ditolak')
                            ->rows(3)
                            ->disabled(function () {
                                // Disable the reviewer notes field if current status is not revision_needed or rejected
                                return !in_array($this->record->status, ['revision_needed', 'rejected']);
                            })
                            ->extraAttributes([
                                'class' => 'text-lg',
                                'style' => 'font-size: 16px; padding: 12px;',
                            ]),
                            
                        // Help section with contextual guidance
                        Placeholder::make('help_section')
                            ->content(new \Illuminate\Support\HtmlString(
                                '<div class="p-4 bg-blue-50 border border-blue-200 rounded-xl mt-4">
                                    <div class="flex items-center mb-2">
                                        <span class="text-2xl mr-2">❓</span>
                                        <h4 class="text-lg font-medium text-blue-800">Butuh Bantuan?</h4>
                                    </div>
                                    <div class="space-y-3">
                                        <div class="flex items-start space-x-3">
                                            <span class="text-xl">📞</span>
                                            <div>
                                                <p class="font-medium text-blue-700">Hubungi Tim PKKI</p>
                                                <p>0721-123456 / admin@pkki.itera.ac.id</p>
                                            </div>
                                        </div>
                                        <div class="flex items-start space-x-3">
                                            <span class="text-xl">📖</span>
                                            <div>
                                                <p class="font-medium text-blue-700">Panduan Pengajuan</p>
                                                <p>Klik <a href="/admin/panduan" class="text-blue-600 underline">disini</a> untuk melihat panduan lengkap</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>'
                            ))
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->collapsed(false)
                    ->extraAttributes(['class' => 'bg-white shadow-sm rounded-xl border border-gray-200']),
            ])
            ->columns([
                'sm' => 1, // Single column on small screens
                'md' => 1, // Single column on medium screens
                'lg' => 1, // Single column on large screens for consistency
            ])
            // Add accessibility and elderly-friendly global styling
            ->extraAttributes(['class' => 'space-y-6 elderly-friendly-form']);
}

/**
 * Helper function to format file size in a human-readable way
 */
protected function formatFileSize($bytes)
{
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= (1 << (10 * $pow));
    
    return round($bytes, 1) . ' ' . $units[$pow];
}

}

// End of EditSubmission.php