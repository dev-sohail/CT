<?php

return [
    'disk' => env('BACKUP_DISK', 'local'),

    'directory' => env('BACKUP_DIRECTORY', 'backups'),

    'keep' => (int) env('BACKUP_KEEP', 7),

    'mysqldump' => env('BACKUP_MYSQLDUMP', 'mysqldump'),

    'include_routines' => (bool) env('BACKUP_INCLUDE_ROUTINES', false),

    'include_triggers' => (bool) env('BACKUP_INCLUDE_TRIGGERS', true),

    'scheduled_at' => env('BACKUP_SCHEDULED_AT', '02:00'),
];
