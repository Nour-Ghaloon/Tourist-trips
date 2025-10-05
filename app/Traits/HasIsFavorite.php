<?php

namespace App\Traits;

use Illuminate\Support\Facades\Auth;

trait HasIsFavorite
{
    public function scopeWithIsFavorite($query)
    {
        $userId = Auth::id() ?? 0;
        $table = $this->getTable();       // اسم الجدول
        $class = get_class($this);        // اسم الموديل

        return $query->select("{$table}.*")
            ->selectRaw("EXISTS(
                SELECT 1 FROM favorites
                WHERE favorites.favoritable_id = {$table}.id
                AND favorites.favoritable_type = ?
                AND favorites.user_id = ?
            ) as is_favorite", [$class, $userId]);
    }
}
