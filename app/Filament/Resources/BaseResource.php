<?php

namespace App\Filament\Resources;

use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Model;

abstract class BaseResource extends Resource
{
    protected static ?string $permission = null;

    public static function shouldRegisterNavigation(): bool
    {
        // Si definiste un $permission manual, úsalo.
        if (static::$permission) {
            return auth()->user()?->can(static::$permission);
        }

        // Si es null, usa el comportamiento nativo de Filament (Policies)
        return parent::shouldRegisterNavigation();
    }

    public static function canViewAny(): bool
    {
        if (static::$permission) {
            return auth()->user()->can(static::$permission);
        }

        return parent::canViewAny();
    }

    public static function canCreate(): bool
    {
        if (static::$permission) {
            return auth()->user()->can(static::$permission);
        }

        return parent::canCreate();
    }

    public static function canEdit(Model $record): bool
    {
        if (static::$permission) {
            return auth()->user()->can(static::$permission);
        }

        return parent::canEdit($record);
    }

    public static function canDelete(Model $record): bool
    {
        if (static::$permission) {
            return auth()->user()->can(static::$permission);
        }

        return parent::canDelete($record);
    }
}