<?php

namespace Tests\Feature;

use Tests\TestCase;

class PublicLegalPagesTest extends TestCase
{
    public function test_terms_page_is_publicly_accessible(): void
    {
        $this->get(route('terms'))
            ->assertOk()
            ->assertSee('Terms &amp; Conditions', false)
            ->assertSee(route('privacy'));
    }

    public function test_privacy_page_is_publicly_accessible(): void
    {
        $this->get(route('privacy'))
            ->assertOk()
            ->assertSee('Privacy Policy')
            ->assertSee('Nigeria Data Protection Act 2023');
    }
}
