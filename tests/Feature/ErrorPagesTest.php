<?php

namespace Tests\Feature;

use Tests\TestCase;

class ErrorPagesTest extends TestCase
{
    public function test_404_page_renders_custom_view(): void
    {
        $response = $this->get('/non-existent-endpoint-xyz');

        $response->assertStatus(404)
            ->assertSee('Error 404')
            ->assertSee('Halaman Tidak Ditemukan');
    }
}

