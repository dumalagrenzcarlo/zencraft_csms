<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table): void {
            $table->string('onboarding_status')->default('pending')->index()->after('suspended_at');
            $table->timestamp('onboarding_completed_at')->nullable()->after('onboarding_status');
        });

        Schema::table('subscriptions', function (Blueprint $table): void {
            $table->timestamp('grace_ends_at')->nullable()->after('renews_at');
            $table->timestamp('cancel_at')->nullable()->after('grace_ends_at');
            $table->string('provider')->nullable()->after('status');
            $table->string('provider_subscription_id')->nullable()->unique()->after('provider');
        });

        Schema::create('support_access_grants', function (Blueprint $table): void {
            $table->id();
            $table->string('tenant_id');
            $table->foreignId('support_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('granted_by')->constrained('users')->restrictOnDelete();
            $table->text('reason');
            $table->timestamp('expires_at');
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->index(['support_user_id', 'tenant_id', 'expires_at'], 'support_grant_lookup');
        });

        Schema::create('tenant_backups', function (Blueprint $table): void {
            $table->id();
            $table->string('tenant_id');
            $table->string('status')->default('pending')->index();
            $table->string('disk')->default('local');
            $table->string('path', 500)->nullable();
            $table->string('checksum', 64)->nullable();
            $table->unsignedBigInteger('size_bytes')->default(0);
            $table->unsignedInteger('table_count')->default(0);
            $table->unsignedBigInteger('row_count')->default(0);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->text('failure_message')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->index(['tenant_id', 'completed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_backups');
        Schema::dropIfExists('support_access_grants');

        Schema::table('subscriptions', function (Blueprint $table): void {
            $table->dropUnique(['provider_subscription_id']);
            $table->dropColumn(['grace_ends_at', 'cancel_at', 'provider', 'provider_subscription_id']);
        });

        Schema::table('tenants', function (Blueprint $table): void {
            $table->dropColumn(['onboarding_status', 'onboarding_completed_at']);
        });
    }
};
