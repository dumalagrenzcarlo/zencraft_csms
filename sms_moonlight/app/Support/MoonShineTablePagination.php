<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use MoonShine\Contracts\Core\Paginator\PaginatorContract;
use MoonShine\Crud\TypeCasts\PaginatorCaster;

final class MoonShineTablePagination
{
    /**
     * @template TItem
     *
     * @param  Collection<int, TItem>  $items
     * @return array{0: Collection<int, TItem>, 1: PaginatorContract<TItem>}
     */
    public static function make(
        Collection $items,
        string $pageName = 'page',
        int $perPage = 15,
    ): array {
        $page = max(1, (int) request()->query($pageName, 1));
        $pageItems = $items->forPage($page, $perPage)->values();
        $paginator = new LengthAwarePaginator(
            $pageItems,
            $items->count(),
            $perPage,
            $page,
            [
                'path' => url()->current(),
                'pageName' => $pageName,
                'query' => request()->except($pageName),
            ],
        );

        $moonShinePaginator = (new PaginatorCaster(
            $paginator->toArray(),
            $paginator->items(),
            $pageName,
        ))->cast();

        return [$pageItems, $moonShinePaginator];
    }
}
