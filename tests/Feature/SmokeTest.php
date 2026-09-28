<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SmokeTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    public function test_home_page_renders(): void
    {
        $this->get('/')->assertOk();
    }

    public function test_course_catalog_renders(): void
    {
        $this->get('/courses')->assertOk();
    }
}
