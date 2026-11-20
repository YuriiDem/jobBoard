<?php

namespace App\Filament\Resources;

use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

class BoardSlug
{
    public static function field(): TextInput
    {
        return TextInput::make('slug')->required()->maxLength(255)->regex('/^[a-z0-9]+(-[a-z0-9]+)*$/D')->notIn(['apply'])
            ->rules(function (?Model $record): array {
                $unique = Rule::unique('public_slugs', 'slug');
                if ($record?->exists) {
                    $unique->ignore($record->getRawOriginal('slug'), 'slug');
                }

                return [$unique];
            });
    }
}
