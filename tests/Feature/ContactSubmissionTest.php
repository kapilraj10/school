<?php

namespace Tests\Feature;

use App\Models\ContactSubmission;
use Tests\TestCase;

class ContactSubmissionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        ContactSubmission::query()->delete();
    }

    public function test_contact_form_submission_is_stored(): void
    {
        $response = $this->post('/contact', [
            'name' => 'Kapil Raj',
            'email' => 'kapil@example.com',
            'subject' => 'Admission Query',
            'message' => 'Please share admission details for class 5.',
        ]);

        $response
            ->assertRedirect('/contact')
            ->assertSessionHas('contact_success');

        $this->assertDatabaseHas(ContactSubmission::class, [
            'name' => 'Kapil Raj',
            'email' => 'kapil@example.com',
            'subject' => 'Admission Query',
        ]);
    }

    public function test_contact_form_requires_all_fields(): void
    {
        $response = $this->from('/contact')->post('/contact', [
            'name' => '',
            'email' => 'not-valid-email',
            'subject' => '',
            'message' => '',
        ]);

        $response
            ->assertRedirect('/contact')
            ->assertSessionHasErrors([
                'name',
                'email',
                'subject',
                'message',
            ]);
    }
}
