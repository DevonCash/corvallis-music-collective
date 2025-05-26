<?php

namespace CorvMC\Productions\Livewire;

use Livewire\Component;
use CorvMC\Productions\Models\Production;
use CorvMC\Productions\Models\ProductionTag;
use CorvMC\Productions\Models\Venue;
use Illuminate\Support\Carbon;
use Livewire\WithPagination;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Illuminate\Support\Str;

class ProductionsIndex extends Component implements HasForms
{
    use WithPagination;
    use InteractsWithForms;

    public $sortBy = 'start_date';
    public $sortDirection = 'desc';
    public $data = [];

    protected $queryString = [
        'sortBy' => ['except' => 'start_date'],
        'sortDirection' => ['except' => 'desc'],
    ];

    protected function rules(): array
    {
        return [
            'data.search' => 'nullable|string',
            'data.selectedVenue' => 'nullable|exists:venues,id',
            'data.dateFrom' => 'nullable|date',
            'data.dateTo' => 'nullable|date|after_or_equal:data.dateFrom',
            'data.selectedTagsByType.*' => 'nullable|array',
            'data.selectedTagsByType.*.*' => 'exists:production_tags,id',
        ];
    }

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        // Fetch all tags grouped by type
        $tagsByType = ProductionTag::orderBy('name')->get()->groupBy('type');
        return $form
            ->schema([
                TextInput::make('search')
                    ->label('Search')
                    ->placeholder('Search productions...')
                    ->debounce(400)
                    ->live(),
                Section::make('Filters')
                    ->collapsible()
                    ->compact()
                    ->collapsed()
                    ->headerActions([
                        Action::make('clear')
                            ->label('Clear Filters')
                            ->button()
                            ->extraAttributes(['class' => 'btn btn-sm btn-primary !py-0'])
                            ->color('secondary')
                            ->action(fn() => $this->form->fill()),
                    ])
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                Select::make('selectedVenue')
                                    ->label('Venue')
                                    ->options(fn() => Venue::orderBy('name')->pluck('name', 'id'))
                                    ->placeholder('All Venues')
                                    ->live(),
                                Grid::make(['default' => 2, 'md' => 2,])
                                    ->columnSpan(2)
                                    ->schema([
                                        DatePicker::make('dateFrom')
                                            ->label('Date From')
                                            ->live(),
                                        DatePicker::make('dateTo')
                                            ->label('Date To')
                                            ->live()
                                    ]),
                            ]),
                        Grid::make(['default' => 1, 'sm' => 3, 'xl' => 5])
                            ->schema(
                                collect($tagsByType)->map(function ($tags, $type) {
                                    $label = Str::headline($type ?: 'Other');
                                    return CheckboxList::make("selectedTagsByType.{$type}")
                                        ->label($label)
                                        ->options($tags->pluck('name', 'id')->toArray())
                                        ->columns(1)
                                        ->live();
                                })->values()->all()
                            ),
                    ])
            ])
            ->statePath('data');
    }

    public function updating($name)
    {
        if (in_array($name, ['data.search', 'data.selectedVenue', 'data.dateFrom', 'data.dateTo', 'data.selectedTagsByType', 'sortBy', 'sortDirection'])) {
            $this->resetPage();
        }
    }

    public function sortBy($field)
    {
        if ($this->sortBy === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function paginationView()
    {
        return 'productions::vendor.livewire.custom-pagination';
    }

    public function render()
    {
        $today = now();
        $upcomingEvents = Production::with(['venue', 'tags'])
            ->where('start_date', '>=', $today)
            ->whereIn('status', ['published', 'active'])
            ->orderBy('start_date')
            ->take(8)
            ->get();

        $formData = $this->form->getState();

        // Merge all selected tag IDs from all types
        $allSelectedTagIds = collect($formData['selectedTagsByType'] ?? [])->flatten()->filter()->unique()->values()->all();

        $productions = Production::with(['venue', 'tags'])
            ->whereIn('status', ['published', 'active'])
            ->when($formData['search'] ?? null, function ($query, $search) {
                $search = strtolower($search);
                $query->where(function ($q) use ($search) {
                    $q->whereRaw('LOWER(title) LIKE ?', ["%{$search}%"])
                        ->orWhereRaw('LOWER(description) LIKE ?', ["%{$search}%"])
                        ->orWhereHas('venue', function ($q) use ($search) {
                            $q->whereRaw('LOWER(name) LIKE ?', ["%{$search}%"]);
                        })
                        ->orWhereHas('tags', function ($q) use ($search) {
                            $q->whereRaw('LOWER(name) LIKE ?', ["%{$search}%"]);
                        });
                });
            })
            ->when($allSelectedTagIds, function ($query) use ($allSelectedTagIds) {
                $query->whereHas('tags', function ($q) use ($allSelectedTagIds) {
                    $q->whereIn('production_tag_id', $allSelectedTagIds);
                }, '>=', count($allSelectedTagIds));
            })
            ->when($formData['selectedVenue'] ?? null, function ($query, $venueId) {
                $query->where('venue_id', $venueId);
            })
            ->when($formData['dateFrom'] ?? null, function ($query, $dateFrom) {
                $query->where(function ($q) use ($dateFrom) {
                    $q->where('start_date', '>=', $dateFrom)
                        ->orWhere('end_date', '>=', $dateFrom);
                });
            })
            ->when($formData['dateTo'] ?? null, function ($query, $dateTo) {
                $query->where(function ($q) use ($dateTo) {
                    $q->where('start_date', '<=', $dateTo)
                        ->orWhere('end_date', '<=', $dateTo);
                });
            })
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate(12);

        return view('productions::livewire.productions-index', [
            'upcomingShows' => $upcomingEvents,
            'productions' => $productions,
        ]);
    }
}
