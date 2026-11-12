<?php

namespace Tests\Feature;

use Tests\TestCase;

class LandingPageScrollTest extends TestCase
{
    public function test_landing_page_renders_successfully(): void
    {
        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertSee('JastipKuy');
    }

    public function test_landing_page_does_not_contain_scroll_smooth(): void
    {
        $response = $this->get('/');
        $content = $response->getContent();

        // scroll-smooth on html element causes scroll locks on WebKit/Blink
        $this->assertStringNotContainsString('class="scroll-smooth"', $content);
        $this->assertStringNotContainsString('scroll-smooth', $content);
    }

    public function test_landing_page_hero_mockup_does_not_trap_mouse_wheel(): void
    {
        $response = $this->get('/');
        $content = $response->getContent();

        // Phone mockup must be non-capturing so cursor hover doesn't hijack scroll on laptops
        $this->assertStringContainsString('pointer-events-none', $content);
        $this->assertStringNotContainsString('overflow-y-auto px-5 py-5 pb-24', $content);
    }

    public function test_landing_page_cta_links_are_valid(): void
    {
        $response = $this->get('/');
        $content = $response->getContent();

        // CTAs should direct to login or dashboard, not broken #calculator
        $this->assertStringNotContainsString('href="#calculator"', $content);
    }
}

