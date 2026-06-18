<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     *
     * @return void
     */
    public function test_guest_is_redirected_to_login_from_home()
    {
        $response = $this->get('/');

        $response->assertRedirect(route('login'));
    }
}
