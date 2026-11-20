<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LocationResource\Pages;
use App\Models\Location;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;

class LocationResource extends Resource
{
    protected static ?string $model = Location::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')->required()->maxLength(255),
                BoardSlug::field(),
                Forms\Components\Select::make('type')->options(['region' => 'region', 'country' => 'country', 'city' => 'city', 'remote' => 'remote'])->required(),
                Forms\Components\TextInput::make('country_code')->length(2),
                Forms\Components\TextInput::make('country_name'),
                Forms\Components\Select::make('parent_id')->relationship('parent', 'name')->searchable()->rules(fn (?Location $record): array => [function (string $attribute, mixed $value, \Closure $fail) use ($record): void {
                    if ($record && DB::selectOne('WITH RECURSIVE parents AS (SELECT id,parent_id FROM locations WHERE id=? UNION SELECT l.id,l.parent_id FROM locations l JOIN parents p ON p.parent_id=l.id) SELECT id FROM parents WHERE id=?', [$value, $record->id])) {
                        $fail('Choose a parent outside this location’s descendants.');
                    }
                }]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable(),
                Tables\Columns\TextColumn::make('type'),
                Tables\Columns\TextColumn::make('parent.name'),
            ])

            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageLocations::route('/'),
        ];
    }
}
