<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\{User, Course, Module, Lesson, Category};
use Illuminate\Support\Str;

class CourseSeeder extends Seeder
{
    public function run(): void
    {
        $marie    = User::where('email', 'marie@acadexa.com')->first();
        $jeanpaul = User::where('email', 'jeanpaul@acadexa.com')->first();
        $aisha    = User::where('email', 'aisha@acadexa.com')->first();

        $webDevCat    = Category::where('slug', 'web-development')->first();
        $businessCat  = Category::where('slug', 'business-entrepreneurship')->first();
        $marketingCat = Category::where('slug', 'startup-innovation')->first();
        $dataCat      = Category::where('slug', 'data-science')->first();
        $designCat    = Category::where('slug', 'graphic-design')->first();

        $courses = [
            [
                'instructor' => $marie,
                'category'   => $webDevCat,
                'data'       => [
                    'title'          => 'Complete Web Development Bootcamp',
                    'slug'           => 'complete-web-development-bootcamp',
                    'description'    => 'Master HTML, CSS, JavaScript, and Laravel from zero to hero. Build real-world projects and launch your web development career.',
                    'level'          => 'beginner',
                    'status'         => 'published',
                    'featured'       => true,
                    'duration_hours' => 40,
                    'language'       => 'en',
                    'what_you_learn' => json_encode(['Build professional websites from scratch', 'Master HTML5 and CSS3', 'Learn JavaScript fundamentals', 'Work with Laravel framework', 'Deploy to production servers']),
                    'requirements'   => json_encode(['Basic computer skills', 'Access to a computer', 'Internet connection']),
                ],
                'modules' => [
                    ['title' => 'Introduction to Web Development', 'lessons' => [
                        ['title' => 'What is Web Development?', 'type' => 'video', 'is_free_preview' => true, 'video_url' => 'https://www.youtube.com/watch?v=ysEN5RaKOlA'],
                        ['title' => 'Setting Up Your Development Environment', 'type' => 'video', 'video_url' => 'https://www.youtube.com/watch?v=ysEN5RaKOlA'],
                        ['title' => 'How the Web Works', 'type' => 'text', 'content' => 'When you type a URL into your browser, a series of steps happen to deliver the webpage to you. Understanding this process helps you become a better developer.'],
                    ]],
                    ['title' => 'HTML & CSS Fundamentals', 'lessons' => [
                        ['title' => 'HTML Structure and Elements', 'type' => 'video', 'video_url' => 'https://www.youtube.com/watch?v=ysEN5RaKOlA'],
                        ['title' => 'CSS Styling and Layouts', 'type' => 'video', 'video_url' => 'https://www.youtube.com/watch?v=ysEN5RaKOlA'],
                        ['title' => 'Responsive Design with Flexbox', 'type' => 'video', 'video_url' => 'https://www.youtube.com/watch?v=ysEN5RaKOlA'],
                        ['title' => 'HTML & CSS Quiz', 'type' => 'quiz'],
                    ]],
                    ['title' => 'JavaScript Essentials', 'lessons' => [
                        ['title' => 'JavaScript Variables and Data Types', 'type' => 'video', 'video_url' => 'https://www.youtube.com/watch?v=ysEN5RaKOlA'],
                        ['title' => 'Functions and Events', 'type' => 'video', 'video_url' => 'https://www.youtube.com/watch?v=ysEN5RaKOlA'],
                        ['title' => 'DOM Manipulation', 'type' => 'video', 'video_url' => 'https://www.youtube.com/watch?v=ysEN5RaKOlA'],
                    ]],
                ],
            ],
            [
                'instructor' => $jeanpaul,
                'category'   => $businessCat,
                'data'       => [
                    'title'          => 'Business Fundamentals for African Entrepreneurs',
                    'slug'           => 'business-fundamentals-african-entrepreneurs',
                    'description'    => 'Learn the core principles of starting and running a successful business in the African context. From idea validation to scaling your enterprise.',
                    'level'          => 'beginner',
                    'status'         => 'published',
                    'featured'       => true,
                    'duration_hours' => 20,
                    'language'       => 'en',
                    'what_you_learn' => json_encode(['Validate your business idea', 'Create a business plan', 'Understand financial basics', 'Market your business locally', 'Scale your operations']),
                    'requirements'   => json_encode(['No prior business experience needed', 'An open mind and willingness to learn']),
                ],
                'modules' => [
                    ['title' => 'The Entrepreneurial Mindset', 'lessons' => [
                        ['title' => 'What Makes a Successful Entrepreneur?', 'type' => 'video', 'is_free_preview' => true, 'video_url' => 'https://www.youtube.com/watch?v=ysEN5RaKOlA'],
                        ['title' => 'Identifying Opportunities in Africa', 'type' => 'text', 'content' => 'Africa presents unique business opportunities driven by rapid urbanization, a growing middle class, and unmet needs in healthcare, education, agriculture, and technology.'],
                    ]],
                    ['title' => 'Building Your Business Plan', 'lessons' => [
                        ['title' => 'Market Research Techniques', 'type' => 'video', 'video_url' => 'https://www.youtube.com/watch?v=ysEN5RaKOlA'],
                        ['title' => 'Financial Projections Basics', 'type' => 'video', 'video_url' => 'https://www.youtube.com/watch?v=ysEN5RaKOlA'],
                        ['title' => 'Business Plan Quiz', 'type' => 'quiz'],
                    ]],
                ],
            ],
            [
                'instructor' => $aisha,
                'category'   => $marketingCat,
                'data'       => [
                    'title'          => 'Digital Marketing Mastery',
                    'slug'           => 'digital-marketing-mastery',
                    'description'    => 'Master social media marketing, SEO, email campaigns, and digital advertising to grow your brand and business online.',
                    'level'          => 'intermediate',
                    'status'         => 'published',
                    'featured'       => false,
                    'duration_hours' => 25,
                    'language'       => 'en',
                    'what_you_learn' => json_encode(['Create effective social media strategies', 'Master SEO and content marketing', 'Run Google and Facebook ad campaigns', 'Build and grow an email list', 'Measure and analyze marketing performance']),
                    'requirements'   => json_encode(['Basic understanding of social media', 'A business or project to promote']),
                ],
                'modules' => [
                    ['title' => 'Social Media Marketing', 'lessons' => [
                        ['title' => 'Choosing the Right Platforms', 'type' => 'video', 'is_free_preview' => true, 'video_url' => 'https://www.youtube.com/watch?v=ysEN5RaKOlA'],
                        ['title' => 'Creating Engaging Content', 'type' => 'video', 'video_url' => 'https://www.youtube.com/watch?v=ysEN5RaKOlA'],
                        ['title' => 'Building a Content Calendar', 'type' => 'text', 'content' => 'A content calendar helps you plan, organize, and schedule your social media posts. Consistency is key to growing your audience.'],
                    ]],
                    ['title' => 'SEO & Content Marketing', 'lessons' => [
                        ['title' => 'Keyword Research', 'type' => 'video', 'video_url' => 'https://www.youtube.com/watch?v=ysEN5RaKOlA'],
                        ['title' => 'On-Page SEO Fundamentals', 'type' => 'video', 'video_url' => 'https://www.youtube.com/watch?v=ysEN5RaKOlA'],
                    ]],
                ],
            ],
            [
                'instructor' => $marie,
                'category'   => $dataCat,
                'data'       => [
                    'title'          => 'Introduction to Data Science with Python',
                    'slug'           => 'introduction-data-science-python',
                    'description'    => 'Start your data science journey. Learn Python basics, data analysis with pandas, visualization with matplotlib, and machine learning fundamentals.',
                    'level'          => 'beginner',
                    'status'         => 'published',
                    'featured'       => false,
                    'duration_hours' => 30,
                    'language'       => 'en',
                    'what_you_learn' => json_encode(['Python programming from scratch', 'Data cleaning and analysis', 'Data visualization', 'Basic machine learning algorithms', 'Real-world data projects']),
                    'requirements'   => json_encode(['No programming experience required', 'Basic math skills']),
                ],
                'modules' => [
                    ['title' => 'Python Programming Basics', 'lessons' => [
                        ['title' => 'Getting Started with Python', 'type' => 'video', 'is_free_preview' => true, 'video_url' => 'https://www.youtube.com/watch?v=ysEN5RaKOlA'],
                        ['title' => 'Data Types and Variables', 'type' => 'video', 'video_url' => 'https://www.youtube.com/watch?v=ysEN5RaKOlA'],
                        ['title' => 'Control Flow: If/Else and Loops', 'type' => 'video', 'video_url' => 'https://www.youtube.com/watch?v=ysEN5RaKOlA'],
                    ]],
                    ['title' => 'Data Analysis with Pandas', 'lessons' => [
                        ['title' => 'Introduction to DataFrames', 'type' => 'video', 'video_url' => 'https://www.youtube.com/watch?v=ysEN5RaKOlA'],
                        ['title' => 'Cleaning Messy Data', 'type' => 'video', 'video_url' => 'https://www.youtube.com/watch?v=ysEN5RaKOlA'],
                        ['title' => 'Data Analysis Quiz', 'type' => 'quiz'],
                    ]],
                ],
            ],
            [
                'instructor' => $aisha,
                'category'   => $designCat,
                'data'       => [
                    'title'          => 'Graphic Design for Beginners',
                    'slug'           => 'graphic-design-for-beginners',
                    'description'    => 'Learn design principles, color theory, typography, and create professional designs using free tools like Canva and GIMP.',
                    'level'          => 'beginner',
                    'status'         => 'published',
                    'featured'       => true,
                    'duration_hours' => 15,
                    'language'       => 'en',
                    'what_you_learn' => json_encode(['Core design principles', 'Color theory and typography', 'Create logos and social media graphics', 'Design for print and digital', 'Build a design portfolio']),
                    'requirements'   => json_encode(['No design experience needed', 'Free Canva account (we will set it up)']),
                ],
                'modules' => [
                    ['title' => 'Design Fundamentals', 'lessons' => [
                        ['title' => 'The 5 Principles of Good Design', 'type' => 'video', 'is_free_preview' => true, 'video_url' => 'https://www.youtube.com/watch?v=ysEN5RaKOlA'],
                        ['title' => 'Understanding Color Theory', 'type' => 'video', 'video_url' => 'https://www.youtube.com/watch?v=ysEN5RaKOlA'],
                        ['title' => 'Typography Basics', 'type' => 'text', 'content' => 'Typography is the art of arranging type to make written language legible, readable, and appealing. The right font choice can make or break your design.'],
                    ]],
                    ['title' => 'Designing with Canva', 'lessons' => [
                        ['title' => 'Getting Started with Canva', 'type' => 'video', 'video_url' => 'https://www.youtube.com/watch?v=ysEN5RaKOlA'],
                        ['title' => 'Creating a Social Media Post', 'type' => 'video', 'video_url' => 'https://www.youtube.com/watch?v=ysEN5RaKOlA'],
                    ]],
                ],
            ],
        ];

        foreach ($courses as $courseData) {
            $d = $courseData['data'];

            $course = Course::create([
                'instructor_id'    => $courseData['instructor']->id,
                'category_id'      => $courseData['category']?->id,
                'level'            => $d['level'],
                'status'           => $d['status'],
                'featured'         => $d['featured'],
                'duration_minutes' => $d['duration_hours'] * 60,
                'slug'             => $d['slug'],
            ]);

            // English translation
            $course->translations()->create([
                'locale'         => 'en',
                'title'          => $d['title'],
                'description'    => $d['description'],
                'requirements'   => $d['requirements'],
                'what_you_learn' => $d['what_you_learn'],
            ]);

            foreach ($courseData['modules'] as $moduleOrder => $moduleData) {
               $module = $course->modules()->create(['order' => $moduleOrder, 'title' => $moduleData['title']]);
                $module->translations()->create([
                    'locale' => 'en',
                    'title'  => $moduleData['title'],
                ]);

                foreach ($moduleData['lessons'] as $lessonOrder => $lessonData) {
                    $lesson = $module->lessons()->create([
                        'type'           => $lessonData['type'],
                        'video_url'      => $lessonData['video_url'] ?? null,
                        'content'        => $lessonData['content'] ?? null,
                        'is_free_preview'=> $lessonData['is_free_preview'] ?? false,
                        'order'          => $lessonOrder,
                        'duration_minutes' => rand(5, 25),
                    ]);
                    $lesson->translations()->create([
                        'locale' => 'en',
                        'title'  => $lessonData['title'],
                    ]);
                }
            }
        }
    }
}
