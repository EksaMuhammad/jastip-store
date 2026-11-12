<?php

namespace Tests\Feature;

use Tests\TestCase;

class PwaFeatureTest extends TestCase
{
    public function test_manifest_json_is_accessible_and_valid(): void
    {
        $manifestPath = public_path('manifest.json');
        $this->assertFileExists($manifestPath);

        $json = json_decode(file_get_contents($manifestPath), true);
        $this->assertIsArray($json);
        $this->assertEquals('JastipKuy - Platform Jasa Titip On-Demand', $json['name']);
        $this->assertEquals('JastipKuy', $json['short_name']);
        $this->assertEquals('standalone', $json['display']);
        $this->assertEquals('/login', $json['start_url']);
        $this->assertEquals('#EC0A23', $json['theme_color']);
        $this->assertNotEmpty($json['icons']);
    }

    public function test_service_worker_file_exists(): void
    {
        $swPath = public_path('sw.js');
        $this->assertFileExists($swPath);

        $content = file_get_contents($swPath);
        $this->assertStringContainsString('CACHE_NAME', $content);
        $this->assertStringContainsString('addEventListener(\'install\'', $content);
        $this->assertStringContainsString('addEventListener(\'fetch\'', $content);
    }

    public function test_pwa_icons_exist(): void
    {
        $this->assertFileExists(public_path('images/icons/icon-192x192.png'));
        $this->assertFileExists(public_path('images/icons/icon-512x512.png'));
        $this->assertFileExists(public_path('images/icons/icon-maskable-192x192.png'));
        $this->assertFileExists(public_path('images/icons/icon-maskable-512x512.png'));
        $this->assertFileExists(public_path('images/icons/icon.svg'));
    }

    public function test_landing_page_includes_pwa_meta_and_install_widget(): void
    {
        $response = $this->get('/');
        $response->assertStatus(200);

        $response->assertSee('<link rel="manifest" href="/manifest.json">', false);
        $response->assertSee('name="theme-color" content="#EC0A23"', false);
        $response->assertSee('window.location.replace(\'/login\');', false);
        $response->assertSee('Install Aplikasi');
        $response->assertSee('window.triggerPwaInstall()', false);
    }

    public function test_support_layout_includes_manifest(): void
    {
        $response = $this->get('/login');
        $response->assertStatus(200);
        $response->assertSee('<link rel="manifest" href="/manifest.json">', false);
    }
}

