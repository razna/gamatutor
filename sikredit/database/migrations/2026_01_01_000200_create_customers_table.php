<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            // Data sensitif disimpan terenkripsi (cast "encrypted"), sehingga bertipe text.
            $table->text('nik')->nullable();
            $table->string('nik_hash', 64)->nullable()->unique(); // untuk cek duplikasi tanpa membuka enkripsi
            $table->text('kk_number')->nullable();
            $table->text('npwp')->nullable();
            $table->string('birth_place')->nullable();
            $table->date('birth_date')->nullable();
            $table->string('gender', 1)->nullable();
            $table->string('marital_status', 20)->nullable();
            $table->unsignedTinyInteger('dependents')->default(0);
            $table->string('education', 20)->nullable();
            $table->string('mother_name')->nullable();
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('postal_code', 10)->nullable();
            $table->string('home_status', 30)->nullable();
            // Pekerjaan / usaha
            $table->string('occupation_type', 30)->nullable(); // karyawan, wiraswasta, profesional, pns
            $table->string('employer_name')->nullable();
            $table->string('job_title')->nullable();
            $table->unsignedSmallInteger('work_years')->nullable();
            $table->string('business_field')->nullable();
            $table->text('office_address')->nullable();
            // Keuangan
            $table->decimal('monthly_income', 15, 2)->default(0);
            $table->decimal('other_income', 15, 2)->default(0);
            $table->decimal('monthly_expense', 15, 2)->default(0);
            $table->decimal('existing_installments', 15, 2)->default(0);
            // Pasangan
            $table->string('spouse_name')->nullable();
            $table->string('spouse_occupation')->nullable();
            $table->decimal('spouse_income', 15, 2)->default(0);
            // Kontak darurat
            $table->string('emergency_name')->nullable();
            $table->string('emergency_relation')->nullable();
            $table->string('emergency_phone', 20)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
