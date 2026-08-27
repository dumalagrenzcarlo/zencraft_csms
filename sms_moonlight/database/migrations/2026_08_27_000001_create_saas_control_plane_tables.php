<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->unsignedInteger('included_users')->default(0);
            $table->unsignedInteger('max_users')->nullable();
            $table->unsignedInteger('monthly_price_cents')->default(0);
            $table->json('features')->nullable();
            $table->boolean('active')->default(true)->index();
            $table->timestamps();
        });

        Schema::table('tenants', function (Blueprint $table): void {
            $table->string('name')->after('id');
            $table->string('slug')->unique()->after('name');
            $table->string('status')->default('trial')->index()->after('slug');
            $table->string('timezone')->default('Asia/Manila')->after('status');
            $table->foreignId('current_plan_id')->nullable()->after('timezone')->constrained('plans')->nullOnDelete();
            $table->timestamp('trial_ends_at')->nullable()->after('current_plan_id');
            $table->timestamp('provisioned_at')->nullable()->after('trial_ends_at');
            $table->timestamp('suspended_at')->nullable()->after('provisioned_at');
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->string('role')->default('support')->index()->after('password');
            $table->boolean('active')->default(true)->index()->after('role');
            $table->timestamp('last_login_at')->nullable()->after('active');
        });

        Schema::create('subscriptions', function (Blueprint $table): void {
            $table->id();
            $table->string('tenant_id');
            $table->foreignId('plan_id');
            $table->string('status')->default('trial')->index();
            $table->unsignedInteger('billable_users')->default(0);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('renews_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('plan_id')->references('id')->on('plans')->restrictOnDelete();
            $table->index(['tenant_id', 'status']);
        });

        Schema::create('platform_audit_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('tenant_id')->nullable()->index();
            $table->string('event');
            $table->string('ip_address', 45)->nullable();
            $table->json('context')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('tenant_id')->references('id')->on('tenants')->nullOnDelete();
            $table->index(['event', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_audit_logs');
        Schema::dropIfExists('subscriptions');

        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['role', 'active', 'last_login_at']);
        });

        Schema::table('tenants', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('current_plan_id');
            $table->dropColumn([
                'name', 'slug', 'status', 'timezone', 'trial_ends_at',
                'provisioned_at', 'suspended_at',
            ]);
        });

        Schema::dropIfExists('plans');
    }
};
