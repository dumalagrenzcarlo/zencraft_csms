<?php

declare(strict_types=1);

namespace App\MoonShine\Pages;

use App\MoonShine\Layouts\AdminLoginLayout;
use MoonShine\Core\Attributes\Layout;
use MoonShine\Laravel\Pages\LoginPage;
use MoonShine\MenuManager\Attributes\SkipMenu;

#[SkipMenu]
#[Layout(AdminLoginLayout::class)]
final class AdminLoginPage extends LoginPage
{
}
