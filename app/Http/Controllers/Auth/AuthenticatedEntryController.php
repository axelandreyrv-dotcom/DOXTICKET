<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Auth\AuthenticatedEntryDestination;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AuthenticatedEntryController extends Controller
{
    public function __invoke(Request $request, AuthenticatedEntryDestination $destination): RedirectResponse
    {
        return redirect($destination->resolve($request));
    }
}
