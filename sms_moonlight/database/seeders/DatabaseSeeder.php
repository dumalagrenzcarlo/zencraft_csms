<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Plan;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->plans() as $plan) {
            Plan::query()->updateOrCreate(['slug' => $plan['slug']], $plan);
        }

        $password = (string) env('PLATFORM_OWNER_PASSWORD');

        if ($password !== '') {
            User::query()->updateOrCreate(
                ['email' => strtolower((string) env('PLATFORM_OWNER_EMAIL', 'owner@example.test'))],
                [
                    'name' => (string) env('PLATFORM_OWNER_NAME', 'Platform Owner'),
                    'password' => Hash::make($password),
                    'role' => 'owner',
                    'active' => true,
                ]
            );
        }

        $supportPassword = (string) env('PLATFORM_SUPPORT_PASSWORD');

        if ($supportPassword !== '') {
            User::query()->updateOrCreate(
                ['email' => strtolower((string) env('PLATFORM_SUPPORT_EMAIL', 'support@example.test'))],
                [
                    'name' => (string) env('PLATFORM_SUPPORT_NAME', 'Support Operator'),
                    'password' => Hash::make($supportPassword),
                    'role' => 'support',
                    'active' => true,
                ]
            );
        }
    }

    /** @return list<array<string, mixed>> */
    private function plans(): array
    {
        return [
            [
                'name' => 'Free',
                'slug' => 'free',
                'included_users' => 111,
                'max_users' => 111,
                'monthly_price_cents' => 0,
                'features' => ['core_school_management', 'attendance'],
                'active' => true,
            ],
            [
                'name' => 'Starter',
                'slug' => 'starter',
                'included_users' => 500,
                'max_users' => 500,
                'monthly_price_cents' => 500000,
                'features' => ['core_school_management', 'attendance', 'reports', 'custom_domain'],
                'active' => true,
            ],
            [
                'name' => 'Growth',
                'slug' => 'growth',
                'included_users' => 500,
                'max_users' => 1000,
                'monthly_price_cents' => 500000,
                'features' => ['core_school_management', 'attendance', 'reports', 'custom_domain', 'priority_support'],
                'active' => true,
            ],
            [
                'name' => 'Scale',
                'slug' => 'scale',
                'included_users' => 1000,
                'max_users' => 2000,
                'monthly_price_cents' => 900000,
                'features' => ['core_school_management', 'attendance', 'reports', 'custom_domain', 'priority_support', 'guided_migration'],
                'active' => true,
            ],
        ];
    }
}
