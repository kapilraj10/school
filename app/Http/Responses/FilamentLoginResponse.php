<?php

namespace App\Http\Responses;

use Filament\Facades\Filament;
use Filament\Http\Responses\Auth\Contracts\LoginResponse as LoginResponseContract;
use Illuminate\Http\RedirectResponse;

class FilamentLoginResponse implements LoginResponseContract
{
    public function toResponse($request): RedirectResponse
    {
        return new RedirectResponse(Filament::getUrl());
    }
}
