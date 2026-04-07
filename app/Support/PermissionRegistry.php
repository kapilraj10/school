<?php

namespace App\Support;

class PermissionRegistry
{
    /**
     * @return array<string, array{label: string, actions: array<int, string>}>
     */
    public static function permissionMap(): array
    {
        return [
            'dashboard' => [
                'label' => 'Dashboard',
                'actions' => ['view'],
            ],
            'timetable_designer' => [
                'label' => 'Timetable Designer',
                'actions' => ['view'],
            ],
            'class_room' => [
                'label' => 'Class Rooms',
                'actions' => ['list', 'view', 'create', 'edit', 'delete'],
            ],
            'teacher' => [
                'label' => 'Teachers',
                'actions' => ['list', 'view', 'create', 'edit', 'delete'],
            ],
            'subject' => [
                'label' => 'Subjects',
                'actions' => ['list', 'view', 'create', 'edit', 'delete'],
            ],
            'academic_term' => [
                'label' => 'Academic Terms',
                'actions' => ['list', 'view', 'create', 'edit', 'delete'],
            ],
            'holiday' => [
                'label' => 'Holidays',
                'actions' => ['list', 'view', 'create', 'edit', 'delete'],
            ],
            'school_gallery' => [
                'label' => 'School Gallery',
                'actions' => ['list', 'view', 'create', 'edit', 'delete'],
            ],
            'blog_post' => [
                'label' => 'Blog Posts',
                'actions' => ['list', 'view', 'create', 'edit', 'delete'],
            ],
            'contact_submission' => [
                'label' => 'Contact Submissions',
                'actions' => ['list', 'view', 'create', 'edit', 'delete'],
            ],
            'contact_setting' => [
                'label' => 'Contact Settings',
                'actions' => ['list', 'view', 'create', 'edit', 'delete'],
            ],
            'exam_schedule' => [
                'label' => 'Exam Schedules',
                'actions' => ['list', 'view', 'create', 'edit', 'delete'],
            ],
            'special_event' => [
                'label' => 'Special Events',
                'actions' => ['list', 'view', 'create', 'edit', 'delete'],
            ],
            'combined_period' => [
                'label' => 'Combined Periods',
                'actions' => ['list', 'view', 'create', 'edit', 'delete'],
            ],
            'class_subject_setting' => [
                'label' => 'Class Subject Settings',
                'actions' => ['list', 'view', 'create', 'edit', 'delete'],
            ],
            'timetable_setting' => [
                'label' => 'General Settings',
                'actions' => ['list', 'view', 'create', 'edit', 'delete'],
            ],
            'role' => [
                'label' => 'Roles',
                'actions' => ['list', 'view', 'create', 'edit', 'delete'],
            ],
            'user' => [
                'label' => 'Users',
                'actions' => ['list', 'view', 'create', 'edit', 'delete'],
            ],
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function allPermissions(): array
    {
        $permissions = [];

        foreach (self::permissionMap() as $prefix => $config) {
            foreach ($config['actions'] as $action) {
                $permissions[] = "{$prefix}.{$action}";
            }
        }

        return $permissions;
    }

    /**
     * @return array<string, array<string, string>>
     */
    public static function groupedOptions(): array
    {
        $options = [];

        foreach (self::permissionMap() as $prefix => $config) {
            $options[$config['label']] = [];

            foreach ($config['actions'] as $action) {
                $options[$config['label']]["{$prefix}.{$action}"] = ucfirst($action);
            }
        }

        return $options;
    }

    /**
     * @return array<string, array<string, string>>
     */
    public static function optionsByPrefix(): array
    {
        $options = [];

        foreach (self::permissionMap() as $prefix => $config) {
            $options[$prefix] = [];

            foreach ($config['actions'] as $action) {
                $options[$prefix]["{$prefix}.{$action}"] = ucfirst($action);
            }
        }

        return $options;
    }
}
