<?php

/**
 * Filament Recurrence Plugin - Usage Examples
 * 
 * This file contains comprehensive examples of how to use the plugin
 * in various scenarios with Laravel 12 and Filament 5.
 */

namespace App\Examples;

use App\Models\Event;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Andreia\FilamentRecurrence\Forms\Components\RecurrenceField;
use Andreia\FilamentRecurrence\Infolists\Components\RecurrenceEntry;
use Andreia\FilamentRecurrence\Tables\Columns\RecurrenceColumn;

// ============================================================================
// EXAMPLE 1: Basic Event Resource with Recurrence
// ============================================================================

class EventResource extends Resource
{
    protected static ?string $model = Event::class;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Event Details')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->required()
                            ->maxLength(255),
                        
                        Forms\Components\Textarea::make('description')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Recurrence Settings')
                    ->schema([
                        RecurrenceField::make('recurrence')
                            ->showStartDate()
                            ->showEndOptions()
                            ->useDateTime(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable(),
                
                RecurrenceColumn::make('recurrence')
                    ->showNextOccurrences(limit: 3),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                \Filament\Infolists\Components\Section::make('Event Information')
                    ->schema([
                        \Filament\Infolists\Components\TextEntry::make('title'),
                        \Filament\Infolists\Components\TextEntry::make('description'),
                    ]),

                \Filament\Infolists\Components\Section::make('Recurrence Pattern')
                    ->schema([
                        RecurrenceEntry::make('recurrence')
                            ->showAllDetails()
                            ->showNextOccurrences(limit: 10)
                            ->showRule(),
                    ]),
            ]);
    }
}

// ============================================================================
// EXAMPLE 2: Model Setup with Recurrence
// ============================================================================

use Illuminate\Database\Eloquent\Model;
use Andreia\FilamentRecurrence\Casts\RecurrenceCast;
use Andreia\FilamentRecurrence\Concerns\HasRecurrence;

class EventModel extends Model
{
    use HasRecurrence;

    protected $fillable = [
        'title',
        'description',
        'recurrence',
    ];

    protected $casts = [
        'recurrence' => RecurrenceCast::class,
    ];

    // Optional: customize the recurrence attribute name
    protected string $recurrenceAttribute = 'recurrence';
}

// ============================================================================
// EXAMPLE 3: Working with Recurrence Data Programmatically
// ============================================================================

use Andreia\FilamentRecurrence\Data\RecurrenceData;

class RecurrenceExamples
{
    public function createDailyEvent(): void
    {
        $event = Event::create([
            'title' => 'Daily Standup',
            'description' => 'Team daily standup meeting',
            'recurrence' => [
                'frequency' => 'DAILY',
                'interval' => 1,
                'start_date' => Carbon::now()->setTime(9, 0),
                'by_day' => ['MO', 'TU', 'WE', 'TH', 'FR'], // Weekdays only
                'count' => 60, // 60 occurrences
            ],
        ]);
    }

    public function createWeeklyMeeting(): void
    {
        $event = Event::create([
            'title' => 'Weekly Team Meeting',
            'recurrence' => [
                'frequency' => 'WEEKLY',
                'interval' => 1,
                'start_date' => Carbon::now()->next('Monday')->setTime(14, 0),
                'by_day' => ['MO'],
                'end_date' => Carbon::now()->addYear(),
            ],
        ]);
    }

    public function createMonthlyReport(): void
    {
        $event = Event::create([
            'title' => 'Monthly Report Due',
            'recurrence' => [
                'frequency' => 'MONTHLY',
                'interval' => 1,
                'start_date' => Carbon::now()->startOfMonth()->addMonth(),
                'by_month_day' => [1], // First day of each month
            ],
        ]);
    }

    public function createQuarterlyReview(): void
    {
        $event = Event::create([
            'title' => 'Quarterly Business Review',
            'recurrence' => [
                'frequency' => 'MONTHLY',
                'interval' => 3, // Every 3 months
                'start_date' => Carbon::parse('2024-01-15'),
                'by_month_day' => [15],
            ],
        ]);
    }

    public function createBirthdayReminder(): void
    {
        $event = Event::create([
            'title' => 'John\'s Birthday',
            'recurrence' => [
                'frequency' => 'YEARLY',
                'interval' => 1,
                'start_date' => Carbon::parse('2024-06-15'),
                'by_month' => [6],
                'by_month_day' => [15],
            ],
        ]);
    }

    public function getEventOccurrences(): void
    {
        $event = Event::first();
        
        // Get recurrence data
        $data = $event->getRecurrenceData();
        
        // Human readable description
        echo $data->toHumanReadable();
        // Output: "Every day on Monday, Tuesday, Wednesday, Thursday, Friday, 60 times"
        
        // Get RRULE string
        echo $data->toRule();
        // Output: "FREQ=DAILY;INTERVAL=1;COUNT=60;BYDAY=MO,TU,WE,TH,FR"
        
        // Get next occurrence
        $next = $event->getNextOccurrence();
        echo $next->format('Y-m-d H:i');
        
        // Get next 10 occurrences
        $occurrences = $event->getUpcomingOccurrences(10);
        foreach ($occurrences as $occurrence) {
            echo $occurrence->format('Y-m-d H:i') . "\n";
        }
        
        // Check if event occurs on specific date
        if ($event->occursOn(Carbon::today())) {
            echo "Event occurs today!";
        }
    }

    public function queryRecurringEvents(): void
    {
        // Find all events that occur today
        $todayEvents = Event::occursOn(Carbon::today())->get();
        
        // Find all events that occur this week
        $weekEvents = Event::occursBetween(
            Carbon::now()->startOfWeek(),
            Carbon::now()->endOfWeek()
        )->get();
        
        // Find all events that occur in the next month
        $monthEvents = Event::occursBetween(
            Carbon::now(),
            Carbon::now()->addMonth()
        )->get();
    }
}

// ============================================================================
// EXAMPLE 4: Custom Form with Advanced Recurrence Options
// ============================================================================

class AdvancedRecurrenceForm
{
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Task Details')
                    ->schema([
                        Forms\Components\TextInput::make('title')->required(),
                        Forms\Components\RichEditor::make('description'),
                        Forms\Components\Select::make('priority')
                            ->options([
                                'low' => 'Low',
                                'medium' => 'Medium',
                                'high' => 'High',
                            ]),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Recurrence Configuration')
                    ->schema([
                        // Basic recurrence field
                        RecurrenceField::make('recurrence')
                            ->showStartDate()
                            ->showEndOptions()
                            ->showAdvancedOptions(),
                        
                        // Additional context fields
                        Forms\Components\Toggle::make('send_reminder')
                            ->label('Send reminder before occurrence')
                            ->default(true)
                            ->live(),
                        
                        Forms\Components\TextInput::make('reminder_minutes')
                            ->label('Reminder time (minutes before)')
                            ->numeric()
                            ->default(15)
                            ->visible(fn(Forms\Get $get) => $get('send_reminder')),
                    ])
                    ->collapsible(),
            ]);
    }
}

// ============================================================================
// EXAMPLE 5: Calendar Integration Example
// ============================================================================

class CalendarIntegration
{
    public function getEventsForCalendar(Carbon $start, Carbon $end): array
    {
        $events = Event::all();
        $calendarEvents = [];

        foreach ($events as $event) {
            $data = $event->getRecurrenceData();
            
            if (!$data) {
                continue;
            }

            // Get all occurrences in the date range
            $occurrences = $data->getOccurrences(1000, $end);
            
            foreach ($occurrences as $occurrence) {
                if ($occurrence->between($start, $end)) {
                    $calendarEvents[] = [
                        'id' => $event->id . '_' . $occurrence->timestamp,
                        'title' => $event->title,
                        'start' => $occurrence->toIso8601String(),
                        'description' => $event->description,
                        'recurrence' => $data->toHumanReadable(),
                    ];
                }
            }
        }

        return $calendarEvents;
    }

    public function exportToICalendar(Event $event): string
    {
        $data = $event->getRecurrenceData();
        
        $ical = "BEGIN:VCALENDAR\n";
        $ical .= "VERSION:2.0\n";
        $ical .= "BEGIN:VEVENT\n";
        $ical .= "SUMMARY:{$event->title}\n";
        $ical .= "DESCRIPTION:{$event->description}\n";
        
        if ($data && $data->startDate) {
            $ical .= "DTSTART:" . $data->startDate->format('Ymd\THis\Z') . "\n";
            $ical .= "RRULE:" . $data->toRule() . "\n";
        }
        
        $ical .= "END:VEVENT\n";
        $ical .= "END:VCALENDAR\n";
        
        return $ical;
    }
}

// ============================================================================
// EXAMPLE 6: Complex Recurrence Patterns
// ============================================================================

class ComplexRecurrencePatterns
{
    public function biWeeklyAlternatingDays(): array
    {
        return [
            'frequency' => 'WEEKLY',
            'interval' => 2,
            'start_date' => Carbon::now()->next('Monday'),
            'by_day' => ['MO', 'WE', 'FR'],
        ];
    }

    public function lastFridayOfMonth(): array
    {
        return [
            'frequency' => 'MONTHLY',
            'interval' => 1,
            'start_date' => Carbon::now()->startOfMonth(),
            'by_day' => ['FR'],
            'by_set_pos' => -1, // Last occurrence
        ];
    }

    public function secondAndFourthTuesday(): array
    {
        return [
            'frequency' => 'MONTHLY',
            'interval' => 1,
            'start_date' => Carbon::now(),
            'by_day' => ['TU'],
            'by_set_pos' => [2, 4], // 2nd and 4th
        ];
    }

    public function businessQuarterEnds(): array
    {
        return [
            'frequency' => 'YEARLY',
            'interval' => 1,
            'start_date' => Carbon::now()->startOfYear(),
            'by_month' => [3, 6, 9, 12], // March, June, September, December
            'by_month_day' => [31],
        ];
    }

    public function firstWorkdayOfMonth(): array
    {
        return [
            'frequency' => 'MONTHLY',
            'interval' => 1,
            'start_date' => Carbon::now(),
            'by_day' => ['MO', 'TU', 'WE', 'TH', 'FR'],
            'by_set_pos' => 1, // First occurrence
        ];
    }
}
