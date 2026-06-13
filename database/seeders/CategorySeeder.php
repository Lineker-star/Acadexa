<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Technology & Programming', 'icon' => '💻', 'slug' => 'technology-programming', 'children' => [
                ['name' => 'Web Development', 'icon' => '🌐', 'slug' => 'web-development'],
                ['name' => 'Mobile Apps', 'icon' => '📱', 'slug' => 'mobile-apps'],
                ['name' => 'Data Science', 'icon' => '📊', 'slug' => 'data-science'],
                ['name' => 'Cybersecurity', 'icon' => '🔐', 'slug' => 'cybersecurity'],
            ]],
            ['name' => 'Business & Entrepreneurship', 'icon' => '💼', 'slug' => 'business-entrepreneurship', 'children' => [
                ['name' => 'Startup & Innovation', 'icon' => '🚀', 'slug' => 'startup-innovation'],
                ['name' => 'Finance & Accounting', 'icon' => '💰', 'slug' => 'finance-accounting'],
                ['name' => 'Project Management', 'icon' => '📋', 'slug' => 'project-management'],
            ]],
            ['name' => 'Arts & Design', 'icon' => '🎨', 'slug' => 'arts-design', 'children' => [
                ['name' => 'Graphic Design', 'icon' => '✏️', 'slug' => 'graphic-design'],
                ['name' => 'Photography', 'icon' => '📸', 'slug' => 'photography'],
            ]],
            ['name' => 'Languages', 'icon' => '🗣️', 'slug' => 'languages', 'children' => [
                ['name' => 'English', 'icon' => '🇬🇧', 'slug' => 'english'],
                ['name' => 'French', 'icon' => '🇫🇷', 'slug' => 'french'],
            ]],
            ['name' => 'Health & Wellness', 'icon' => '🏥', 'slug' => 'health-wellness'],
            ['name' => 'Agriculture & Environment', 'icon' => '🌱', 'slug' => 'agriculture-environment'],
            ['name' => 'Education & Teaching', 'icon' => '📚', 'slug' => 'education-teaching'],
            ['name' => 'Personal Development', 'icon' => '🌟', 'slug' => 'personal-development'],
        ];

        foreach ($categories as $data) {
            $children = $data['children'] ?? [];
            unset($data['children']);

            $parent = Category::create(array_merge($data, ['is_active' => true, 'order' => 0]));

            // English translation (canonical)
            $parent->translations()->create([
                'locale' => 'en',
                'name' => $data['name'],
            ]);

            foreach ($children as $i => $childData) {
                $child = Category::create(array_merge($childData, [
                    'parent_id' => $parent->id,
                    'is_active' => true,
                    'order' => $i,
                ]));
                $child->translations()->create([
                    'locale' => 'en',
                    'name' => $childData['name'],
                ]);
            }
        }
    }
}
