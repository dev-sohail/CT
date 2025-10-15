<?php
declare(strict_types=1);
require_once ROOT . '/Brain/Core/Model.php';

class DashboardModel extends Model
{
    public function getDashboardData(string $role, int $userId): array {
        return [
            'stats' => $this->getStatsForRole($role, $userId),
            'quickLinks' => $this->getQuickLinksForRole($role),
            'recentActivity' => $this->getRecentActivity($userId)
        ];
    }
    
    private function getStatsForRole(string $role, int $userId): array {
        switch ($role) {
            case 'student':
                return [
                    ['label' => 'Enrolled Courses', 'value' => 5, 'icon' => 'fa-book', 'color' => 'primary'],
                    ['label' => 'Assignments Due', 'value' => 3, 'icon' => 'fa-tasks', 'color' => 'warning'],
                    ['label' => 'Average Grade', 'value' => '85%', 'icon' => 'fa-chart-line', 'color' => 'success'],
                    ['label' => 'Attendance', 'value' => '92%', 'icon' => 'fa-calendar-check', 'color' => 'info']
                ];
            case 'teacher':
                return [
                    ['label' => 'Active Classes', 'value' => 4, 'icon' => 'fa-chalkboard-teacher', 'color' => 'primary'],
                    ['label' => 'Total Students', 'value' => 120, 'icon' => 'fa-users', 'color' => 'success'],
                    ['label' => 'Pending Grades', 'value' => 15, 'icon' => 'fa-clipboard-check', 'color' => 'warning'],
                    ['label' => 'Materials', 'value' => 28, 'icon' => 'fa-file-alt', 'color' => 'info']
                ];
            case 'parent':
                return [
                    ['label' => 'Children', 'value' => 2, 'icon' => 'fa-child', 'color' => 'primary'],
                    ['label' => 'Total Notifications', 'value' => 5, 'icon' => 'fa-bell', 'color' => 'warning'],
                    ['label' => 'Upcoming Events', 'value' => 3, 'icon' => 'fa-calendar', 'color' => 'info'],
                    ['label' => 'Messages', 'value' => 8, 'icon' => 'fa-envelope', 'color' => 'success']
                ];
            case 'staff':
                return [
                    ['label' => 'Active Tasks', 'value' => 12, 'icon' => 'fa-clipboard-list', 'color' => 'primary'],
                    ['label' => 'Departments', 'value' => 3, 'icon' => 'fa-building', 'color' => 'info'],
                    ['label' => 'Pending Requests', 'value' => 7, 'icon' => 'fa-file-invoice', 'color' => 'warning'],
                    ['label' => 'Completed', 'value' => 45, 'icon' => 'fa-check-circle', 'color' => 'success']
                ];
            default:
                return [];
        }
    }
    
    private function getQuickLinksForRole(string $role): array {
        switch ($role) {
            case 'student':
                return [
                    ['title' => 'My Courses', 'url' => '/user/courses', 'icon' => 'fa-book', 'color' => 'primary'],
                    ['title' => 'Assignments', 'url' => '/user/assignments', 'icon' => 'fa-tasks', 'color' => 'warning'],
                    ['title' => 'Grades', 'url' => '/user/grades', 'icon' => 'fa-chart-line', 'color' => 'success'],
                    ['title' => 'Schedule', 'url' => '/user/schedule', 'icon' => 'fa-calendar', 'color' => 'info']
                ];
            case 'teacher':
                return [
                    ['title' => 'My Classes', 'url' => '/user/classes', 'icon' => 'fa-chalkboard', 'color' => 'primary'],
                    ['title' => 'Grade Students', 'url' => '/user/grading', 'icon' => 'fa-pencil-alt', 'color' => 'success'],
                    ['title' => 'Materials', 'url' => '/user/materials', 'icon' => 'fa-folder', 'color' => 'info'],
                    ['title' => 'Attendance', 'url' => '/user/attendance', 'icon' => 'fa-clipboard-check', 'color' => 'warning']
                ];
            case 'parent':
                return [
                    ['title' => 'Children', 'url' => '/user/children', 'icon' => 'fa-child', 'color' => 'primary'],
                    ['title' => 'Progress Reports', 'url' => '/user/reports', 'icon' => 'fa-file-alt', 'color' => 'success'],
                    ['title' => 'Messages', 'url' => '/user/messages', 'icon' => 'fa-envelope', 'color' => 'info'],
                    ['title' => 'Events', 'url' => '/user/events', 'icon' => 'fa-calendar', 'color' => 'warning']
                ];
            case 'staff':
                return [
                    ['title' => 'Tasks', 'url' => '/user/tasks', 'icon' => 'fa-clipboard-list', 'color' => 'primary'],
                    ['title' => 'Departments', 'url' => '/user/departments', 'icon' => 'fa-building', 'color' => 'info'],
                    ['title' => 'Reports', 'url' => '/user/reports', 'icon' => 'fa-chart-bar', 'color' => 'success'],
                    ['title' => 'Settings', 'url' => '/user/settings', 'icon' => 'fa-cog', 'color' => 'secondary']
                ];
            default:
                return [];
        }
    }
    
    private function getRecentActivity(int $userId): array {
        return [
            ['action' => 'Logged in', 'timestamp' => date('Y-m-d H:i:s', strtotime('-2 hours')), 'icon' => 'fa-sign-in-alt'],
            ['action' => 'Updated profile', 'timestamp' => date('Y-m-d H:i:s', strtotime('-1 day')), 'icon' => 'fa-user-edit'],
            ['action' => 'Viewed dashboard', 'timestamp' => date('Y-m-d H:i:s', strtotime('-3 days')), 'icon' => 'fa-eye']
        ];
    }
}

