<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Tenant;
use Illuminate\Support\Facades\DB;
use MoonShine\Laravel\Models\MoonshineUserRole;

final class OnboardingReadiness
{
    /** @return array{ready:bool,completed:int,total:int,percent:int,items:list<array{key:string,label:string,complete:bool}>} */
    public function inspect(Tenant $tenant): array
    {
        $items = $tenant->run(function (): array {
            return [
                $this->item('administrator', 'Administrator account created', DB::table('moonshine_users')->where('moonshine_user_role_id', MoonshineUserRole::DEFAULT_ROLE_ID)->exists()),
                $this->item('school_profile', 'School profile configured', filled(DB::table('settings')->where('settingName', 'school_name')->value('settingValue'))),
                $this->item('school_year', 'Active school year configured', DB::table('school_year')->where('active', true)->exists()),
                $this->item('grade_levels', 'At least one grade level configured', DB::table('grade')->exists()),
                $this->item('teachers', 'At least one teacher or instructor created', DB::table('advisers')->whereIn('staff_type', ['teacher', 'instructor'])->exists()),
                $this->item('classes', 'At least one active class configured', DB::table('classes')->where('active', true)->exists()),
                $this->item('students', 'At least one student imported or created', DB::table('students')->exists()),
                $this->item('scanner_token', 'Scanner API credential generated', filled(DB::table('settings')->where('settingName', 'api_authcode')->value('settingValue'))),
            ];
        });
        $completed = collect($items)->where('complete', true)->count();
        $total = count($items);

        return [
            'ready' => $completed === $total,
            'completed' => $completed,
            'total' => $total,
            'percent' => $total === 0 ? 0 : (int) round(($completed / $total) * 100),
            'items' => $items,
        ];
    }

    public function synchronize(Tenant $tenant): array
    {
        $readiness = $this->inspect($tenant);
        $tenant->forceFill([
            'onboarding_status' => $readiness['ready'] ? 'complete' : 'in_progress',
            'onboarding_completed_at' => $readiness['ready']
                ? ($tenant->onboarding_completed_at ?? now())
                : null,
        ])->save();

        return $readiness;
    }

    private function item(string $key, string $label, bool $complete): array
    {
        return compact('key', 'label', 'complete');
    }
}
