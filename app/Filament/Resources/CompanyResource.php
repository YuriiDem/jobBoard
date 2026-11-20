<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CompanyResource\Pages;
use App\Jobs\NotifyIndex;
use App\Models\Company;
use App\Observers\JobObserver;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;

class CompanyResource extends Resource
{
    protected static ?string $model = Company::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')->required()->maxLength(255),
                Forms\Components\TextInput::make('slug')->required()->regex('/^[a-z0-9]+(-[a-z0-9]+)*$/D')->unique(ignoreRecord: true),
                Forms\Components\TextInput::make('tagline'),
                Forms\Components\Textarea::make('description'),
                Forms\Components\TextInput::make('website_url')->url(),
                Forms\Components\Select::make('funding_stage')->options(['bootstrapped' => 'bootstrapped', 'pre_seed' => 'pre seed', 'seed' => 'seed', 'series_a' => 'series a', 'series_b' => 'series b', 'series_c_plus' => 'series c plus', 'public' => 'public', 'acquired' => 'acquired']),
                Forms\Components\TagsInput::make('markets'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable(),
                Tables\Columns\TextColumn::make('slug'),
                Tables\Columns\TextColumn::make('funding_stage'),
            ])

            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->modalDescription('Deleting this company also deletes its jobs and their metrics.')
                    ->before(function (Company $record): void {
                        foreach ($record->jobs()->where('status', 'published')->select(['id', 'slug'])->lazyById(250) as $job) {
                            NotifyIndex::forSlug($job->slug);
                        }
                        DB::afterCommit(fn () => JobObserver::forgetSalary());
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageCompanies::route('/'),
        ];
    }
}
