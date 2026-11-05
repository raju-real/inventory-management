<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_guests_are_sent_to_the_login_page()
    {
        $this->get('/')->assertRedirect(route('login'));
    }

    public function test_the_login_page_loads()
    {
        $this->get(route('login'))->assertOk()->assertSee('Log in');
    }
}
