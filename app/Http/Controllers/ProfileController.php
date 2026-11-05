<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileRequest;
use App\Services\UserService;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function edit(Request $request)
    {
        return view('profile.edit', ['user' => $request->user()]);
    }

    public function update(ProfileRequest $request, UserService $service)
    {
        $service->updateProfile($request->user(), $request->validated());

        return back()->with('success', 'Profile updated.');
    }
}
