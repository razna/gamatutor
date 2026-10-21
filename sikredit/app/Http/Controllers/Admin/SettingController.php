<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\NotificationTemplate;
use App\Models\Setting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function edit()
    {
        $values = [];
        foreach (array_keys(Setting::DEFAULTS) as $k) {
            $values[$k] = Setting::get($k);
        }
        $templates = NotificationTemplate::query()->get()->keyBy('event');

        return view('admin.master.settings', compact('values', 'templates'));
    }

    public function update(Request $request)
    {
        $rules = [];
        foreach (array_keys(Setting::DEFAULTS) as $k) {
            $rules[$k] = ['required', 'numeric', 'min:0'];
        }
        $rules['staff_two_factor'] = ['required', 'in:0,1'];
        $data = $request->validate($rules);

        $sum = collect($data)->filter(fn ($v, $k) => str_starts_with($k, 'weight_'))->sum();
        if (abs($sum - 100) > 0.01) {
            return back()->withErrors(['weight_character' => "Total bobot 5C harus 100 (saat ini {$sum})."])->withInput();
        }

        foreach ($data as $k => $v) {
            Setting::put($k, $v);
        }
        AuditLog::record('settings.update', null, 'Memperbarui parameter sistem', $data);

        return back()->with('success', 'Pengaturan disimpan.');
    }

    public function templates(Request $request)
    {
        $data = $request->validate([
            'templates' => ['required', 'array'],
            'templates.*.subject' => ['required', 'string', 'max:200'],
            'templates.*.body' => ['required', 'string', 'max:2000'],
        ]);
        foreach ($data['templates'] as $event => $tpl) {
            if (array_key_exists($event, NotificationTemplate::EVENTS)) {
                NotificationTemplate::query()->updateOrCreate(['event' => $event], $tpl);
            }
        }
        AuditLog::record('settings.templates', null, 'Memperbarui template notifikasi');

        return back()->with('success', 'Template notifikasi disimpan.');
    }
}
