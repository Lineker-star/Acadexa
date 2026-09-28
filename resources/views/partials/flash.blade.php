@foreach(['success' => 'success', 'error' => 'danger', 'warning' => 'warning', 'info' => 'info'] as $msg => $type)
    @if(session($msg))
        <div class="flash-alert alert alert-{{ $type }} alert-dismissible fade show" role="alert">
            <x-icon :name="match($type) { 'success' => 'check-circle-fill', 'danger' => 'x-circle-fill', 'warning' => 'exclamation-triangle-fill', default => 'info-circle-fill' }" class="me-2" />
            {{ session($msg) }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
@endforeach

@if($errors->any())
    <div class="flash-alert alert alert-danger alert-dismissible fade show" role="alert">
        <x-icon name="exclamation-triangle-fill" class="me-2" />
        <ul class="mb-0 ps-3">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif
