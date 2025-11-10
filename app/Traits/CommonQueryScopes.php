<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

trait CommonQueryScopes
{
    /**
     * Scope a query to filter by date
     *
     * @param Builder $query
     * @param string|null $date
     * @param string $column
     * @return Builder
     */
    public function scopeFilterByDate(Builder $query, ?string $date, string $column = 'date'): Builder
    {
        if (empty($date)) {
            return $query;
        }

        try {
            $parsedDate = Carbon::parse($date);
            return $query->whereDate($column, $parsedDate->format('Y-m-d'));
        } catch (\Exception $e) {
            // If date parsing fails, return query unchanged
            return $query;
        }
    }

    /**
     * Scope a query to filter by date range
     *
     * @param Builder $query
     * @param string|null $startDate
     * @param string|null $endDate
     * @param string $column
     * @return Builder
     */
    public function scopeFilterByDateRange(Builder $query, ?string $startDate, ?string $endDate, string $column = 'date'): Builder
    {
        if (!empty($startDate)) {
            try {
                $start = Carbon::parse($startDate);
                $query->whereDate($column, '>=', $start->format('Y-m-d'));
            } catch (\Exception $e) {
                // Continue if date parsing fails
            }
        }

        if (!empty($endDate)) {
            try {
                $end = Carbon::parse($endDate);
                $query->whereDate($column, '<=', $end->format('Y-m-d'));
            } catch (\Exception $e) {
                // Continue if date parsing fails
            }
        }

        return $query;
    }

    /**
     * Scope a query to search by title
     *
     * @param Builder $query
     * @param string|null $search
     * @param string $column
     * @return Builder
     */
    public function scopeSearchByTitle(Builder $query, ?string $search, string $column = 'title'): Builder
    {
        if (empty($search)) {
            return $query;
        }

        return $query->where($column, 'LIKE', '%' . $search . '%');
    }

    /**
     * Scope a query to search by multiple columns
     *
     * @param Builder $query
     * @param string|null $search
     * @param array $columns
     * @return Builder
     */
    public function scopeSearchByColumns(Builder $query, ?string $search, array $columns = ['title', 'description']): Builder
    {
        if (empty($search)) {
            return $query;
        }

        return $query->where(function ($q) use ($search, $columns) {
            foreach ($columns as $column) {
                $q->orWhere($column, 'LIKE', '%' . $search . '%');
            }
        });
    }

    /**
     * Scope a query to filter upcoming records by date
     *
     * @param Builder $query
     * @param string $column
     * @return Builder
     */
    public function scopeUpcoming(Builder $query, string $column = 'date'): Builder
    {
        return $query->where($column, '>=', now());
    }

    /**
     * Scope a query to filter past records by date
     *
     * @param Builder $query
     * @param string $column
     * @return Builder
     */
    public function scopePast(Builder $query, string $column = 'date'): Builder
    {
        return $query->where($column, '<', now());
    }

    /**
     * Scope a query to filter by location
     *
     * @param Builder $query
     * @param string|null $location
     * @param string $column
     * @return Builder
     */
    public function scopeFilterByLocation(Builder $query, ?string $location, string $column = 'location'): Builder
    {
        if (empty($location)) {
            return $query;
        }

        return $query->where($column, 'LIKE', '%' . $location . '%');
    }
}