<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('status')->default('active');
            $t->timestamps();
        });
        Schema::table('users', function (Blueprint $t) {
            $t->foreignId('tenant_id')->nullable()->constrained()->restrictOnDelete();
            $t->string('role')->default('admin');
        });
        Schema::create('branches', function (Blueprint $t) {
            $t->id();
            $t->foreignId('tenant_id')->constrained()->restrictOnDelete();
            $t->string('name');
            $t->string('timezone')->default('America/Costa_Rica');
            $t->timestamps();
            $t->unique(['tenant_id', 'id']);
            $t->unique(['tenant_id', 'name']);
        });
        Schema::create('employees', function (Blueprint $t) {
            $t->id();
            $t->foreignId('tenant_id')->constrained()->restrictOnDelete();
            $t->unsignedBigInteger('branch_id');
            $t->string('internal_code', 40);
            $t->string('name', 120);
            $t->string('job', 120)->nullable();
            $t->boolean('active')->default(true);
            $t->timestamps();
            $t->unique(['tenant_id', 'internal_code']);
            $t->unique(['tenant_id', 'id']);
            $t->foreign(['tenant_id', 'branch_id'])->references(['tenant_id', 'id'])->on('branches')->restrictOnDelete();
        });
        Schema::create('device_enrollments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('tenant_id')->constrained()->restrictOnDelete();
            $t->unsignedBigInteger('branch_id');
            $t->string('code_hash', 64)->unique();
            $t->timestamp('expires_at');
            $t->timestamp('consumed_at')->nullable();
            $t->timestamps();
            $t->foreign(['tenant_id', 'branch_id'])->references(['tenant_id', 'id'])->on('branches')->restrictOnDelete();
        });
        Schema::create('devices', function (Blueprint $t) {
            $t->id();
            $t->foreignId('tenant_id')->constrained()->restrictOnDelete();
            $t->unsignedBigInteger('branch_id');
            $t->string('name', 100);
            $t->string('token_hash', 64)->unique();
            $t->timestamp('revoked_at')->nullable();
            $t->timestamps();
            $t->unique(['tenant_id', 'id']);
            $t->foreign(['tenant_id', 'branch_id'])->references(['tenant_id', 'id'])->on('branches')->restrictOnDelete();
        });
        Schema::create('attendance_events', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->foreignId('tenant_id')->constrained()->restrictOnDelete();
            $t->unsignedBigInteger('branch_id');
            $t->unsignedBigInteger('device_id');
            $t->unsignedBigInteger('employee_id');
            $t->unsignedBigInteger('sequence');
            $t->string('action', 20);
            $t->string('local_occurred_at', 40);
            $t->dateTime('occurred_at');
            $t->dateTime('received_at');
            $t->string('payload_hash', 64);
            $t->unique(['device_id', 'sequence']);
            $t->index(['tenant_id', 'employee_id', 'occurred_at']);
            $t->foreign(['tenant_id', 'branch_id'])->references(['tenant_id', 'id'])->on('branches')->restrictOnDelete();
            $t->foreign(['tenant_id', 'device_id'])->references(['tenant_id', 'id'])->on('devices')->restrictOnDelete();
            $t->foreign(['tenant_id', 'employee_id'])->references(['tenant_id', 'id'])->on('employees')->restrictOnDelete();
        });
        Schema::create('audit_logs', function (Blueprint $t) {
            $t->id();
            $t->foreignId('tenant_id')->nullable()->constrained()->restrictOnDelete();
            $t->foreignId('user_id')->nullable()->constrained()->restrictOnDelete();
            $t->string('action', 80);
            $t->string('subject_id', 80)->nullable();
            $t->timestamp('created_at');
        });
        Schema::create('inquiries', function (Blueprint $t) {
            $t->id();
            $t->string('name', 100);
            $t->string('email', 200);
            $t->string('company', 120);
            $t->text('message');
            $t->timestamp('consented_at');
            $t->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['inquiries', 'audit_logs', 'attendance_events', 'devices', 'device_enrollments', 'employees', 'branches'] as $table) {
            Schema::dropIfExists($table);
        }
        Schema::table('users', function (Blueprint $t) {
            $t->dropConstrainedForeignId('tenant_id');
            $t->dropColumn('role');
        });
        Schema::dropIfExists('tenants');
    }
};
