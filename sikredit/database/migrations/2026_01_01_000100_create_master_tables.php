<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10)->unique();
            $table->string('name');
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('phone', 30)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('credit_products', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name');
            $table->string('category', 50);
            $table->text('description')->nullable();
            $table->decimal('interest_rate', 5, 2); // % per tahun
            $table->string('interest_method', 20)->default('anuitas');
            $table->decimal('min_amount', 15, 2);
            $table->decimal('max_amount', 15, 2);
            $table->unsignedSmallInteger('min_tenor');
            $table->unsignedSmallInteger('max_tenor');
            $table->decimal('provision_fee', 5, 2)->default(1); // % dari plafon
            $table->decimal('admin_fee', 15, 2)->default(0);
            $table->decimal('penalty_rate', 6, 3)->default(0.1); // % per hari dari angsuran tertunggak
            $table->boolean('requires_collateral')->default(true);
            $table->decimal('max_ltv', 5, 2)->default(80); // %
            $table->text('requirements')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('document_types', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->string('name');
            $table->string('description')->nullable();
            $table->timestamps();
        });

        Schema::create('credit_product_document_type', function (Blueprint $table) {
            $table->foreignId('credit_product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('document_type_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_required')->default(true);
            $table->primary(['credit_product_id', 'document_type_id']);
        });

        Schema::create('holidays', function (Blueprint $table) {
            $table->id();
            $table->date('date')->unique();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('settings', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        Schema::create('notification_templates', function (Blueprint $table) {
            $table->id();
            $table->string('event', 50)->unique();
            $table->string('subject');
            $table->text('body');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_templates');
        Schema::dropIfExists('settings');
        Schema::dropIfExists('holidays');
        Schema::dropIfExists('credit_product_document_type');
        Schema::dropIfExists('document_types');
        Schema::dropIfExists('credit_products');
        Schema::dropIfExists('branches');
    }
};
