<?php

namespace App\Filament\Resources\ClassRooms\Pages;

use App\Filament\Resources\ClassRooms\ClassRoomResource;
use App\Models\ClassSubjectSetting;
use App\Models\Subject;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Enums\FontWeight;

class ViewClassRoom extends ViewRecord
{
    protected static string $resource = ClassRoomResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            Action::make('back')
                ->label('Back')
                ->url(static::getResource()::getUrl('index'))
                ->color('gray'),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Class Information')
                    ->description('Basic details about this class')
                    ->icon('heroicon-o-academic-cap')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('name')
                                    ->label('Class Name')
                                    ->icon('heroicon-m-bookmark')
                                    ->size(TextEntry\TextEntrySize::Large)
                                    ->weight(FontWeight::Bold)
                                    ->color('primary'),

                                TextEntry::make('section')
                                    ->label('Section')
                                    ->badge()
                                    ->icon('heroicon-m-tag')
                                    ->size(TextEntry\TextEntrySize::Large)
                                    ->color('info'),

                                TextEntry::make('status')
                                    ->label('Status')
                                    ->badge()
                                    ->icon(fn (string $state): string => match ($state) {
                                        'active' => 'heroicon-m-check-circle',
                                        'inactive' => 'heroicon-m-x-circle',
                                        default => 'heroicon-m-question-mark-circle',
                                    })
                                    ->color(fn (string $state): string => match ($state) {
                                        'active' => 'success',
                                        'inactive' => 'danger',
                                        default => 'gray',
                                    })
                                    ->size(TextEntry\TextEntrySize::Large),
                            ]),

                        Grid::make(2)
                            ->schema([
                                TextEntry::make('full_name')
                                    ->label('Full Class Name')
                                    ->icon('heroicon-m-identification')
                                    ->copyable()
                                    ->copyMessage('Copied!')
                                    ->copyMessageDuration(1500),

                                TextEntry::make('classTeacher.name')
                                    ->label('Class Teacher')
                                    ->icon('heroicon-m-user')
                                    ->placeholder('Not assigned')
                                    ->default('—'),
                            ]),
                    ])
                    ->collapsible(),

                Section::make('Timetable Configuration')
                    ->description('Schedule and period allocation for this class')
                    ->icon('heroicon-o-calendar-days')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('weekly_periods')
                                    ->label('Weekly Periods')
                                    ->icon('heroicon-m-clock')
                                    ->suffix(' periods')
                                    ->numeric()
                                    ->badge()
                                    ->color('warning')
                                    ->size(TextEntry\TextEntrySize::Large),

                                TextEntry::make('total_subjects')
                                    ->label('Total Subjects')
                                    ->icon('heroicon-m-book-open')
                                    ->suffix(' subjects')
                                    ->numeric()
                                    ->badge()
                                    ->color('success')
                                    ->size(TextEntry\TextEntrySize::Large),

                                TextEntry::make('timetableSlots')
                                    ->label('Allocated Slots')
                                    ->icon('heroicon-m-calendar')
                                    ->getStateUsing(fn ($record) => $record->timetableSlots()->count())
                                    ->suffix(' slots')
                                    ->numeric()
                                    ->badge()
                                    ->color('info')
                                    ->size(TextEntry\TextEntrySize::Large),
                            ]),
                    ])
                    ->collapsible(),

                Section::make('Subject Configuration')
                    ->description('Subjects assigned to this class')
                    ->icon('heroicon-o-book-open')
                    ->schema([
                        TextEntry::make('subjects_list')
                            ->label('Active Subjects')
                            ->icon('heroicon-m-academic-cap')
                            ->getStateUsing(function ($record) {
                                $subjects = Subject::where('class_room_id', $record->id)
                                    ->where('status', 'active')
                                    ->get();

                                if ($subjects->isEmpty()) {
                                    return 'No subjects configured yet. You can add subjects from the Subjects page.';
                                }

                                $settings = ClassSubjectSetting::where('class_room_id', $record->id)
                                    ->whereIn('subject_id', $subjects->pluck('id'))
                                    ->pluck('weekly_periods', 'subject_id');

                                return $subjects->map(function ($subject) use ($settings) {
                                    $weeklyPeriods = $settings[$subject->id] ?? 0;

                                    return "• {$subject->name} ({$subject->code}) - {$weeklyPeriods} periods/week";
                                })->join("\n");
                            })
                            ->html()
                            ->formatStateUsing(fn ($state) => nl2br(e($state)))
                            ->columnSpanFull()
                            ->placeholder('No subjects configured yet. You can add subjects from the Subjects page.'),
                    ])
                    ->collapsible()
                    ->collapsed(fn ($record) => Subject::where('class_room_id', $record->id)->count() === 0),

                Section::make('Metadata')
                    ->description('Record creation and update information')
                    ->icon('heroicon-o-information-circle')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('created_at')
                                    ->label('Created At')
                                    ->icon('heroicon-m-plus-circle')
                                    ->dateTime('M d, Y h:i A')
                                    ->since()
                                    ->tooltip(fn ($state) => $state?->format('l, F j, Y g:i:s A')),

                                TextEntry::make('updated_at')
                                    ->label('Last Updated')
                                    ->icon('heroicon-m-arrow-path')
                                    ->dateTime('M d, Y h:i A')
                                    ->since()
                                    ->tooltip(fn ($state) => $state?->format('l, F j, Y g:i:s A')),
                            ]),
                    ])
                    ->collapsed()
                    ->collapsible(),
            ]);
    }
}
