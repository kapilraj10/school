<?php

namespace Testing_algo;

class TeacherData {
    
    public static function getTeachers(): array
    {
        $teachers = [
            // Class 1-4 Teachers
            ['name' => 'T001', 'subject' => 'Eng', 'class_range' => '1 - 4', 'count' => 1, 'available_days' => ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri'], 'available_periods' => ['1', '2', '3', '4', '5', '6', '7', '8']],
            ['name' => 'T002', 'subject' => 'Nep', 'class_range' => '1 - 4', 'count' => 1, 'available_days' => ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri'], 'available_periods' => ['1', '2', '3', '4', '5', '6', '7', '8']],
            ['name' => 'T003', 'subject' => 'Maths', 'class_range' => '1 - 4', 'count' => 1, 'available_days' => ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri'], 'available_periods' => ['1', '2', '3', '4', '5', '6', '7', '8']],
            ['name' => 'T004', 'subject' => 'Science', 'class_range' => '1 - 4', 'count' => 1, 'available_days' => ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri'], 'available_periods' => ['1', '2', '3', '4', '5', '6', '7', '8']],
            ['name' => 'T005', 'subject' => 'Serofero', 'class_range' => '1 - 4', 'count' => 1, 'available_days' => ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri'], 'available_periods' => ['1', '2', '3', '4', '5', '6', '7', '8']],
            ['name' => 'T006', 'subject' => 'Computer', 'class_range' => '1 - 4', 'count' => 1, 'available_days' => ['Sun', 'Mon', 'Tue', 'Wed', 'Thu'], 'available_periods' => ['1', '2', '3', '4', '5', '6', '7', '8']],
            ['name' => 'T007', 'subject' => 'Moral', 'class_range' => '1 - 4', 'count' => 1, 'available_days' => ['Sun', 'Mon', 'Tue', 'Wed', 'Fri'], 'available_periods' => ['1', '2', '3', '4', '5', '6', '7', '8']],
            ['name' => 'T008', 'subject' => 'GK', 'class_range' => '1 - 4', 'count' => 1, 'available_days' => ['Sun', 'Mon', 'Thu', 'Fri'], 'available_periods' => ['1', '2', '3', '4', '5', '6', '7', '8']],
            ['name' => 'T009', 'subject' => 'Dance', 'class_range' => '1 - 4', 'count' => 1, 'available_days' => ['Sun', 'Tue', 'Thu', 'Fri'], 'available_periods' => ['1', '2', '3', '4', '5', '6', '7', '8']],
            ['name' => 'T010', 'subject' => 'Music', 'class_range' => '1 - 4', 'count' => 1, 'available_days' => ['Sun', 'Mon', 'Wed', 'Fri'], 'available_periods' => ['1', '2', '3', '4', '5', '6', '7', '8']],
            ['name' => 'T011', 'subject' => 'Art', 'class_range' => '1 - 4', 'count' => 1, 'available_days' => ['Sun', 'Tue', 'Thu', 'Fri'], 'available_periods' => ['1', '2', '3', '4', '5', '6', '7', '8']],
            ['name' => 'T012', 'subject' => 'Sports', 'class_range' => '1 - 4', 'count' => 1, 'available_days' => ['Sun', 'Mon', 'Wed', 'Fri'], 'available_periods' => ['1', '2', '3', '4', '5', '6', '7', '8']],
            ['name' => 'T013', 'subject' => 'Taekwando', 'class_range' => '1 - 4', 'count' => 1, 'available_days' => ['Sun', 'Tue', 'Thu'], 'available_periods' => ['1', '2', '3', '4', '5', '6', '7', '8']],
            ['name' => 'T014', 'subject' => 'Library', 'class_range' => '1 - 4', 'count' => 1, 'available_days' => ['Sun', 'Mon', 'Wed'], 'available_periods' => ['1', '2', '3', '4', '5', '6', '7', '8']],
            
            // Class 5-7 Teachers
            ['name' => 'T015', 'subject' => 'Eng', 'class_range' => '5 - 7', 'count' => 1, 'available_days' => ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri'], 'available_periods' => ['1', '2', '3', '4', '5', '6', '7', '8']],
            ['name' => 'T016', 'subject' => 'Nep', 'class_range' => '5 - 7', 'count' => 1, 'available_days' => ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri'], 'available_periods' => ['1', '2', '3', '4', '5', '6', '7', '8']],
            ['name' => 'T017', 'subject' => 'Maths', 'class_range' => '5 - 7', 'count' => 1, 'available_days' => ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri'], 'available_periods' => ['1', '2', '3', '4', '5', '6', '7', '8']],
            ['name' => 'T018', 'subject' => 'Science', 'class_range' => '5 - 7', 'count' => 1, 'available_days' => ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri'], 'available_periods' => ['1', '2', '3', '4', '5', '6', '7', '8']],
            ['name' => 'T019', 'subject' => 'Social', 'class_range' => '5 - 7', 'count' => 1, 'available_days' => ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri'], 'available_periods' => ['1', '2', '3', '4', '5', '6', '7', '8']],
            ['name' => 'T020', 'subject' => 'H&PE', 'class_range' => '5 - 7', 'count' => 1, 'available_days' => ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri'], 'available_periods' => ['1', '2', '3', '4', '5', '6', '7', '8']],
            ['name' => 'T021', 'subject' => 'Nepal Bhasa', 'class_range' => '5 - 7', 'count' => 1, 'available_days' => ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri'], 'available_periods' => ['1', '2', '3', '4', '5', '6', '7', '8']],
            ['name' => 'T022', 'subject' => 'Computer', 'class_range' => '5 - 7', 'count' => 1, 'available_days' => ['Sun', 'Mon', 'Tue', 'Wed', 'Thu'], 'available_periods' => ['1', '2', '3', '4', '5', '6', '7', '8']],
            ['name' => 'T023', 'subject' => 'Dance', 'class_range' => '5 - 7', 'count' => 1, 'available_days' => ['Sun', 'Tue', 'Thu', 'Fri'], 'available_periods' => ['1', '2', '3', '4', '5', '6', '7', '8']],
            ['name' => 'T024', 'subject' => 'Music', 'class_range' => '5 - 7', 'count' => 1, 'available_days' => ['Sun', 'Mon', 'Wed', 'Fri'], 'available_periods' => ['1', '2', '3', '4', '5', '6', '7', '8']],
            ['name' => 'T025', 'subject' => 'Art', 'class_range' => '5 - 7', 'count' => 1, 'available_days' => ['Sun', 'Tue', 'Thu', 'Fri'], 'available_periods' => ['1', '2', '3', '4', '5', '6', '7', '8']],
            ['name' => 'T026', 'subject' => 'Sports', 'class_range' => '5 - 7', 'count' => 1, 'available_days' => ['Sun', 'Mon', 'Wed', 'Fri'], 'available_periods' => ['1', '2', '3', '4', '5', '6', '7', '8']],
            ['name' => 'T027', 'subject' => 'Taekwando', 'class_range' => '5 - 7', 'count' => 1, 'available_days' => ['Sun', 'Tue', 'Thu'], 'available_periods' => ['1', '2', '3', '4', '5', '6', '7', '8']],
            ['name' => 'T028', 'subject' => 'Library', 'class_range' => '5 - 7', 'count' => 1, 'available_days' => ['Sun', 'Mon', 'Wed'], 'available_periods' => ['1', '2', '3', '4', '5', '6', '7', '8']],
            
            // Class 8 Teachers
            ['name' => 'T029', 'subject' => 'Eng', 'class_range' => '8', 'count' => 1, 'available_days' => ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri'], 'available_periods' => ['1', '2', '3', '4', '5', '6', '7', '8']],
            ['name' => 'T030', 'subject' => 'Nep', 'class_range' => '8', 'count' => 1, 'available_days' => ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri'], 'available_periods' => ['1', '2', '3', '4', '5', '6', '7', '8']],
            ['name' => 'T031', 'subject' => 'Maths', 'class_range' => '8', 'count' => 1, 'available_days' => ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri'], 'available_periods' => ['1', '2', '3', '4', '5', '6', '7', '8']],
            ['name' => 'T032', 'subject' => 'Science', 'class_range' => '8', 'count' => 1, 'available_days' => ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri'], 'available_periods' => ['1', '2', '3', '4', '5', '6', '7', '8']],
            ['name' => 'T033', 'subject' => 'Social', 'class_range' => '8', 'count' => 1, 'available_days' => ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri'], 'available_periods' => ['1', '2', '3', '4', '5', '6', '7', '8']],
            ['name' => 'T034', 'subject' => 'H&PE', 'class_range' => '8', 'count' => 1, 'available_days' => ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri'], 'available_periods' => ['1', '2', '3', '4', '5', '6', '7', '8']],
            ['name' => 'T035', 'subject' => 'Opt. Math', 'class_range' => '8', 'count' => 1, 'available_days' => ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri'], 'available_periods' => ['1', '2', '3', '4', '5', '6', '7', '8']],
            ['name' => 'T036', 'subject' => 'Nepal Bhasa', 'class_range' => '8', 'count' => 1, 'available_days' => ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri'], 'available_periods' => ['1', '2', '3', '4', '5', '6', '7', '8']],
            ['name' => 'T037', 'subject' => 'Computer', 'class_range' => '8', 'count' => 1, 'available_days' => ['Sun', 'Mon', 'Tue', 'Wed', 'Thu'], 'available_periods' => ['1', '2', '3', '4', '5', '6', '7', '8']],
            ['name' => 'T038', 'subject' => 'Dance/Music', 'class_range' => '8', 'count' => 1, 'available_days' => ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri'], 'available_periods' => ['1', '2', '3', '4', '5', '6', '7', '8']],
            ['name' => 'T039', 'subject' => 'Art', 'class_range' => '8', 'count' => 1, 'available_days' => ['Sun', 'Tue', 'Thu', 'Fri'], 'available_periods' => ['1', '2', '3', '4', '5', '6', '7', '8']],
            ['name' => 'T040', 'subject' => 'Sports', 'class_range' => '8', 'count' => 1, 'available_days' => ['Sun', 'Mon', 'Wed', 'Fri'], 'available_periods' => ['1', '2', '3', '4', '5', '6', '7', '8']],
            
            // Class 9-10 Teachers
            ['name' => 'T041', 'subject' => 'Eng', 'class_range' => '9 - 10', 'count' => 1, 'available_days' => ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri'], 'available_periods' => ['1', '2', '3', '4', '5', '6', '7', '8']],
            ['name' => 'T042', 'subject' => 'Nep', 'class_range' => '9 - 10', 'count' => 1, 'available_days' => ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri'], 'available_periods' => ['1', '2', '3', '4', '5', '6', '7', '8']],
            ['name' => 'T043', 'subject' => 'Maths', 'class_range' => '9 - 10', 'count' => 1, 'available_days' => ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri'], 'available_periods' => ['1', '2', '3', '4', '5', '6', '7', '8']],
            ['name' => 'T044', 'subject' => 'Science', 'class_range' => '9 - 10', 'count' => 1, 'available_days' => ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri'], 'available_periods' => ['1', '2', '3', '4', '5', '6', '7', '8']],
            ['name' => 'T045', 'subject' => 'Social', 'class_range' => '9 - 10', 'count' => 1, 'available_days' => ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri'], 'available_periods' => ['1', '2', '3', '4', '5', '6', '7', '8']],
            ['name' => 'T046', 'subject' => 'Opt. Math', 'class_range' => '9 - 10', 'count' => 1, 'available_days' => ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri'], 'available_periods' => ['1', '2', '3', '4', '5', '6', '7', '8']],
            ['name' => 'T047', 'subject' => 'Computer/Account', 'class_range' => '9 - 10', 'count' => 1, 'available_days' => ['Sun', 'Mon', 'Tue', 'Wed', 'Thu'], 'available_periods' => ['1', '2', '3', '4', '5', '6', '7', '8']],
            ['name' => 'T048', 'subject' => 'Dance/Music/Art', 'class_range' => '9 - 10', 'count' => 1, 'available_days' => ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri'], 'available_periods' => ['1', '2', '3', '4', '5', '6', '7', '8']],
            ['name' => 'T049', 'subject' => 'Sports', 'class_range' => '9 - 10', 'count' => 1, 'available_days' => ['Sun', 'Mon', 'Wed', 'Fri'], 'available_periods' => ['1', '2', '3', '4', '5', '6', '7', '8']],
        ];

        return $teachers;
    }
}