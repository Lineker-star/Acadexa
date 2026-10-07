<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $months = collect(range(11, 0))->map(fn ($i) => now()->startOfMonth()->subMonths($i));
        $from = $months->first();

        $series = [
            'registrations' => $this->monthly(User::where('role', 'student'), 'created_at', $from),
            'enrollments'   => $this->monthly(Enrollment::query(), 'enrolled_at', $from),
            'completions'   => $this->monthly(Enrollment::whereNotNull('completed_at'), 'completed_at', $from),
            'certificates'  => $this->monthly(Certificate::query(), 'issued_at', $from),
        ];

        $rows = $months->map(fn (Carbon $m) => [
            'label'         => $m->translatedFormat('M Y'),
            'registrations' => $series['registrations'][$m->format('Y-m')] ?? 0,
            'enrollments'   => $series['enrollments'][$m->format('Y-m')] ?? 0,
            'completions'   => $series['completions'][$m->format('Y-m')] ?? 0,
            'certificates'  => $series['certificates'][$m->format('Y-m')] ?? 0,
        ]);

        $topCourses = Course::with('translations')
            ->withCount(['enrollments', 'enrollments as completed_count' => fn ($q) => $q->where('progress_percent', '>=', 100)])
            ->withAvg('reviews', 'rating')
            ->orderByDesc('enrollments_count')->take(10)->get();

        $totals = [
            'students'    => User::where('role', 'student')->count(),
            'active_30d'  => Enrollment::where('updated_at', '>=', now()->subDays(30))->distinct('user_id')->count('user_id'),
            'enrollments' => Enrollment::count(),
            'completion'  => ($e = Enrollment::count()) ? round(Enrollment::where('progress_percent', '>=', 100)->count() / $e * 100, 1) : 0,
        ];

        return view('admin.reports.index', compact('rows', 'topCourses', 'totals'));
    }

    public function export(Request $request, string $type): StreamedResponse
    {
        abort_unless(in_array($type, ['users', 'enrollments', 'certificates'], true), 404);
        ActivityLog::record('report_export', "Exported {$type} CSV");

        [$headers, $query, $map] = match ($type) {
            'users' => [
                ['ID', 'Nom', 'E-mail', 'Rôle', 'Pays', 'Actif', 'Banni', 'Inscrit le', 'Fin d’essai'],
                User::query()->orderBy('id'),
                fn (User $u) => [$u->id, $u->name, $u->email, $u->role, $u->country, $u->is_active ? 'oui' : 'non',
                    $u->isBanned() ? 'oui' : 'non', $u->created_at?->format('Y-m-d'), $u->trialEndsAt()?->format('Y-m-d')],
            ],
            'enrollments' => [
                ['ID', 'Étudiant', 'E-mail', 'Cours', 'Inscrit le', 'Progression (%)', 'Terminé le'],
                Enrollment::with(['user', 'course.translations'])->orderBy('id'),
                fn (Enrollment $e) => [$e->id, $e->user?->name, $e->user?->email, $e->course?->title(),
                    $e->enrolled_at?->format('Y-m-d'), $e->progress_percent, $e->completed_at?->format('Y-m-d')],
            ],
            'certificates' => [
                ['Code', 'Étudiant', 'E-mail', 'Cours', 'Délivré le'],
                Certificate::with(['user', 'course.translations'])->orderBy('id'),
                fn (Certificate $c) => [$c->certificate_code, $c->user?->name, $c->user?->email, $c->course?->title(), $c->issued_at?->format('Y-m-d')],
            ],
        };

        return response()->streamDownload(function () use ($headers, $query, $map) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM for Excel
            fputcsv($out, $headers, ';');
            $query->chunk(500, function ($rows) use ($out, $map) {
                foreach ($rows as $row) {
                    fputcsv($out, array_map(fn ($v) => $this->csvSafe($v), $map($row)), ';');
                }
            });
            fclose($out);
        }, "acadexxa-{$type}-" . now()->format('Ymd') . '.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** Neutralises spreadsheet formulas in user-provided values (CSV injection). */
    private function csvSafe($value)
    {
        if (is_string($value) && $value !== '' && in_array($value[0], ['=', '+', '-', '@', "\t", "\r"], true)) {
            return "'" . $value;
        }
        return $value;
    }

    /** Counts per "Y-m" month, computed in PHP so it works on MySQL and SQLite alike. */
    private function monthly($query, string $column, Carbon $from): array
    {
        return $query->where($column, '>=', $from)->pluck($column)
            ->groupBy(fn ($date) => Carbon::parse($date)->format('Y-m'))
            ->map->count()->all();
    }
}
