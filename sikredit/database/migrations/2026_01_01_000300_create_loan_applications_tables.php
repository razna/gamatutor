<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loan_applications', function (Blueprint $table) {
            $table->id();
            $table->string('number', 30)->unique();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('credit_product_id')->constrained();
            $table->foreignId('branch_id')->constrained();
            $table->decimal('amount', 15, 2);
            $table->unsignedSmallInteger('tenor');
            $table->string('purpose');
            $table->text('purpose_detail')->nullable();
            $table->decimal('interest_rate', 5, 2);
            $table->string('interest_method', 20);
            $table->string('status', 20)->default('draft')->index();
            $table->timestamp('status_changed_at')->nullable();
            $table->timestamp('sla_due_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            // Petugas
            $table->foreignId('cs_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('analyst_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approver_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('legal_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('disbursed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('required_approver_role', 30)->nullable();
            // Keputusan
            $table->decimal('approved_amount', 15, 2)->nullable();
            $table->unsignedSmallInteger('approved_tenor')->nullable();
            $table->decimal('approved_rate', 5, 2)->nullable();
            $table->text('approval_conditions')->nullable();
            $table->text('decision_notes')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->string('sp3k_number', 40)->nullable();
            // Legal & akad
            $table->string('contract_number', 40)->nullable();
            $table->dateTime('akad_scheduled_at')->nullable();
            $table->date('akad_date')->nullable();
            $table->string('notary_name')->nullable();
            $table->text('binding_notes')->nullable();
            // Pencairan
            $table->text('disbursement_account')->nullable();
            $table->string('disbursement_bank')->nullable();
            $table->date('disbursed_at')->nullable();
            $table->decimal('provision_amount', 15, 2)->nullable();
            $table->decimal('admin_amount', 15, 2)->nullable();
            $table->decimal('net_disbursed', 15, 2)->nullable();
            $table->date('first_due_date')->nullable();
            // Monitoring
            $table->unsignedTinyInteger('collectibility')->nullable();
            $table->unsignedInteger('days_past_due')->default(0);
            $table->decimal('outstanding_principal', 15, 2)->nullable();
            $table->unsignedTinyInteger('restructure_count')->default(0);
            $table->date('paid_off_at')->nullable();
            $table->timestamps();
            $table->index(['branch_id', 'status']);
        });

        Schema::create('application_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_application_id')->constrained()->cascadeOnDelete();
            $table->foreignId('document_type_id')->constrained();
            $table->string('path');
            $table->string('original_name');
            $table->string('mime', 100)->nullable();
            $table->unsignedInteger('size')->default(0);
            $table->string('status', 20)->default('pending'); // pending, valid, invalid
            $table->string('note')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
        });

        Schema::create('collaterals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_application_id')->constrained()->cascadeOnDelete();
            $table->string('type', 30); // tanah_bangunan, kendaraan, deposito, mesin, lainnya
            $table->string('description');
            $table->string('ownership_number')->nullable(); // No. SHM / BPKB
            $table->string('owner_name')->nullable();
            $table->text('location')->nullable();
            $table->decimal('estimated_value', 15, 2)->default(0);
            $table->decimal('market_value', 15, 2)->nullable();
            $table->decimal('liquidation_value', 15, 2)->nullable();
            $table->foreignId('appraised_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('appraised_at')->nullable();
            $table->string('binding_type', 30)->nullable(); // HT, Fidusia, Gadai
            $table->string('binding_number')->nullable();
            $table->timestamps();
        });

        Schema::create('surveys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_application_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('surveyor_id')->constrained('users');
            $table->date('visit_date');
            $table->string('met_with')->nullable();
            $table->boolean('address_verified')->default(false);
            $table->boolean('income_verified')->default(false);
            $table->boolean('business_verified')->default(false);
            $table->string('residence_condition', 30)->nullable();
            $table->text('neighborhood_info')->nullable();
            $table->text('notes')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->json('photos')->nullable();
            $table->timestamps();
        });

        Schema::create('credit_analyses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_application_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('analyst_id')->constrained('users');
            foreach (['character', 'capacity', 'capital', 'collateral', 'condition'] as $c) {
                $table->unsignedTinyInteger($c.'_score');
                $table->text($c.'_notes')->nullable();
            }
            $table->unsignedTinyInteger('slik_collectibility')->default(1);
            $table->text('slik_notes')->nullable();
            $table->decimal('net_income', 15, 2)->default(0);
            $table->decimal('new_installment', 15, 2)->default(0);
            $table->decimal('dsr', 6, 2)->default(0);
            $table->decimal('ltv', 6, 2)->nullable();
            $table->decimal('total_score', 5, 2);
            $table->string('grade', 1);
            $table->string('recommendation', 10); // approve, reject
            $table->decimal('recommended_amount', 15, 2)->nullable();
            $table->unsignedSmallInteger('recommended_tenor')->nullable();
            $table->decimal('recommended_rate', 5, 2)->nullable();
            $table->text('summary')->nullable();
            $table->timestamps();
        });

        Schema::create('application_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_application_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('from_status', 20)->nullable();
            $table->string('to_status', 20);
            $table->text('notes')->nullable();
            $table->boolean('sla_breached')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('application_logs');
        Schema::dropIfExists('credit_analyses');
        Schema::dropIfExists('surveys');
        Schema::dropIfExists('collaterals');
        Schema::dropIfExists('application_documents');
        Schema::dropIfExists('loan_applications');
    }
};
