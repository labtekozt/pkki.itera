@extends('layouts.app')

@section('title', 'Timeline Pengajuan')

@section('content')
<div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
    <div class="md:flex md:items-center md:justify-between mb-4">
        <div class="flex-1 min-w-0">
            <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:truncate">
                Timeline untuk: {{ $submission->title }}
            </h2>
            <p class="mt-1 text-sm text-gray-500">
                {{ $submission->submissionType->name ?? 'Tipe Tidak Diketahui' }} | 
                Status: <span class="font-medium 
                    @if($submission->status === 'approved') text-green-700
                    @elseif($submission->status === 'rejected') text-red-700
                    @elseif($submission->status === 'revision_needed') text-yellow-700
                    @elseif($submission->status === 'completed') text-green-700
                    @elseif($submission->status === 'in_review') text-blue-700
                    @else text-gray-700
                    @endif">
                    @if($submission->status === 'approved') Disetujui
                    @elseif($submission->status === 'rejected') Ditolak
                    @elseif($submission->status === 'revision_needed') Perlu Revisi
                    @elseif($submission->status === 'completed') Selesai
                    @elseif($submission->status === 'in_review') Dalam Review
                    @elseif($submission->status === 'submitted') Dikirim
                    @elseif($submission->status === 'draft') Draft
                    @else {{ str_replace('_', ' ', ucfirst($submission->status)) }}
                    @endif
                </span>
            </p>
        </div>
        <div class="mt-4 flex md:mt-0 md:ml-4">
            <a href="{{ route('tracking.detail', ['submission_id' => $submission->id]) }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                Lihat Catatan Detail
            </a>
        </div>
    </div>

    <!-- Basic submission info -->
    <div class="bg-white shadow overflow-hidden sm:rounded-lg mb-6">
        <div class="px-4 py-5 sm:px-6 bg-gray-50">
            <h3 class="text-lg leading-6 font-medium text-gray-900">
                Detail Pengajuan
            </h3>
        </div>
        <div class="border-t border-gray-200 px-4 py-5 sm:p-6">
            <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-2 lg:grid-cols-4">
                <div>
                    <dt class="text-sm font-medium text-gray-500">Pengaju</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $submission->user->fullname ?? 'Tidak Diketahui' }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Tahap Saat Ini</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $submission->currentStage->name ?? 'Belum ditetapkan' }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Tanggal Pengajuan</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $submission->created_at->format('d-m-Y') }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Sertifikat</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $submission->certificate ?? 'Belum diterbitkan' }}</dd>
                </div>
            </div>
        </div>
    </div>

    <!-- Timeline -->
    <div class="flow-root">
        <ul role="list" class="-mb-8">
            @foreach($timeline as $stageGroup)
                <li>
                    <div class="relative pb-8">
                        <span class="absolute top-5 left-5 -ml-px h-full w-0.5 bg-gray-200" aria-hidden="true"></span>
                        <div class="relative flex items-start space-x-3">
                            <div>
                                <div class="relative px-1">
                                    <div class="h-8 w-8 bg-primary-500 rounded-full flex items-center justify-center ring-8 ring-white">
                                        <svg class="h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                </div>
                            </div>
                            <div class="min-w-0 flex-1 py-0">                                    <div class="bg-white shadow overflow-hidden sm:rounded-lg mb-8">
                                        <div class="px-4 py-5 sm:px-6 bg-gray-50">
                                            <h3 class="text-lg leading-6 font-medium text-gray-900">
                                                Tahap: {{ $stageGroup['stage_name'] }}
                                            </h3>
                                            <p class="mt-1 max-w-2xl text-sm text-gray-500">
                                                {{ \Carbon\Carbon::parse($stageGroup['start_date'])->format('d M Y') }} - 
                                                @if($stageGroup['stage_id'] == $submission->current_stage_id)
                                                    Saat Ini
                                                @else
                                                    {{ \Carbon\Carbon::parse($stageGroup['end_date'])->format('d M Y') }}
                                                @endif
                                            </p>
                                        </div>
                                    <div class="border-t border-gray-200">
                                        <ul role="list" class="divide-y divide-gray-200">
                                            @foreach($stageGroup['events'] as $event)
                                                <li class="px-4 py-4 sm:px-6 hover:bg-gray-50">
                                                    <div class="flex items-center justify-between">
                                                        <div class="flex flex-col flex-grow">
                                                            <div class="flex justify-between text-sm font-medium text-primary-600 truncate">
                                                                <p>{{ 
                                                                    $event['event_type'] === 'state_change' ? 'Perubahan Status' :
                                                                    ($event['action'] === 'document_uploaded' ? 'Dokumen Diunggah' :
                                                                    ($event['action'] === 'document_approved' ? 'Dokumen Disetujui' :
                                                                    ($event['action'] === 'document_rejected' ? 'Dokumen Ditolak' :
                                                                    ($event['action'] === 'review_started' ? 'Review Dimulai' :
                                                                    ($event['action'] === 'revision_requested' ? 'Revisi Diminta' :
                                                                    str_replace('_', ' ', ucfirst($event['event_type'] ?? $event['action']))))))) 
                                                                }}</p>
                                                                <div class="ml-2 flex-shrink-0 flex">
                                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                                        @if($event['status'] === 'approved') bg-green-100 text-green-800
                                                                        @elseif($event['status'] === 'rejected') bg-red-100 text-red-800
                                                                        @elseif($event['status'] === 'revision_needed') bg-yellow-100 text-yellow-800
                                                                        @elseif($event['status'] === 'completed') bg-green-100 text-green-800
                                                                        @elseif($event['status'] === 'in_progress') bg-blue-100 text-blue-800
                                                                        @else bg-gray-100 text-gray-800
                                                                        @endif">
                                                                        @if($event['status'] === 'approved') Disetujui
                                                                        @elseif($event['status'] === 'rejected') Ditolak
                                                                        @elseif($event['status'] === 'revision_needed') Perlu Revisi
                                                                        @elseif($event['status'] === 'completed') Selesai
                                                                        @elseif($event['status'] === 'in_progress') Sedang Proses
                                                                        @elseif($event['status'] === 'started') Dimulai
                                                                        @else {{ str_replace('_', ' ', ucfirst($event['status'])) }}
                                                                        @endif
                                                                    </span>
                                                                </div>
                                                            </div>
                                                            <div class="flex justify-between">
                                                                <p class="text-sm text-gray-500">
                                                                    <time datetime="{{ $event['date'] }}">{{ \Carbon\Carbon::parse($event['date'])->format('d M Y H:i') }}</time>
                                                                    • {{ $event['processor_name'] ?? 'Sistem' }}
                                                                </p>
                                                                
                                                                @if($event['is_transition'])
                                                                    <p class="text-sm text-gray-500">
                                                                        Dipindah dari {{ $event['transition_from'] }}
                                                                    </p>
                                                                @endif
                                                            </div>
                                                            
                                                            @if($event['comment'])
                                                                <div class="mt-2 text-sm text-gray-700">
                                                                    {{ $event['comment'] }}
                                                                </div>
                                                            @endif
                                                            
                                                            @if($event['has_document'])
                                                                <div class="mt-2">
                                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                                        Dokumen: {{ $event['document_title'] }}
                                                                    </span>
                                                                </div>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </li>
            @endforeach
        </ul>
    </div>
</div>
@endsection