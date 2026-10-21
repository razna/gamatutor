<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    public function edit(Request $request)
    {
        return view('customer.profile', ['user' => $request->user(), 'customer' => $request->user()->customer]);
    }

    public function update(Request $request)
    {
        $user = $request->user();
        $customer = $user->customer;

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'regex:/^(\+62|62|0)8[0-9]{8,12}$/'],
            'nik' => ['required', 'digits:16', function ($attr, $value, $fail) use ($customer) {
                $hash = hash_hmac('sha256', $value, config('app.key'));
                if (Customer::query()->where('nik_hash', $hash)->where('id', '!=', $customer->id)->exists()) {
                    $fail('NIK sudah terdaftar pada akun lain.');
                }
            }],
            'kk_number' => ['nullable', 'digits:16'],
            'npwp' => ['nullable', 'regex:/^[0-9.\-]{15,20}$/'],
            'birth_place' => ['required', 'string', 'max:100'],
            'birth_date' => ['required', 'date', 'before:-17 years'],
            'gender' => ['required', Rule::in(['L', 'P'])],
            'marital_status' => ['required', Rule::in(['lajang', 'menikah', 'cerai'])],
            'dependents' => ['required', 'integer', 'min:0', 'max:20'],
            'education' => ['nullable', 'string', 'max:20'],
            'mother_name' => ['required', 'string', 'max:100'],
            'address' => ['required', 'string', 'max:500'],
            'city' => ['required', 'string', 'max:100'],
            'postal_code' => ['nullable', 'digits:5'],
            'home_status' => ['required', 'string', 'max:30'],
            'occupation_type' => ['required', Rule::in(array_keys(Customer::OCCUPATIONS))],
            'employer_name' => ['required', 'string', 'max:150'],
            'job_title' => ['nullable', 'string', 'max:100'],
            'work_years' => ['required', 'integer', 'min:0', 'max:60'],
            'business_field' => ['nullable', 'string', 'max:100'],
            'office_address' => ['nullable', 'string', 'max:500'],
            'monthly_income' => ['required', 'numeric', 'min:0'],
            'other_income' => ['nullable', 'numeric', 'min:0'],
            'monthly_expense' => ['required', 'numeric', 'min:0'],
            'existing_installments' => ['nullable', 'numeric', 'min:0'],
            'spouse_name' => ['nullable', 'required_if:marital_status,menikah', 'string', 'max:100'],
            'spouse_occupation' => ['nullable', 'string', 'max:100'],
            'spouse_income' => ['nullable', 'numeric', 'min:0'],
            'emergency_name' => ['required', 'string', 'max:100'],
            'emergency_relation' => ['required', 'string', 'max:50'],
            'emergency_phone' => ['required', 'regex:/^(\+62|62|0)[0-9]{8,13}$/'],
        ], ['birth_date.before' => 'Usia minimal 17 tahun.']);

        $user->update(['name' => $data['name'], 'phone' => $data['phone']]);
        unset($data['name'], $data['phone']);
        foreach (['other_income', 'existing_installments', 'spouse_income'] as $f) {
            $data[$f] = $data[$f] ?? 0;
        }
        $customer->update($data);
        AuditLog::record('customer.profile', $customer, 'Memperbarui data profil nasabah');

        return back()->with('success', 'Data profil berhasil disimpan.');
    }

    public function password(Request $request)
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
        ]);
        $request->user()->update(['password' => $data['password']]);
        AuditLog::record('auth.password', $request->user(), 'Mengganti kata sandi');

        return back()->with('success', 'Kata sandi berhasil diganti.');
    }
}
