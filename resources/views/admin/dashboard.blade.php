@extends('layouts.admin')
@section('title', __('Dashboard'))

@section('content')
<!-- Stats Grid -->
<div class="row g-4 mb-4">
    @foreach([
        ['label' => __('Total Users'),       'num' => $stats['total_users'],          'icon' => 'people',             'color' => '#EEF3FF', 'fg' => '#0A2A5E'],
        ['label' => __('Students'),          'num' => $stats['total_students'],       'icon' => 'mortarboard',        'color' => '#D1FAE5', 'fg' => '#047857'],
        ['label' => __('Instructors'),       'num' => $stats['total_instructors'],    'icon' => 'person-video3',      'color' => '#FEF3C7', 'fg' => '#B45309'],
        ['label' => __('Published Courses'), 'num' => $stats['published_courses'],    'icon' => 'collection-play',    'color' => '#EEF3FF', 'fg' => '#0A2A5E'],
        ['label' => __('Pending Courses'),   'num' => $stats['pending_courses'],      'icon' => 'hourglass-split',    'color' => '#FEE2E2', 'fg' => '#B91C1C'],
        ['label' => __('Enrollments'),       'num' => $stats['total_enrollments'],    'icon' => 'journal-check',      'color' => '#D1FAE5', 'fg' => '#047857'],
        ['label' => __('Certificates'),      'num' => $stats['total_certificates'],   'icon' => 'award',              'color' => '#FEF3C7', 'fg' => '#B45309'],
        ['label' => __('Pending Apps'),      'num' => $stats['pending_applications'], 'icon' => 'person-lines-fill',  'color' => '#FEE2E2', 'fg' => '#B91C1C'],
    ] as $s)
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="stat-icon" style="background:{{ $s['color'] }};color:{{ $s['fg'] }};"><x-icon :name="$s['icon']" style="font-size:1.5rem" /></div>
            <div>
                <div class="stat-number">{{ number_format($s['num']) }}</div>
                <div class="stat-label">{{ $s['label'] }}</div>
            </div>
        </div>
    </div>
    @endforeach
</div>

<div class="row g-4 mb-4">
    <!-- Growth Chart -->
    <div class="col-lg-8">
        <div class="bg-white rounded-xl shadow-brand p-4">
            <h5 class="mb-4">{{ __('Growth Overview (Last 6 Months)') }}</h5>
            <canvas id="growthChart" height="80"></canvas>
        </div>
    </div>

    <!-- Pending Actions -->
    <div class="col-lg-4">
        <div class="bg-white rounded-xl shadow-brand p-4 h-100">
            <h5 class="mb-4"><x-icon name="lightning-charge-fill" class="text-warning me-1" />{{ __('Actions Needed') }}</h5>
            <div class="list-group list-group-flush">
                @if($stats['pending_courses'] > 0)
                <a href="{{ route('admin.courses.index', ['status'=>'pending']) }}"
                   class="list-group-item list-group-item-action d-flex justify-content-between align-items-center border-0 py-2 px-0">
                    <span><x-icon name="clock" class="text-warning me-2" />{{ __('Pending Course Reviews') }}</span>
                    <span class="badge bg-warning text-dark">{{ $stats['pending_courses'] }}</span>
                </a>
                @endif
                @if($stats['pending_applications'] > 0)
                <a href="{{ route('admin.applications.index') }}"
                   class="list-group-item list-group-item-action d-flex justify-content-between align-items-center border-0 py-2 px-0">
                    <span><x-icon name="person-check" class="text-info me-2" />{{ __('Instructor Applications') }}</span>
                    <span class="badge bg-info">{{ $stats['pending_applications'] }}</span>
                </a>
                @endif
                <a href="{{ route('admin.contacts.index') }}"
                   class="list-group-item list-group-item-action d-flex justify-content-between align-items-center border-0 py-2 px-0">
                    <span><x-icon name="envelope" class="text-primary me-2" />{{ __('Contact Messages') }}</span>
                    <span class="badge bg-primary">
                        {{ \App\Models\ContactSubmission::where('is_read', false)->count() }}
                    </span>
                </a>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Recent Users -->
    <div class="col-lg-6">
        <div class="bg-white rounded-xl shadow-brand p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0">{{ __('Recent Users') }}</h5>
                <a href="{{ route('admin.users.index') }}" class="btn btn-outline-primary btn-sm">{{ __('View All') }}</a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr><th>{{ __('Name') }}</th><th>{{ __('Role') }}</th><th>{{ __('Joined') }}</th></tr>
                    </thead>
                    <tbody>
                        @foreach($recentUsers as $u)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <img src="{{ $u->avatarUrl() }}" class="rounded-circle" width="32" height="32" alt="{{ $u->name }}">
                                    <div>
                                        <div style="font-size:.88rem;font-weight:600;">{{ $u->name }}</div>
                                        <div style="font-size:.75rem;color:var(--text-muted);">{{ $u->email }}</div>
                                    </div>
                                </div>
                            </td>
                            <td><span class="badge bg-primary-light text-primary" style="background:#EEF3FF!important;">{{ __('lms.role_' . $u->role) }}</span></td>
                            <td><span style="font-size:.8rem;">{{ $u->created_at->diffForHumans() }}</span></td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Pending Courses -->
    <div class="col-lg-6">
        <div class="bg-white rounded-xl shadow-brand p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0">{{ __('Pending Course Reviews') }}</h5>
                <a href="{{ route('admin.courses.index', ['status'=>'pending']) }}" class="btn btn-outline-primary btn-sm">{{ __('View All') }}</a>
            </div>
            @forelse($pendingCourses as $course)
            <div class="d-flex align-items-center gap-3 mb-3 pb-3 border-bottom">
                <img src="{{ $course->thumbnailUrl() }}" class="rounded" width="56" height="40" style="object-fit:cover;" alt="{{ e($course->title()) }}">
                <div class="flex-grow-1">
                    <div style="font-size:.88rem;font-weight:600;">{{ $course->title() }}</div>
                    <div style="font-size:.75rem;color:var(--text-muted);">{{ __('by :name', ['name' => $course->instructor?->name]) }}</div>
                </div>
                <div class="d-flex gap-1">
                    <form method="POST" action="{{ route('admin.courses.approve', $course) }}">
                        @csrf
                        <button class="btn btn-success btn-sm" title="{{ __('Approve') }}"><x-icon name="check" /></button>
                    </form>
                    <a href="{{ route('admin.courses.show', $course) }}" class="btn btn-primary btn-sm" title="{{ __('Review') }}"><x-icon name="eye" /></a>
                </div>
            </div>
            @empty
            <p class="text-muted small">{{ __('No pending courses.') }}</p>
            @endforelse
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
const growthChart = document.getElementById('growthChart');
const userData    = @json($userGrowth);
const enrollData  = @json($enrollmentGrowth);

const monthFormat = new Intl.DateTimeFormat(@json(str_replace('_', '-', app()->getLocale())), { month: 'short', year: 'numeric' });
const labels = userData.map(d => monthFormat.format(new Date(d.year, d.month - 1, 1)));

new Chart(growthChart, {
    type: 'line',
    data: {
        labels: labels,
        datasets: [
            {
                label: @json(__('New Users')),
                data: userData.map(d => d.count),
                borderColor: '#0A2A5E',
                backgroundColor: 'rgba(10,42,94,.1)',
                tension: 0.4,
                fill: true,
            },
            {
                label: @json(__('Enrollments')),
                data: enrollData.map(d => d.count),
                borderColor: '#C1440E',
                backgroundColor: 'rgba(193,68,14,.1)',
                tension: 0.4,
                fill: true,
            }
        ]
    },
    options: {
        responsive: true,
        plugins: { legend: { position: 'top' } },
        scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
    }
});
</script>
@endpush
