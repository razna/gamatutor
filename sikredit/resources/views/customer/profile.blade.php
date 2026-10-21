<x-layouts.app title="Profil Saya" subtitle="Data ini digunakan untuk analisis kelayakan kredit dan disimpan secara terenkripsi.">
    @php $c = $customer; @endphp
    <form method="POST" action="{{ route('customer.profile.update') }}" class="space-y-6">
        @csrf @method('PUT')
        <div class="card">
            <div class="card-header"><div class="card-title">Data Pribadi</div></div>
            <div class="card-body grid gap-4 md:grid-cols-3">
                <x-field label="Nama lengkap" name="name" :value="$user->name" required />
                <x-field label="Nomor HP" name="phone" :value="$user->phone" required />
                <div><label class="form-label">Email</label><input class="form-input bg-slate-50" value="{{ $user->email }}" disabled></div>
                <x-field label="NIK" name="nik" :value="$c->nik" required maxlength="16" inputmode="numeric" />
                <x-field label="Nomor KK" name="kk_number" :value="$c->kk_number" maxlength="16" inputmode="numeric" />
                <x-field label="NPWP" name="npwp" :value="$c->npwp" placeholder="12.345.678.9-012.345" />
                <x-field label="Tempat lahir" name="birth_place" :value="$c->birth_place" required />
                <x-field label="Tanggal lahir" name="birth_date" type="date" :value="$c->birth_date?->toDateString()" required />
                <x-field label="Jenis kelamin" name="gender" :value="$c->gender" :options="['L' => 'Laki-laki', 'P' => 'Perempuan']" required />
                <x-field label="Status perkawinan" name="marital_status" :value="$c->marital_status" :options="['lajang' => 'Belum menikah', 'menikah' => 'Menikah', 'cerai' => 'Cerai']" required />
                <x-field label="Jumlah tanggungan" name="dependents" type="number" min="0" :value="$c->dependents ?? 0" required />
                <x-field label="Pendidikan terakhir" name="education" :value="$c->education" :options="['SMA' => 'SMA/SMK', 'D3' => 'Diploma', 'S1' => 'Sarjana (S1)', 'S2' => 'Magister (S2)', 'S3' => 'Doktor (S3)', 'lainnya' => 'Lainnya']" />
                <x-field label="Nama ibu kandung" name="mother_name" :value="$c->mother_name" required />
                <x-field class="md:col-span-2" label="Alamat sesuai KTP" name="address" type="textarea" :value="$c->address" required />
                <div class="grid gap-4">
                    <x-field label="Kota / Kabupaten" name="city" :value="$c->city" required />
                    <x-field label="Kode pos" name="postal_code" :value="$c->postal_code" maxlength="5" />
                </div>
                <x-field label="Status tempat tinggal" name="home_status" :value="$c->home_status" :options="['milik_sendiri' => 'Milik sendiri', 'keluarga' => 'Milik keluarga', 'sewa' => 'Sewa / Kontrak', 'dinas' => 'Rumah dinas']" required />
            </div>
        </div>

        <div class="card">
            <div class="card-header"><div class="card-title">Pekerjaan / Usaha</div></div>
            <div class="card-body grid gap-4 md:grid-cols-3">
                <x-field label="Jenis pekerjaan" name="occupation_type" :value="$c->occupation_type" :options="\App\Models\Customer::OCCUPATIONS" required />
                <x-field label="Nama perusahaan / usaha" name="employer_name" :value="$c->employer_name" required />
                <x-field label="Jabatan" name="job_title" :value="$c->job_title" />
                <x-field label="Lama bekerja / usaha (tahun)" name="work_years" type="number" min="0" :value="$c->work_years" required />
                <x-field label="Bidang usaha" name="business_field" :value="$c->business_field" />
                <x-field label="Alamat kantor / usaha" name="office_address" :value="$c->office_address" />
            </div>
        </div>

        <div class="card">
            <div class="card-header"><div class="card-title">Keuangan (per bulan)</div></div>
            <div class="card-body grid gap-4 md:grid-cols-4">
                <x-field label="Penghasilan utama (Rp)" name="monthly_income" type="number" min="0" :value="$c->monthly_income ?: null" required />
                <x-field label="Penghasilan lain (Rp)" name="other_income" type="number" min="0" :value="$c->other_income" />
                <x-field label="Pengeluaran rutin (Rp)" name="monthly_expense" type="number" min="0" :value="$c->monthly_expense" required />
                <x-field label="Cicilan berjalan (Rp)" name="existing_installments" type="number" min="0" :value="$c->existing_installments" hint="Total cicilan di bank/lembaga lain." />
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            <div class="card">
                <div class="card-header"><div class="card-title">Data Pasangan</div></div>
                <div class="card-body grid gap-4 md:grid-cols-3">
                    <x-field label="Nama pasangan" name="spouse_name" :value="$c->spouse_name" hint="Wajib bila menikah." />
                    <x-field label="Pekerjaan" name="spouse_occupation" :value="$c->spouse_occupation" />
                    <x-field label="Penghasilan (Rp)" name="spouse_income" type="number" min="0" :value="$c->spouse_income" />
                </div>
            </div>
            <div class="card">
                <div class="card-header"><div class="card-title">Kontak Darurat (tidak serumah)</div></div>
                <div class="card-body grid gap-4 md:grid-cols-3">
                    <x-field label="Nama" name="emergency_name" :value="$c->emergency_name" required />
                    <x-field label="Hubungan" name="emergency_relation" :value="$c->emergency_relation" required />
                    <x-field label="Telepon" name="emergency_phone" :value="$c->emergency_phone" required />
                </div>
            </div>
        </div>
        <div class="flex justify-end"><button class="btn-primary px-8">Simpan Profil</button></div>
    </form>

    <form method="POST" action="{{ route('customer.profile.password') }}" class="card mt-6">
        @csrf @method('PUT')
        <div class="card-header"><div class="card-title">Ganti Kata Sandi</div></div>
        <div class="card-body grid gap-4 md:grid-cols-4">
            <x-field label="Kata sandi saat ini" name="current_password" type="password" required />
            <x-field label="Kata sandi baru" name="password" type="password" required />
            <x-field label="Ulangi kata sandi baru" name="password_confirmation" type="password" required />
            <div class="flex items-end"><button class="btn-secondary w-full">Ganti Kata Sandi</button></div>
        </div>
    </form>
</x-layouts.app>
