<?php

namespace Tests\Feature;

use Tests\TestCase;

class AdminLandingTest extends TestCase
{
    /** @test */
    public function admin_root_redirects_to_trial_balance(): void
    {
        $response = $this->get('/admin');

        $response->assertRedirect('/admin/reports/trial-balance');
    }
}
