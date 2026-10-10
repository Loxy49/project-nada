<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    // Halaman "/" mengalihkan tamu ke login (dan user yang sudah login ke dashboard).
    public function test_halaman_awal_mengalihkan_tamu_ke_login(): void
    {
        $this->get('/')->assertRedirect(route('login'));
    }
}
