<?php

namespace App\Filament\Pages;

use App\Models\HomepageContent;
use App\Models\Job;
use App\Services\Homepage;
use App\Services\JobFilterOptions;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Pboivin\FilamentPeek\Pages\Actions\PreviewAction;
use Pboivin\FilamentPeek\Pages\Concerns\HasPreviewModal;

class HomepageEditor extends Page
{
    use HasPreviewModal;

    protected static ?string $navigationIcon = 'heroicon-o-home';
    protected static ?string $navigationGroup = 'CMS';
    protected static ?string $title = 'Homepage';
    protected static ?string $slug = 'homepage';
    protected static string $view = 'filament.pages.homepage-editor';

    public array $data = [];

    public function mount(): void
    {
        $this->form->fill(HomepageContent::published());
    }

    public function form(Form $form): Form
    {
        return $form->statePath('data')->schema([
            Section::make('Search by your stack')->columns(2)->schema([
                Toggle::make('stack_enabled')->label('Show section')->columnSpanFull(),
                self::text('stack_title', 'Heading'), self::text('stack_link', 'Link label'),
                self::description('stack_description'), self::limit('stack_limit', 'Technology limit'),
                self::choices('technologies', 'Technologies', 'tag'),
            ]),
            Section::make('Latest IT jobs')->columns(2)->schema([
                Toggle::make('latest_enabled')->label('Show section')->columnSpanFull(),
                self::text('latest_title', 'Heading'), self::text('latest_link', 'Link label'),
                self::description('latest_description'), self::limit('latest_limit', 'Vacancy limit'),
                Select::make('jobs')->label('Vacancies')->multiple()->searchable()->columnSpanFull()
                    ->helperText('Leave empty to show the latest active vacancies. Selected vacancies are also sorted by publication date.')
                    ->getSearchResultsUsing(fn (string $search) => Job::active()->where('title', 'ilike', '%'.$search.'%')
                        ->orderByDesc('published_at')->limit(50)->pluck('title', 'id')->all())
                    ->getOptionLabelsUsing(fn (array $values) => Job::whereIntegerInRaw('id', $values)->pluck('title', 'id')->all()),
            ]),
            Section::make('IT jobs by profession and location')->columns(2)->schema([
                Toggle::make('directory_enabled')->label('Show section')->columnSpanFull(),
                self::text('directory_title', 'Heading')->columnSpanFull(),
                self::text('roles_title', 'Professions heading'), self::limit('roles_limit', 'Profession limit'),
                self::choices('roles', 'Professions', 'role'),
                self::text('locations_title', 'Locations heading'), self::limit('locations_limit', 'Location limit'),
                self::choices('locations', 'Locations', 'location'),
            ]),
        ]);
    }

    private static function text(string $field, string $label): TextInput
    {
        return TextInput::make($field)->label($label)->required()->maxLength(150);
    }

    private static function description(string $field): TextInput
    {
        return TextInput::make($field)->label('Description')->maxLength(300);
    }

    private static function limit(string $field, string $label): TextInput
    {
        return TextInput::make($field)->label($label)->required()->integer()->minValue(1)->maxValue(48);
    }

    private static function choices(string $field, string $label, string $kind): Select
    {
        return Select::make($field)->label($label)->multiple()->searchable()->columnSpanFull()
            ->helperText('Leave empty to rank by active openings, or select items in display order.')
            ->options(function () use ($kind) {
                $options = JobFilterOptions::forListing(Job::active(), 'jobs');
                $labels = collect($options[$kind] ?? [])->map(fn ($row) => $row['label'])->all();
                if ($kind === 'location') {
                    $labels['@remote'] = 'Remote';
                }
                return $labels;
            });
    }

    protected function getHeaderActions(): array
    {
        return [PreviewAction::make()];
    }

    protected function getPreviewModalView(): string
    {
        return 'home';
    }

    protected function mutatePreviewModalData(array $data): array
    {
        return Homepage::data($this->form->getState()) + ['isPeekPreviewModal' => true];
    }

    public function save(): void
    {
        HomepageContent::updateOrCreate(['id' => 1], ['settings' => $this->form->getState()]);
        Notification::make()->title('Homepage published')->success()->send();
    }
}
