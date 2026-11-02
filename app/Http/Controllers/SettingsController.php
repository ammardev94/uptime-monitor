<?php

namespace App\Http\Controllers;

use App\Mail\TestMail;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SettingsController extends Controller
{
    public function edit()
    {
        $values = [];
        foreach (array_keys(Setting::DEFAULTS) as $key) {
            $values[$key] = Setting::get($key);
        }

        return view('settings', ['values' => $values]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'alert_emails' => 'nullable|string|max:1000',
            'timeout_seconds' => 'required|integer|min:5|max:60',
            'concurrency' => 'required|integer|min:1|max:30',
            'max_pages_per_site' => 'required|integer|min:1|max:2000',
            'ssl_warning_days' => 'required|integer|min:1|max:60',
        ]);
        $emails = array_filter(array_map('trim', preg_split('/[\s,;]+/', (string) $data['alert_emails'])));
        $bad = array_filter($emails, fn ($e) => ! filter_var($e, FILTER_VALIDATE_EMAIL));
        if ($bad) {
            return back()->withInput()->withErrors(['alert_emails' => 'Invalid email: '.implode(', ', $bad)]);
        }
        $data['alert_emails'] = implode(', ', $emails);
        $data['treat_403_as_up'] = $request->boolean('treat_403_as_up') ? '1' : '0';

        foreach ($data as $k => $v) {
            Setting::put($k, (string) $v);
        }

        return back()->with('ok', 'Settings saved.');
    }

    public function testEmail()
    {
        $to = array_filter(array_map('trim', explode(',', (string) Setting::get('alert_emails'))));
        if (! $to) {
            return back()->withErrors(['alert_emails' => 'Add at least one alert email first.']);
        }
        try {
            Mail::to($to)->send(new TestMail);
        } catch (Throwable $e) {
            return back()->withErrors(['mail' => 'Sending failed: '.$e->getMessage()]);
        }

        return back()->with('ok', 'Test email sent to '.implode(', ', $to).(config('mail.default') === 'log' ? ' (MAIL_MAILER=log — it was written to storage/logs, not actually sent).' : '.'));
    }
}
