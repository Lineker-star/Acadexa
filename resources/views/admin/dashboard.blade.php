@extends('layouts.admin')
@section('title', 'Dashboard')

@section('content')
<!-- Stats Grid -->
<div class="row g-4 mb-4">
    @foreach([
        ['label'=>'Total Users',       'num'=>$stats['total_users'],        'icon'=>'👥','color'=>'#EEF3FF','trend'=>'+'],
        ['label'=>'Students',          'num'=>$stats['total_students'],      'icon'=>'🎓','color'=>'#D1FAE5','trend'=>'+'],
        ['label'=>'Instructors',       'num'=>$stats['total_instructors'],   'icon'=>'👨‍🏫','color'=>'#FEF3C7','trend'=>'+'],
        ['label'=>'Published Courses', 'num'=>$stats['published_courses'],   'icon'=>'📚','color'=>'#EEF3FF','trend'=>''],
        ['label'=>'Pending Courses',   'num'=>$stats['pending_courses'],     'icon'=>'⏳','color'=>'#FEE2E2','trend'=>''],
        ['label'=>'Enrollments',       'num'=>$stats['total_enrollments'],   'icon'=>'📝','color'=>'#D1FAE5','trend'=>'+'],
        ['label'=>'Certificates',      'num'=>$stats['total_certificates'],  'icon'=>'🏆','color'=>'#FEF3C7','trend'=>'+'],
        ['label'=>'Pending Apps',      'num'=>$stats['pending_applications'],'icon'=>'📋','color'=>'#FEE2E2','trend'=>''],
    ] as $s)
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="stat-icon" style="background:{{ $s['color'] }};"><span>{{ $s['icon'] }}</span></div>
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
            <h5 class="mb-4">Growth Overview (Last 6 Months)</h5>
            <canvas id="growthChart" height="80"></canvas>
        </div>
    </div>

    <!-- Pending Actions -->
    <div class="col-lg-4">
        <div class="bg-white rounded-xl shadow-brand p-4 h-100">
            <h5 class="mb-4">⚡ Actions Needed</h5>
            <div class="list-group list-group-flush">
                @if($stats['pending_courses'] > 0)
                <a href="{{ route('admin.courses.index', ['status'=>'pending']) }}"
                   class="list-group-item list-group-item-action d-flex justify-content-between align-items-center border-0 py-2 px-0">
                    <span><i class="bi bi-clock text-warning me-2"></i>Pending Course Reviews</span>
                    <span class="badge bg-warning text-dark">{{ $stats['pending_courses'] }}</span>
                </a>
                @endif
                @if($stats['pending_applications'] > 0)
                <a href="{{ route('admin.applications.index') }}"
                   class="list-group-item list-group-item-action d-flex justify-content-between align-items-center border-0 py-2 px-0">
                    <span><i class="bi bi-person-check text-info me-2"></i>Instructor Applications</span>
                    <span class="badge bg-info">{{ $stats['pending_applications'] }}</span>
                </a>
                @endif
                <a href="{{ route('admin.contacts.index') }}"
                   class="list-group-item list-group-item-action d-flex justify-content-between align-items-center border-0 py-2 px-0">
                    <span><i class="bi bi-envelope text-primary me-2"></i>Contact Messages</span>
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
                <h5 class="mb-0">Recent Users</h5>
                <a href="{{ route('admin.users.index') }}" class="btn btn-outline-primary btn-sm">View All</a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr><th>Name</th><th>Role</th><th>Joined</th></tr>
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
                            <td><span class="badge bg-primary-light text-primary" style="background:#EEF3FF!important;">{{ ucfirst($u->role) }}</span></td>
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
                <h5 class="mb-0">Pending Course Reviews</h5>
                <a href="{{ route('admin.courses.index', ['status'=>'pending']) }}" class="btn btn-outline-primary btn-sm">View All</a>
            </div>
            @forelse($pendingCourses as $course)
            <div class="d-flex align-items-center gap-3 mb-3 pb-3 border-bottom">
                <img src="{{ $course->thumbnailUrl() }}" class="rounded" width="56" height="40" style="object-fit:cover;" alt="{{ e($course->title()) }}">
                <div class="flex-grow-1">
                    <div style="font-size:.88rem;font-weight:600;">{{ $course->title() }}</div>
                    <div style="font-size:.75rem;color:var(--text-muted);">by {{ $course->instructor?->name }}</div>
                </div>
                <div class="d-flex gap-1">
                    <form method="POST" action="{{ route('admin.courses.approve', $course) }}">
                        @csrf
                        <button class="btn btn-success btn-sm" title="Approve"><i class="bi bi-check"></i></button>
                    </form>
                    <a href="{{ route('admin.courses.show', $course) }}" class="btn btn-primary btn-sm" title="Review"><i class="bi bi-eye"></i></a>
                </div>
            </div>
            @empty
            <p class="text-muted small">No pending courses.</p>
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

const labels = userData.map(d => `${d.year}-${String(d.month).padStart(2,'0')}`);

new Chart(growthChart, {
    type: 'line',
    data: {
        labels: labels,
        datasets: [
            {
                label: 'New Users',
                data: userData.map(d => d.count),
                borderColor: '#0A2A5E',
                backgroundColor: 'rgba(10,42,94,.1)',
                tension: 0.4,
                fill: true,
            },
            {
                label: 'Enrollments',
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
