<?php

namespace App\Http\Controllers;

use App\Support\Mobile\MobilePasswordResetUrl;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MobilePasswordResetController extends Controller
{
    public function show(Request $request): View
    {
        $validated = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
        ]);

        return view('mobile.reset-password', [
            'deepLink' => MobilePasswordResetUrl::deepLink(
                $validated['token'],
                $validated['email'],
            ),
        ]);
    }
}
