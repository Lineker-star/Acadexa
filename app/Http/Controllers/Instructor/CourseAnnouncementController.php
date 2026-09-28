<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Instructor\Concerns\AuthorizesCourse;
use App\Models\Course;
use App\Models\CourseAnnouncement;
use App\Models\User;
use App\Notifications\CourseAnnouncementPosted;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;

class CourseAnnouncementController extends Controller
{
    use AuthorizesCourse;

    public function index(Course $course)
    {
        $this->authorizeCourse($course);
        $course->load('translations');
        $announcements = $course->announcements()->with('author')->paginate(15);

        return view('instructor.announcements.index', compact('course', 'announcements'));
    }

    public function store(Request $request, Course $course)
    {
        $this->authorizeCourse($course);
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'body'  => ['required', 'string', 'max:10000'],
        ]);

        $announcement = CourseAnnouncement::create($data + ['course_id' => $course->id, 'user_id' => $request->user()->id]);

        $students = User::whereIn('id', $course->enrollments()->select('user_id'))->where('is_active', true)->get();
        Notification::send($students, new CourseAnnouncementPosted($announcement));

        return back()->with('success', trans_choice('lms.announcement_sent', $students->count(), ['count' => $students->count()]));
    }

    public function destroy(CourseAnnouncement $announcement)
    {
        $this->authorizeCourse($announcement->course);
        $announcement->delete();

        return back()->with('success', __('lms.announcement_deleted'));
    }
}
