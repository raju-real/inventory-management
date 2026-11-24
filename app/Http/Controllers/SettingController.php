<?php

namespace App\Http\Controllers;

use App\Http\Requests\MailSettingRequest;
use App\Http\Requests\PointSettingRequest;
use App\Http\Requests\SiteSettingRequest;
use App\Http\Requests\VatSettingRequest;
use App\Services\SettingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class SettingController extends Controller
{
    public function __construct(private SettingService $service)
    {
        $this->middleware('admin');
    }

    public function edit()
    {
        return view('settings.edit');
    }

    public function update(SiteSettingRequest $request)
    {
        $this->service->save($request->validated());

        return redirect()->route('settings.edit')->with('success', 'Settings saved.');
    }

    public function updatePoints(PointSettingRequest $request)
    {
        $this->service->save($request->validated());

        return redirect()->route('settings.edit', ['tab' => 'points'])->with('success', 'Point settings saved.');
    }

    public function updateVat(VatSettingRequest $request)
    {
        $this->service->save($request->validated());

        return redirect()->route('settings.edit', ['tab' => 'vat'])->with('success', 'VAT settings saved.');
    }

    public function updateMail(MailSettingRequest $request)
    {
        $this->service->saveMail($request->validated());

        return redirect()->route('settings.edit', ['tab' => 'mail'])->with('success', 'Mail settings saved.');
    }

    public function testMail(Request $request)
    {
        $data = $request->validate(['test_email' => ['required', 'email:rfc,filter', 'max:255']]);

        try {
            Mail::raw('This is a test email from ' . setting('site_name', config('app.name')) . '. Your mail settings work.', function ($message) use ($data) {
                $message->to($data['test_email'])->subject('Test email');
            });
        } catch (\Throwable $e) {
            report($e);

            return redirect()->route('settings.edit', ['tab' => 'mail'])->with('error', 'Sending failed: ' . $e->getMessage());
        }

        return redirect()->route('settings.edit', ['tab' => 'mail'])->with('success', "Test email sent to {$data['test_email']}.");
    }
}
