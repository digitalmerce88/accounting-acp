<?php

namespace Tests\Feature;

use Tests\TestCase;

class AdminLandingTest extends TestCase
{
    /** @test */
    public function admin_root_requires_auth_and_redirects_to_login(): void
    {
        $response = $this->get('/admin');

        // Without login, admin area should redirect to /login
        $response->assertRedirect('/login');
    }
}
