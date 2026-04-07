<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreContactSubmissionRequest;
use App\Models\ContactSetting;
use App\Models\ContactSubmission;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Throwable;

class ContactSubmissionController extends Controller
{
    public function create(): View
    {
        return view('contact', [
            'mapEmbedUrl' => ContactSetting::mapEmbedUrl(),
        ]);
    }

    public function store(StoreContactSubmissionRequest $request): RedirectResponse
    {
        try {
            ContactSubmission::query()->create($request->validated());
        } catch (Throwable) {
            return redirect()
                ->route('contact')
                ->withInput()
                ->withErrors([
                    'message' => 'Your message could not be sent. Please try again.',
                ]);
        }

        return redirect()
            ->route('contact')
            ->with('contact_success', 'Your message has been sent successfully! We will get back to you soon.');
    }
}
