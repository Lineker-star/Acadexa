@foreach(['success' => 'success', 'error' => 'danger', 'warning' => 'warning', 'info' => 'info'] as $msg => $type)
    @if(session($msg))
        <div class="flash-alert alert alert-{{ $type }} alert-dismissible fade show" role="alert">
            <i class="bi bi-{{ $type === 'success' ? 'check-circle' : ($type === 'danger' ? 'x-circle' : 'info-circle') }}-fill me-2"></i>
            {{ session($msg) }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
@endforeach

@if($errors->any())
    <div class="flash-alert alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>
        <ul class="mb-0 ps-3">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif
