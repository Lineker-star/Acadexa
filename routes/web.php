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

// ─── Public / Locale ─────────────────────────────────────────────────────────

Route::post('/locale', [LocaleController::class, 'switch'])->name('locale.switch');
Route::get('/sitemap.xml', [HomeController::class, 'sitemap'])->name('sitemap');
Route::get('/robots.txt', [HomeController::class, 'robots'])->name('robots');

// ─── Public Pages ─────────────────────────────────────────────────────────────

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/search', [SearchController::class, 'index'])->name('search');
Route::get('/search/suggestions', [SearchController::class, 'suggestions'])->name('search.suggestions');

Route::get('/courses', [PublicCourseController::class, 'index'])->name('courses.index');
Route::get('/courses/{course:slug}', [PublicCourseController::class, 'show'])->name('courses.show');
Route::get('/instructor/{user}', [PublicCourseController::class, 'instructorProfile'])->name('instructor.profile');

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
        Route::post('/lessons/{lesson}/complete', [StudentLesson::class, 'complete'])->name('student.lesson.complete');
        Route::post('/lessons/{lesson}/comment', [StudentLesson::class, 'comment'])->name('student.lesson.comment');
        Route::post('/quiz/{quiz}/attempt', [StudentQuiz::class, 'attempt'])->name('student.quiz.attempt');
    });

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

        Route::resource('courses', InstructorCourse::class);
        Route::post('courses/{course}/submit', [InstructorCourse::class, 'submit'])->name('courses.submit');
        Route::post('courses/{course}/modules', [InstructorCourse::class, 'storeModule'])->name('courses.modules.store');
        Route::post('courses/{module}/lessons', [InstructorCourse::class, 'storeLesson'])->name('courses.lessons.store');
        Route::delete('modules/{module}', [InstructorCourse::class, 'destroyModule'])->name('modules.destroy');
        Route::delete('lessons/{lesson}', [InstructorCourse::class, 'destroyLesson'])->name('lessons.destroy');

        Route::post('comments/{comment}/reply', [InstructorComment::class, 'reply'])->name('comment.reply');
    });
});

// ─── Admin Routes ─────────────────────────────────────────────────────────────

Route::prefix('acadexa-control')->name('admin.')->group(function () {

    // Admin auth (separate from Breeze)
    Route::get('/login', [AdminAuthController::class, 'showLogin'])->name('login')->middleware('guest');
    Route::post('/login', [AdminAuthController::class, 'login'])->name('login.post')->middleware('throttle:10,1');
    Route::post('/logout', [AdminAuthController::class, 'logout'])->name('logout');

    Route::middleware('admin')->group(function () {
        Route::get('/', [AdminDashboard::class, 'index'])->name('dashboard');

        // Users
        Route::get('/users', [AdminUser::class, 'index'])->name('users.index');
        Route::get('/users/{user}', [AdminUser::class, 'show'])->name('users.show');
        Route::post('/users/{user}/activate', [AdminUser::class, 'activate'])->name('users.activate');
        Route::post('/users/{user}/deactivate', [AdminUser::class, 'deactivate'])->name('users.deactivate');
        Route::post('/users/{user}/ban', [AdminUser::class, 'ban'])->name('users.ban');
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
