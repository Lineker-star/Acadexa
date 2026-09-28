<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Public\HomeController;
use App\Http\Controllers\Public\CourseController as PublicCourseController;
use App\Http\Controllers\Public\CategoryController as PublicCategoryController;
use App\Http\Controllers\Public\ContactController;
use App\Http\Controllers\Public\CmsPageController;
use App\Http\Controllers\Public\SearchController;
use App\Http\Controllers\Public\LocaleController;
use App\Http\Controllers\Public\BecomeInstructorController;
use App\Http\Controllers\Public\CertificateVerifyController;
use App\Http\Controllers\Student\DashboardController as StudentDashboard;
use App\Http\Controllers\Student\CourseController as StudentCourse;
use App\Http\Controllers\Student\LessonController as StudentLesson;
use App\Http\Controllers\Student\QuizController as StudentQuiz;
use App\Http\Controllers\Student\CertificateController as StudentCertificate;
use App\Http\Controllers\Student\ProfileController as StudentProfile;
use App\Http\Controllers\Student\ReviewController as StudentReview;
use App\Http\Controllers\Student\WishlistController as StudentWishlist;
use App\Http\Controllers\Student\SubscriptionController;
use App\Http\Controllers\Instructor\DashboardController as InstructorDashboard;
use App\Http\Controllers\Instructor\CourseController as InstructorCourse;
use App\Http\Controllers\Instructor\ApplicationController as InstructorApplication;
use App\Http\Controllers\Instructor\CommentController as InstructorComment;
use App\Http\Controllers\Admin\DashboardController as AdminDashboard;
use App\Http\Controllers\Admin\UserController as AdminUser;
use App\Http\Controllers\Admin\CourseController as AdminCourse;
use App\Http\Controllers\Admin\CategoryController as AdminCategory;
use App\Http\Controllers\Admin\InstructorApplicationController as AdminApplication;
use App\Http\Controllers\Admin\AnnouncementController as AdminAnnouncement;
use App\Http\Controllers\Admin\CmsPageController as AdminCmsPage;
use App\Http\Controllers\Admin\ReviewController as AdminReview;
use App\Http\Controllers\Admin\SettingController as AdminSetting;
use App\Http\Controllers\Admin\ActivityLogController as AdminActivityLog;
use App\Http\Controllers\Admin\ContactController as AdminContact;
use App\Http\Controllers\Admin\TranslationController as AdminTranslation;
use App\Http\Controllers\Admin\CertificateController as AdminCertificate;
use App\Http\Controllers\Admin\Auth\AdminAuthController;
use App\Http\Controllers\Admin\ReportController as AdminReport;
use App\Http\Controllers\Admin\TwoFactorController as AdminTwoFactor;
use App\Http\Controllers\Instructor\ModuleController as InstructorModule;
use App\Http\Controllers\Instructor\LessonController as InstructorLesson;
use App\Http\Controllers\Instructor\LessonResourceController as InstructorResource;
use App\Http\Controllers\Instructor\QuizController as InstructorQuiz;
use App\Http\Controllers\Instructor\SubmissionController as InstructorSubmission;
use App\Http\Controllers\Instructor\CourseStudentController as InstructorStudent;
use App\Http\Controllers\Instructor\CourseAnnouncementController as InstructorAnnouncement;
use App\Http\Controllers\Student\AssignmentController as StudentAssignment;
use App\Http\Controllers\Student\OfflineController;
use App\Http\Controllers\Student\LibraryController as StudentLibrary;
use App\Http\Controllers\Instructor\AssessmentController as InstructorAssessment;
use App\Http\Controllers\Instructor\BookController as InstructorBook;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PwaController;

// ─── Public / Locale ─────────────────────────────────────────────────────────

Route::post('/locale', [LocaleController::class, 'switch'])->name('locale.switch');
Route::get('/sitemap.xml', [HomeController::class, 'sitemap'])->name('sitemap');
Route::get('/robots.txt', [HomeController::class, 'robots'])->name('robots');

// ─── PWA ──────────────────────────────────────────────────────────────────────

Route::get('/manifest.webmanifest', [PwaController::class, 'manifest'])->name('pwa.manifest');
Route::get('/sw.js', [PwaController::class, 'serviceWorker'])->name('pwa.sw');
Route::get('/icons.svg', [PwaController::class, 'iconSprite'])->name('pwa.icons');
Route::get('/pwa/{name}', [PwaController::class, 'icon'])->where('name', '[a-z0-9\-]+\.png')->name('pwa.icon');
// Offline app shell: public on purpose (cached by the service worker, holds no server data).
Route::get('/offline', [OfflineController::class, 'index'])->name('offline');

// ─── Protected media (access checked in the controller; free previews are public) ──

Route::get('/media/lessons/{lesson}/video', [MediaController::class, 'video'])->name('media.lesson.video');
Route::get('/media/resources/{resource}', [MediaController::class, 'resource'])->name('media.resource');
Route::get('/media/books/{book}', [MediaController::class, 'book'])->middleware('auth')->name('media.book');
Route::get('/media/submissions/{submission}', [MediaController::class, 'submission'])->middleware('auth')->name('media.submission');

// ─── Public Pages ─────────────────────────────────────────────────────────────

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/search', [SearchController::class, 'index'])->name('search');
Route::get('/search/suggestions', [SearchController::class, 'suggestions'])->name('search.suggestions');

Route::get('/courses', [PublicCourseController::class, 'index'])->name('courses.index');
Route::get('/courses/{course:slug}', [PublicCourseController::class, 'show'])->name('courses.show');
Route::get('/courses/{course:slug}/preview/{lesson}', [PublicCourseController::class, 'preview'])->name('courses.preview');
// Numeric only: otherwise it would swallow /instructor/submissions, /instructor/earnings…
Route::get('/instructor/{user}', [PublicCourseController::class, 'instructorProfile'])->whereNumber('user')->name('instructor.profile');

Route::get('/categories/{category:slug}', [PublicCategoryController::class, 'show'])->name('categories.show');

Route::get('/contact', [ContactController::class, 'index'])->name('contact');
Route::post('/contact', [ContactController::class, 'store'])->name('contact.store')->middleware('throttle:5,1');

Route::get('/become-an-instructor', [BecomeInstructorController::class, 'index'])->name('become-instructor');

Route::get('/verify-certificate/{code}', [CertificateVerifyController::class, 'verify'])->name('certificate.verify');

// CMS Pages (about, faq, terms, privacy)
Route::get('/page/{slug}', [CmsPageController::class, 'show'])->name('cms.page');

// ─── Laravel Breeze Auth ─────────────────────────────────────────────────────

require __DIR__.'/auth.php';

// ─── Student Routes ───────────────────────────────────────────────────────────

Route::middleware(['auth', 'verified', 'role:student,instructor,admin,super_admin'])->group(function () {

    Route::get('/dashboard', [StudentDashboard::class, 'index'])->name('dashboard');

    Route::get('/subscription', [SubscriptionController::class, 'index'])->name('student.subscription');

    Route::post('/enroll/{course}', [StudentCourse::class, 'enroll'])->name('student.enroll');

    Route::middleware('trial')->group(function () {
        Route::get('/my-courses', [StudentCourse::class, 'index'])->name('student.courses.index');
        Route::get('/my-courses/{enrollment}', [StudentCourse::class, 'player'])->name('student.courses.player');
        Route::get('/my-courses/{enrollment}/results', [StudentCourse::class, 'results'])->name('student.courses.results');
        Route::get('/lessons/{lesson}/open', [StudentCourse::class, 'openLesson'])->name('student.lessons.open');
        Route::get('/my-courses/course/{course}/announcements', [StudentCourse::class, 'announcements'])->name('student.course.announcements');
        Route::post('/lessons/{lesson}/complete', [StudentLesson::class, 'complete'])->name('student.lesson.complete');
        Route::post('/lessons/{lesson}/watch', [StudentLesson::class, 'watch'])->middleware('throttle:120,1')->name('student.lesson.watch');
        Route::post('/lessons/{lesson}/comment', [StudentLesson::class, 'comment'])->middleware('throttle:10,1')->name('student.lesson.comment');
        Route::post('/lessons/{lesson}/assignment', [StudentAssignment::class, 'submit'])->middleware('throttle:10,1')->name('student.assignment.submit');
        Route::post('/quiz/{quiz}/start', [StudentQuiz::class, 'start'])->name('student.quiz.start');
        Route::post('/quiz/{quiz}/attempt', [StudentQuiz::class, 'attempt'])->middleware('throttle:20,1')->name('student.quiz.attempt');

        // Personal library: books kept in the account, readable offline in the app
        Route::get('/my-library', [StudentLibrary::class, 'index'])->name('student.library.index');
        Route::get('/my-library/data', [StudentLibrary::class, 'data'])->name('student.library.data');
        Route::get('/my-library/course/{course}', [StudentLibrary::class, 'course'])->name('student.library.course');
        Route::post('/my-library/{book}', [StudentLibrary::class, 'add'])->name('student.library.add');
        Route::delete('/my-library/{book}', [StudentLibrary::class, 'remove'])->name('student.library.remove');
        Route::post('/my-library/{book}/position', [StudentLibrary::class, 'position'])->middleware('throttle:120,1')->name('student.library.position');

        // Offline mode (PWA)
        Route::get('/offline/courses/{enrollment}/package', [OfflineController::class, 'package'])->name('offline.package');
        Route::post('/offline/sync', [OfflineController::class, 'sync'])->middleware('throttle:30,1')->name('offline.sync');
    });

    Route::get('/offline/session', [OfflineController::class, 'session'])->name('offline.session');

    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/{id}/open', [NotificationController::class, 'open'])->name('notifications.open');
    Route::post('/notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.read-all');
    Route::post('/notifications/preferences', [NotificationController::class, 'preferences'])->name('notifications.preferences');

    // Private messages (student <-> instructor)
    Route::get('/messages', [MessageController::class, 'index'])->name('messages.index');
    Route::post('/messages', [MessageController::class, 'store'])->middleware('throttle:10,1')->name('messages.store');
    Route::get('/messages/{conversation}', [MessageController::class, 'show'])->name('messages.show');
    Route::post('/messages/{conversation}', [MessageController::class, 'reply'])->middleware('throttle:30,1')->name('messages.reply');

    Route::get('/my-certificates', [StudentCertificate::class, 'index'])->name('student.certificates.index');
    Route::get('/my-certificates/{certificate}/download', [StudentCertificate::class, 'download'])->name('student.certificates.download');

    Route::get('/profile', [StudentProfile::class, 'edit'])->name('student.profile.edit');
    Route::post('/profile', [StudentProfile::class, 'update'])->name('student.profile.update');

    Route::get('/wishlist', [StudentWishlist::class, 'index'])->name('student.wishlist.index');
    Route::post('/wishlist/{course}', [StudentWishlist::class, 'toggle'])->name('student.wishlist.toggle');

    Route::post('/reviews/{course}', [StudentReview::class, 'store'])->name('student.review.store');
});

// ─── Instructor Routes ────────────────────────────────────────────────────────

Route::prefix('instructor')->name('instructor.')->middleware(['auth', 'verified'])->group(function () {

    Route::post('/apply', [InstructorApplication::class, 'store'])->name('apply.store');

    Route::middleware('role:instructor,admin,super_admin')->group(function () {
        Route::get('/', [InstructorDashboard::class, 'index'])->name('dashboard');
        Route::get('/earnings', [InstructorDashboard::class, 'earnings'])->name('earnings');

        Route::resource('courses', InstructorCourse::class)->except(['show']);
        Route::post('courses/{course}/submit', [InstructorCourse::class, 'submit'])->name('courses.submit');

        // Curriculum: modules
        Route::post('courses/{course}/modules', [InstructorModule::class, 'store'])->name('modules.store');
        Route::post('courses/{course}/modules/reorder', [InstructorModule::class, 'reorder'])->name('modules.reorder');
        Route::put('modules/{module}', [InstructorModule::class, 'update'])->name('modules.update');
        Route::delete('modules/{module}', [InstructorModule::class, 'destroy'])->name('modules.destroy');

        // Curriculum: lessons
        Route::post('modules/{module}/lessons', [InstructorLesson::class, 'store'])->name('lessons.store');
        Route::post('courses/{course}/lessons/reorder', [InstructorLesson::class, 'reorder'])->name('lessons.reorder');
        Route::get('lessons/{lesson}/edit', [InstructorLesson::class, 'edit'])->name('lessons.edit');
        Route::put('lessons/{lesson}', [InstructorLesson::class, 'update'])->name('lessons.update');
        Route::delete('lessons/{lesson}', [InstructorLesson::class, 'destroy'])->name('lessons.destroy');
        Route::get('lessons/{lesson}/video/status', [InstructorLesson::class, 'uploadStatus'])->name('lessons.video.status');
        Route::post('lessons/{lesson}/video/chunk', [InstructorLesson::class, 'uploadChunk'])->name('lessons.video.chunk');
        Route::delete('lessons/{lesson}/video', [InstructorLesson::class, 'deleteVideo'])->name('lessons.video.destroy');
        Route::post('lessons/{lesson}/resources', [InstructorResource::class, 'store'])->name('resources.store');
        Route::delete('resources/{resource}', [InstructorResource::class, 'destroy'])->name('resources.destroy');

        // Quizzes
        Route::put('quizzes/{quiz}', [InstructorQuiz::class, 'updateSettings'])->name('quizzes.update');
        Route::post('quizzes/{quiz}/questions', [InstructorQuiz::class, 'storeQuestion'])->name('questions.store');
        Route::post('quizzes/{quiz}/questions/reorder', [InstructorQuiz::class, 'reorderQuestions'])->name('questions.reorder');
        Route::put('questions/{question}', [InstructorQuiz::class, 'updateQuestion'])->name('questions.update');
        Route::delete('questions/{question}', [InstructorQuiz::class, 'destroyQuestion'])->name('questions.destroy');
        Route::post('quizzes/{quiz}/import', [InstructorQuiz::class, 'import'])->name('questions.import');

        // Assessment path: lesson quiz, module exercise, final evaluation
        Route::get('quizzes/{quiz}/edit', [InstructorAssessment::class, 'edit'])->name('quizzes.edit');
        Route::get('lessons/{lesson}/quiz', [InstructorAssessment::class, 'lessonQuiz'])->name('assessments.lesson');
        Route::get('modules/{module}/exercise', [InstructorAssessment::class, 'moduleExercise'])->name('assessments.module');
        Route::get('courses/{course}/final-evaluation', [InstructorAssessment::class, 'finalEvaluation'])->name('assessments.final');

        // Course books (student library)
        Route::post('courses/{course}/books', [InstructorBook::class, 'store'])->name('books.store');
        Route::put('books/{book}', [InstructorBook::class, 'update'])->name('books.update');
        Route::delete('books/{book}', [InstructorBook::class, 'destroy'])->name('books.destroy');

        // Follow-up: students, assignments, announcements, Q&A
        Route::get('courses/{course}/students', [InstructorStudent::class, 'index'])->name('students.index');
        Route::get('courses/{course}/knowledge', [InstructorStudent::class, 'knowledge'])->name('students.knowledge');
        Route::get('courses/{course}/students/export', [InstructorStudent::class, 'export'])->name('students.export');
        Route::get('courses/{course}/students/{enrollment}', [InstructorStudent::class, 'show'])->name('students.show');
        Route::get('submissions', [InstructorSubmission::class, 'index'])->name('submissions.index');
        Route::get('submissions/{submission}', [InstructorSubmission::class, 'show'])->name('submissions.show');
        Route::post('submissions/{submission}/grade', [InstructorSubmission::class, 'grade'])->name('submissions.grade');
        Route::get('courses/{course}/announcements', [InstructorAnnouncement::class, 'index'])->name('announcements.index');
        Route::post('courses/{course}/announcements', [InstructorAnnouncement::class, 'store'])->name('announcements.store');
        Route::delete('announcements/{announcement}', [InstructorAnnouncement::class, 'destroy'])->name('announcements.destroy');

        Route::get('qa', [InstructorComment::class, 'index'])->name('qa.index');
        Route::post('comments/{comment}/reply', [InstructorComment::class, 'reply'])->name('comment.reply');
    });
});

// ─── Admin Routes ─────────────────────────────────────────────────────────────

Route::prefix('acadexa-control')->name('admin.')->group(function () {

    // Admin auth (separate from Breeze)
    Route::get('/login', [AdminAuthController::class, 'showLogin'])->name('login')->middleware('guest');
    Route::post('/login', [AdminAuthController::class, 'login'])->name('login.post')->middleware('throttle:10,1');
    Route::post('/logout', [AdminAuthController::class, 'logout'])->name('logout');

    // Second login step for admins with two-factor authentication
    Route::get('/two-factor', [AdminTwoFactor::class, 'challenge'])->name('2fa.challenge');
    Route::post('/two-factor', [AdminTwoFactor::class, 'verify'])->middleware('throttle:10,1')->name('2fa.verify');

    Route::middleware('admin')->group(function () {
        Route::get('/', [AdminDashboard::class, 'index'])->name('dashboard');

        // Own account security (2FA)
        Route::get('/security', [AdminTwoFactor::class, 'show'])->name('security');
        Route::post('/security/2fa/enable', [AdminTwoFactor::class, 'enable'])->name('2fa.enable');
        Route::post('/security/2fa/disable', [AdminTwoFactor::class, 'disable'])->name('2fa.disable');

        // Reports & CSV exports
        Route::get('/reports', [AdminReport::class, 'index'])->name('reports.index');
        Route::get('/reports/export/{type}', [AdminReport::class, 'export'])->name('reports.export');

        // Users
        Route::get('/users', [AdminUser::class, 'index'])->name('users.index');
        Route::get('/users/{user}', [AdminUser::class, 'show'])->name('users.show');
        Route::post('/users/{user}/activate', [AdminUser::class, 'activate'])->name('users.activate');
        Route::post('/users/{user}/deactivate', [AdminUser::class, 'deactivate'])->name('users.deactivate');
        Route::post('/users/{user}/ban', [AdminUser::class, 'ban'])->name('users.ban');
        Route::post('/users/{user}/unban', [AdminUser::class, 'unban'])->name('users.unban');
        Route::post('/users/{user}/permissions', [AdminUser::class, 'updatePermissions'])->name('users.permissions');
        Route::post('/users/{user}/reset-2fa', [AdminTwoFactor::class, 'reset'])->name('users.reset-2fa');
        Route::post('/users/{user}/extend-trial', [AdminUser::class, 'extendTrial'])->name('users.extend-trial');
        Route::post('/users/{user}/reset-password', [AdminUser::class, 'resetPassword'])->name('users.reset-password');

        // Courses
        Route::get('/courses', [AdminCourse::class, 'index'])->name('courses.index');
        Route::get('/courses/{course}', [AdminCourse::class, 'show'])->name('courses.show');
        Route::post('/courses/{course}/approve', [AdminCourse::class, 'approve'])->name('courses.approve');
        Route::post('/courses/{course}/reject', [AdminCourse::class, 'reject'])->name('courses.reject');
        Route::post('/courses/{course}/feature', [AdminCourse::class, 'feature'])->name('courses.feature');
        Route::post('/courses/{course}/unpublish', [AdminCourse::class, 'unpublish'])->name('courses.unpublish');
        Route::delete('/courses/{course}', [AdminCourse::class, 'destroy'])->name('courses.destroy');

        // Categories
        Route::get('/categories', [AdminCategory::class, 'index'])->name('categories.index');
        Route::post('/categories', [AdminCategory::class, 'store'])->name('categories.store');
        Route::put('/categories/{category}', [AdminCategory::class, 'update'])->name('categories.update');
        Route::delete('/categories/{category}', [AdminCategory::class, 'destroy'])->name('categories.destroy');
        Route::post('/categories/reorder', [AdminCategory::class, 'reorder'])->name('categories.reorder');

        // Instructor Applications
        Route::get('/instructor-applications', [AdminApplication::class, 'index'])->name('applications.index');
        Route::get('/instructor-applications/{application}', [AdminApplication::class, 'show'])->name('applications.show');
        Route::post('/instructor-applications/{application}/approve', [AdminApplication::class, 'approve'])->name('applications.approve');
        Route::post('/instructor-applications/{application}/reject', [AdminApplication::class, 'reject'])->name('applications.reject');

        // Announcements
        Route::resource('announcements', AdminAnnouncement::class)->except(['show']);

        // CMS Pages
        Route::get('/cms-pages', [AdminCmsPage::class, 'index'])->name('cms-pages.index');
        Route::get('/cms-pages/create', [AdminCmsPage::class, 'create'])->name('cms-pages.create');
        Route::post('/cms-pages', [AdminCmsPage::class, 'store'])->name('cms-pages.store');
        Route::get('/cms-pages/{cmsPage}/edit', [AdminCmsPage::class, 'edit'])->name('cms-pages.edit');
        Route::put('/cms-pages/{cmsPage}', [AdminCmsPage::class, 'update'])->name('cms-pages.update');
        Route::delete('/cms-pages/{cmsPage}', [AdminCmsPage::class, 'destroy'])->name('cms-pages.destroy');

        // Reviews
        Route::get('/reviews', [AdminReview::class, 'index'])->name('reviews.index');
        Route::post('/reviews/{review}/flag', [AdminReview::class, 'flag'])->name('reviews.flag');
        Route::delete('/reviews/{review}', [AdminReview::class, 'destroy'])->name('reviews.destroy');

        // Settings
        Route::get('/settings', [AdminSetting::class, 'index'])->name('settings.index');
        Route::post('/settings', [AdminSetting::class, 'update'])->name('settings.update');

        // Activity Logs
        Route::get('/activity-logs', [AdminActivityLog::class, 'index'])->name('activity-logs.index');
        Route::post('/activity-logs/clear', [AdminActivityLog::class, 'clear'])->name('activity-logs.clear');

        // Contacts
        Route::get('/contacts', [AdminContact::class, 'index'])->name('contacts.index');
        Route::get('/contacts/{contact}', [AdminContact::class, 'show'])->name('contacts.show');
        Route::delete('/contacts/{contact}', [AdminContact::class, 'destroy'])->name('contacts.destroy');

        // Translations
        Route::get('/translations', [AdminTranslation::class, 'index'])->name('translations.index');
        Route::post('/translations', [AdminTranslation::class, 'update'])->name('translations.update');

        // Certificates
        Route::get('/certificates', [AdminCertificate::class, 'index'])->name('certificates.index');
        Route::delete('/certificates/{certificate}', [AdminCertificate::class, 'destroy'])->name('certificates.destroy');
        Route::get('/certificate-template', [AdminCertificate::class, 'template'])->name('certificate.template');
        Route::put('/certificate-template', [AdminCertificate::class, 'updateTemplate'])->name('certificate.template.update');
    });
});
