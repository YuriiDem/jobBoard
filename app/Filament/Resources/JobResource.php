<?php

namespace App\Filament\Resources;

use App\Filament\Resources\JobResource\Pages;
use App\Models\Job;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class JobResource extends Resource
{
    protected static ?string $model = Job::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('title')->required()->maxLength(255),
                BoardSlug::field(),
                Forms\Components\Select::make('company_id')->relationship('company', 'name')->searchable()->required(),
                Forms\Components\Select::make('department_id')->relationship('department', 'name')->searchable()->live()->afterStateUpdated(fn (Forms\Set $set) => $set('role_id', null)),
                Forms\Components\Select::make('role_id')->relationship('role', 'name', fn (Builder $query, Forms\Get $get) => $query->where('department_id', $get('department_id')))->searchable(),
                Forms\Components\Select::make('primary_location_id')->relationship('primaryLocation', 'name')->searchable(),
                Forms\Components\Select::make('locations')->multiple()->relationship('locations', 'name')->searchable(),
                Forms\Components\Textarea::make('description_plain'),
                Forms\Components\TextInput::make('apply_url')->required()->url()->rules(['url:http,https']),
                Forms\Components\Select::make('employment_type')->options(array_flip(config('jobboard.employment'))),
                Forms\Components\Select::make('workplace_type')->options(['remote' => 'remote', 'hybrid' => 'hybrid', 'onsite' => 'onsite']),
                Forms\Components\TagsInput::make('tags')->default([]),
                Forms\Components\TextInput::make('salary_min')->integer()->minValue(0),
                Forms\Components\TextInput::make('salary_max')->integer()->minValue(0),
                Forms\Components\TextInput::make('salary_currency')->length(3)->default(config('jobboard.default_currency')),
                Forms\Components\Select::make('salary_period')->options(['year' => 'year', 'month' => 'month', 'hour' => 'hour']),
                Forms\Components\Select::make('status')->options(['draft' => 'draft', 'pending_payment' => 'pending payment', 'published' => 'published', 'expired' => 'expired'])->default('draft')->required(),
                Forms\Components\DateTimePicker::make('published_at'),
                Forms\Components\DateTimePicker::make('expires_at')->after('published_at'),
                Forms\Components\Toggle::make('is_featured'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')->searchable(),
                Tables\Columns\TextColumn::make('company.name'),
                Tables\Columns\TextColumn::make('department.name'),
                Tables\Columns\TextColumn::make('status'),
                Tables\Columns\TextColumn::make('published_at'),
            ])
            ->filters([Tables\Filters\SelectFilter::make('status')->options(['draft' => 'Draft', 'published' => 'Published', 'expired' => 'Expired', 'pending_payment' => 'Pending payment'])])
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
            'index' => Pages\ManageJobs::route('/'),
        ];
    }
}
