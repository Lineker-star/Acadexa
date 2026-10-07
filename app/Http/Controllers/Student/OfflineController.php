<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\Quiz;
use App\Services\ProgressService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Backend of the PWA offline mode.
 *
 * - package(): everything needed to follow a course without network, as JSON. The browser
 *   stores it in IndexedDB and caches the listed media URLs (videos, resources).
 * - sync(): replays actions made offline (lesson completed, watch time, quiz answers, reading position).
 *   Quiz answers are graded here. The package only carries salted hashes of the right answers of
 *   lesson quizzes and module exercises, so the app can show a provisional result offline and let
 *   the student move on; the server's grading on sync is the one that counts. The final
 *   evaluation is never available offline.
 */
class OfflineController extends Controller
{
    public function __construct(private ProgressService $progress) {}

    public function index()
    {
        return view('student.offline');
    }

    /** Current user and a fresh CSRF token, used by the sync queue after reconnecting. */
    public function session(Request $request)
    {
        return response()->json([
            'user_id' => $request->user()->id,
            'name'    => $request->user()->name,
            'csrf'    => csrf_token(),
        ])->header('Cache-Control', 'no-store');
    }

    public function package(Request $request, Enrollment $enrollment)
    {
        abort_if($enrollment->user_id !== $request->user()->id, 403);
        abort_unless($request->user()->isTrialActive(), 402, __('messages.trial_expired'));

        $enrollment->load([
            'course.translations', 'course.instructor',
            'course.modules.translations',
            'course.modules.lessons.translations',
            'course.modules.lessons.resources',
            'course.modules.lessons.quiz.questions.options',
            'course.modules.exam.questions.options',
            'course.finalExam',
        ]);
        $course = $enrollment->course;
        $completed = $this->progress->completedLessonIds($enrollment);
        $steps = $this->progress->steps($course);
        $salt = bin2hex(random_bytes(8));

        $media = [];
        $modules = $course->modules->map(function ($module) use (&$media, $completed, $salt) {
            $exam = $module->exam && $module->exam->isReady() ? $module->exam : null;
            return [
                'id'          => $module->id,
                'title'       => $module->title(),
                'description' => $module->descriptionText(),
                'hours'       => $module->hoursLabel(),
                'lessons'     => $module->lessons->map(function (Lesson $lesson) use (&$media, $completed, $salt) {
                    return $this->lessonPayload($lesson, $completed, $media, $salt);
                })->values(),
                'exam'        => $exam ? ['title' => $exam->title()] + $this->quizPayload($exam, $salt) : null,
            ];
        })->values();
        $final = $course->finalExam && $course->finalExam->isReady() ? $course->finalExam : null;

        $totalBytes = collect($media)->sum('size');

        return response()->json([
            'version'       => $this->version($enrollment),
            'generated_at'  => now()->toIso8601String(),
            'user_id'       => $request->user()->id,
            'enrollment_id' => $enrollment->id,
            'course' => [
                'id'          => $course->id,
                'slug'        => $course->slug,
                'title'       => $course->title(),
                'description' => $course->description(),
                'instructor'  => $course->instructor?->name,
                'hours'       => $course->hoursLabel(),
                'thumbnail'   => $course->thumbnailUrl(),
                'sequential'  => $course->is_sequential,
                'online_url'  => route('student.courses.player', $enrollment),
            ],
            'progress'      => (float) $enrollment->progress_percent,
            'completed_ids' => $completed,
            'passed_quiz_ids' => $this->progress->passedQuizIds($enrollment, $steps),
            'final'         => $final ? ['id' => $final->id, 'title' => $final->title(), 'questions' => $final->questionCount()] : null,
            'hash_salt'     => $salt,
            'pass_percent'  => (int) config('lms.assessment.pass_percent'),
            'modules'       => $modules,
            'media'         => array_values($media),
            'total_bytes'   => $totalBytes,
        ])->header('Cache-Control', 'no-store');
    }

    /**
     * Replays offline events in order. Body: { events: [ {id, type, ...}, ... ] }
     * Returns one result per event id; events that failed permanently are reported
     * so the browser drops them instead of retrying forever.
     */
    public function sync(Request $request)
    {
        $data = $request->validate([
            'events'        => ['required', 'array', 'max:200'],
            'events.*.id'   => ['required', 'string', 'max:64'],
            'events.*.type' => ['required', 'in:complete,watch,quiz,library_position'],
        ]);

        $results = [];
        foreach ($request->input('events') as $event) {
            $results[] = ['id' => $event['id']] + $this->replay($request, $event);
        }

        return response()->json(['results' => $results]);
    }

    private function replay(Request $request, array $event): array
    {
        try {
            $response = match ($event['type']) {
                'watch' => app(LessonController::class)->watch(
                    $this->subRequest($request, [
                        'position'      => $event['position'] ?? 0,
                        'duration'      => $event['duration'] ?? 0,
                        'watched_delta' => $event['watched_delta'] ?? 0,
                    ]),
                    Lesson::findOrFail($event['lesson_id'] ?? 0)
                ),
                'complete' => app(LessonController::class)->complete(
                    $this->subRequest($request, []),
                    Lesson::findOrFail($event['lesson_id'] ?? 0)
                ),
                'quiz' => app(QuizController::class)->attempt(
                    $this->subRequest($request, [
                        'answers'    => $event['answers'] ?? [],
                        'offline_at' => $event['at'] ?? null,
                    ]),
                    Quiz::findOrFail($event['quiz_id'] ?? 0)
                ),
                'library_position' => app(LibraryController::class)->position(
                    $this->subRequest($request, [
                        'position' => (int) ($event['position'] ?? 0),
                        'progress' => (int) ($event['progress'] ?? 0),
                    ]),
                    \App\Models\Book::findOrFail($event['book_id'] ?? 0)
                ),
            };

            $status = $response instanceof JsonResponse ? $response->getStatusCode() : 200;
            return ['ok' => $status < 400, 'status' => $status, 'data' => $response->getData(true)];
        } catch (\Illuminate\Validation\ValidationException $e) {
            return ['ok' => false, 'status' => 422, 'data' => ['message' => $e->getMessage()]];
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return ['ok' => false, 'status' => 404, 'data' => ['message' => __('not found')]];
        } catch (HttpException $e) {
            return ['ok' => false, 'status' => $e->getStatusCode(), 'data' => ['message' => $e->getMessage()]];
        }
    }

    private function subRequest(Request $parent, array $input): Request
    {
        $sub = Request::create($parent->url(), 'POST', $input);
        $sub->headers->set('Accept', 'application/json');
        $sub->setUserResolver(fn () => $parent->user());
        $sub->setLaravelSession($parent->session());
        return $sub;
    }

    private function lessonPayload(Lesson $lesson, array $completed, array &$media, string $salt): array
    {
        $offline = $lesson->isOfflineCapable();
        $video = null;

        if ($lesson->type === 'video' && $lesson->hasVideo()) {
            $kind = $lesson->videoKind();
            $video = ['kind' => $kind, 'offline' => $kind === Lesson::VIDEO_UPLOAD && $lesson->is_downloadable];
            if ($video['offline']) {
                $video['url'] = route('media.lesson.video', $lesson);
                $video['mime'] = $lesson->video_mime ?: 'video/mp4';
                $media[] = ['url' => $video['url'], 'size' => (int) $lesson->video_size, 'kind' => 'video', 'lesson_id' => $lesson->id];
            }
        }

        $resources = $lesson->resources->map(function ($resource) use (&$media, $lesson) {
            $url = route('media.resource', $resource);
            if ($lesson->is_downloadable) {
                $media[] = ['url' => $url, 'size' => (int) $resource->size, 'kind' => 'resource', 'lesson_id' => $lesson->id];
            }
            return ['title' => $resource->title, 'name' => $resource->original_name, 'size' => $resource->sizeLabel(), 'url' => $url, 'kind' => \App\Support\MediaKind::of($resource->original_name)];
        })->values();

        $studentQuiz = $lesson->studentQuiz();
        $quiz = $studentQuiz ? $this->quizPayload($studentQuiz, $salt) : null;

        return [
            'id'          => $lesson->id,
            'title'       => $lesson->title(),
            'type'        => $lesson->type,
            'minutes'     => $lesson->duration_minutes,
            'content'     => $lesson->renderedBody(),
            'video'       => $video,
            'resources'   => $resources,
            'quiz'        => $quiz,
            'offline'     => $offline || $lesson->type === 'quiz' || $lesson->type === 'assignment',
            'completable' => $lesson->isManuallyCompletable(),
            'quiz_check'  => $lesson->requiresQuiz(),
            'completed'   => in_array($lesson->id, $completed, true),
        ];
    }

    /**
     * Questions without the right answers. Each question carries sha256(salt|id|right option ids):
     * enough for a provisional offline result, while the server grades the answers on sync.
     */
    private function quizPayload(Quiz $quiz, string $salt): array
    {
        $timed = (bool) $quiz->time_limit_minutes;
        $open = $quiz->questions->contains(fn ($question) => $question->isOpen());
        if ($open) {
            return ['id' => $quiz->id, 'scope' => $quiz->scope, 'passing_score' => 0, 'timed' => false, 'open' => true, 'questions' => []];
        }
        return [
            'id'            => $quiz->id,
            'scope'         => $quiz->scope,
            'passing_score' => $quiz->effectivePassingScore(),
            'timed'         => $timed,
            'questions'     => $timed ? [] : $quiz->questions->map(fn ($question) => [
                'id'       => $question->id,
                'text'     => $question->question,
                'multiple' => $question->type === 'multiple',
                'check'    => hash('sha256', $salt . '|' . $question->id . '|' . $question->options->where('is_correct', true)->pluck('id')->sort()->values()->implode(',')),
                'options'  => $question->options->map(fn ($o) => ['id' => $o->id, 'text' => $o->option_text])->values(),
            ])->values(),
        ];
    }

    /** Changes whenever the course content changes, so the app knows to refresh the download. */
    private function version(Enrollment $enrollment): string
    {
        $course = $enrollment->course;
        $stamps = [$course->updated_at?->timestamp, $course->finalExam?->updated_at?->timestamp];
        foreach ($course->modules as $module) {
            $stamps[] = $module->updated_at?->timestamp;
            $stamps[] = $module->exam?->updated_at?->timestamp;
            $stamps[] = $module->exam?->questions->max('updated_at')?->timestamp;
            foreach ($module->lessons as $lesson) {
                $stamps[] = $lesson->updated_at?->timestamp;
                $stamps[] = $lesson->quiz?->questions->max('updated_at')?->timestamp;
                $stamps[] = $lesson->id;
            }
        }
        return substr(sha1(implode('|', $stamps)), 0, 16);
    }
}
