<?php

namespace App\Support;

use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class TableListing
{
    public static function applySearch(Builder $query, ?string $search, array $columns): Builder
    {
        $search = trim((string) $search);

        if ($search === '' || $columns === []) {
            return $query;
        }

        return $query->where(function (Builder $builder) use ($columns, $search): void {
            foreach ($columns as $column) {
                $builder->orWhere($column, 'like', '%' . $search . '%');
            }
        });
    }

    public static function perPage(Request $request, int $default = 15, int $max = 100): int
    {
        $value = (int) $request->integer('per_page', $default);

        if ($value < 1) {
            return $default;
        }

        return min($value, $max);
    }

    public static function paginate(Builder $query, Request $request, int $default = 15): LengthAwarePaginator
    {
        return $query->paginate(self::perPage($request, $default))->withQueryString();
    }

    public static function cursorPaginate(Builder $query, Request $request, int $default = 50): CursorPaginator
    {
        return $query->cursorPaginate(self::perPage($request, $default))->withQueryString();
    }
}
