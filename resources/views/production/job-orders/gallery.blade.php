@extends('layouts.app')

@section('content')
<div class="container-fluid mt-4">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div class="d-flex align-items-center">
            <a href="{{ route('job-orders.index') }}" class="btn btn-outline-secondary btn-sm me-3">
                <i class="bi bi-arrow-left"></i> Back
            </a>
            <div>
                <h2 class="mb-0 h4 fw-bold text-dark">{{ $jobOrder->name }}</h2>
                <p class="text-muted mb-0 small">
                    <i class="bi bi-building me-1"></i> {{ $jobOrder->department ? $jobOrder->department->name : '-' }}
                    @if($jobOrder->project)
                        <span class="mx-2">|</span>
                        <i class="bi bi-folder me-1"></i> {{ $jobOrder->project->name }}
                    @endif
                </p>
            </div>
        </div>
        
        <div class="d-flex gap-2">
            <form action="{{ route('job-orders.sync.gallery', $jobOrder->id) }}" method="POST" id="syncGalleryForm">
                @csrf
                <button type="submit" class="btn btn-info btn-sm shadow-sm px-3" id="btnSyncGallery">
                    <i class="fas fa-sync-alt me-1"></i> Sync from Lark
                </button>
            </form>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row g-4">
        {{-- Section: Project Images --}}
        <div class="col-12">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="card-header bg-white py-3 border-0">
                    <div class="d-flex align-items-center">
                        <div class="icon-box bg-primary-soft text-primary me-3">
                            <i class="bi bi-images"></i>
                        </div>
                        <h5 class="mb-0 fw-bold">Project Images</h5>
                        <span class="badge bg-primary-soft text-primary ms-2 rounded-pill px-3">{{ count($jobOrder->project_image_urls) }}</span>
                    </div>
                </div>
                <div class="card-body p-4">
                    @if(count($jobOrder->project_image_urls) > 0)
                        <div class="gallery-grid">
                            @foreach($jobOrder->project_image_urls as $url)
                                <a href="{{ $url }}" data-fancybox="project-gallery" data-caption="Project Image - {{ $jobOrder->name }}" class="gallery-item">
                                    <img src="{{ $url }}" alt="Project Image" loading="lazy">
                                    <div class="gallery-overlay">
                                        <i class="bi bi-zoom-in text-white fs-2"></i>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-5 bg-light rounded-4">
                            <i class="bi bi-image text-muted fs-1 mb-3 d-block"></i>
                            <p class="text-muted mb-0">No Project Images found in Lark.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Section: Latest Design --}}
        <div class="col-12 col-lg-6">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-white py-3 border-0">
                    <div class="d-flex align-items-center">
                        <div class="icon-box bg-success-soft text-success me-3">
                            <i class="bi bi-palette"></i>
                        </div>
                        <h5 class="mb-0 fw-bold">Latest Design</h5>
                        <span class="badge bg-success-soft text-success ms-2 rounded-pill px-3">{{ count($jobOrder->latest_design_urls) }}</span>
                    </div>
                </div>
                <div class="card-body p-4">
                    @if(count($jobOrder->latest_design_urls) > 0)
                        <div class="gallery-grid mini">
                            @foreach($jobOrder->latest_design_urls as $url)
                                <a href="{{ $url }}" data-fancybox="design-gallery" data-caption="Latest Design - {{ $jobOrder->name }}" class="gallery-item">
                                    <img src="{{ $url }}" alt="Latest Design" loading="lazy">
                                    <div class="gallery-overlay">
                                        <i class="bi bi-zoom-in text-white fs-3"></i>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-5 bg-light rounded-4">
                            <i class="bi bi-pencil-square text-muted fs-1 mb-3 d-block"></i>
                            <p class="text-muted mb-0">No Design images found.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Section: Final Images --}}
        <div class="col-12 col-lg-6">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-white py-3 border-0">
                    <div class="d-flex align-items-center">
                        <div class="icon-box bg-warning-soft text-warning me-3">
                            <i class="bi bi-check2-all"></i>
                        </div>
                        <h5 class="mb-0 fw-bold">Final Images</h5>
                        <span class="badge bg-warning-soft text-warning ms-2 rounded-pill px-3">{{ count($jobOrder->final_images_urls) }}</span>
                    </div>
                </div>
                <div class="card-body p-4">
                    @if(count($jobOrder->final_images_urls) > 0)
                        <div class="gallery-grid mini">
                            @foreach($jobOrder->final_images_urls as $url)
                                <a href="{{ $url }}" data-fancybox="final-gallery" data-caption="Final Image - {{ $jobOrder->name }}" class="gallery-item">
                                    <img src="{{ $url }}" alt="Final Image" loading="lazy">
                                    <div class="gallery-overlay">
                                        <i class="bi bi-zoom-in text-white fs-3"></i>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-5 bg-light rounded-4">
                            <i class="bi bi-camera-fill text-muted fs-1 mb-3 d-block"></i>
                            <p class="text-muted mb-0">No Final Images found.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .bg-primary-soft { background-color: rgba(13, 110, 253, 0.1); }
    .bg-success-soft { background-color: rgba(25, 135, 84, 0.1); }
    .bg-warning-soft { background-color: rgba(255, 193, 7, 0.1); }
    
    .icon-box {
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 12px;
        font-size: 1.25rem;
    }

    .gallery-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 1.5rem;
    }
    
    .gallery-grid.mini {
        grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
        gap: 1rem;
    }

    .gallery-item {
        position: relative;
        display: block;
        border-radius: 16px;
        overflow: hidden;
        aspect-ratio: 1 / 1;
        background-color: #f8f9fa;
        border: 1px solid rgba(0,0,0,0.05);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .gallery-item img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.5s ease;
    }

    .gallery-overlay {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.4);
        display: flex;
        align-items: center;
        justify-content: center;
        opacity: 0;
        transition: opacity 0.3s ease;
    }

    .gallery-item:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0,0,0,0.1);
    }

    .gallery-item:hover img {
        transform: scale(1.1);
    }

    .gallery-item:hover .gallery-overlay {
        opacity: 1;
    }

    @media (max-width: 576px) {
        .gallery-grid {
            grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
            gap: 0.75rem;
        }
    }
</style>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        $('#btnSyncGallery').on('click', function(e) {
            $(this).html('<i class="fas fa-spinner fa-spin me-1"></i> Syncing...');
            $(this).prop('disabled', true);
            $('#syncGalleryForm').submit();
        });
    });
</script>
@endpush
