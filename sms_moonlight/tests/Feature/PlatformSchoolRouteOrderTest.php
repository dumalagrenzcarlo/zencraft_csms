<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Http\Request;
use Tests\TestCase;

class PlatformSchoolRouteOrderTest extends TestCase
{
    public function test_create_path_matches_the_static_create_route(): void
    {
        $request = Request::create('/platform/schools/create', 'GET');
        $route = app('router')->getRoutes()->match($request);

        $this->assertSame('platform.schools.create', $route->getName());
    }
}
