<?php
declare(strict_types=1);
require_once ROOT . '/Brain/Core/Model.php';

class DashboardModel extends Model
{
    public function getStatistics(): array {
        try {
            // Get user stats
            $usersStmt = $this->db->prepare("SELECT COUNT(*) as total, SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active FROM user_info");
            $usersStmt->execute();
            $users = $usersStmt->fetch(\PDO::FETCH_ASSOC);
            
            // Get role stats
            $rolesStmt = $this->db->prepare("SELECT COUNT(*) as total FROM roles WHERE is_active = 1");
            $rolesStmt->execute();
            $roles = $rolesStmt->fetch(\PDO::FETCH_ASSOC);
            
            // Get page stats
            $pagesStmt = $this->db->prepare("SELECT COUNT(*) as total, SUM(CASE WHEN status = 'published' THEN 1 ELSE 0 END) as published FROM pages");
            $pagesStmt->execute();
            $pages = $pagesStmt->fetch(\PDO::FETCH_ASSOC);
            
            // Get block stats
            $blocksStmt = $this->db->prepare("SELECT COUNT(*) as total, SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active FROM html_blocks");
            $blocksStmt->execute();
            $blocks = $blocksStmt->fetch(\PDO::FETCH_ASSOC);
            
            // Get module count
            $moduleCount = $this->countModules();
            
            return [
                'total_users' => $users['total'] ?? 0,
                'active_users' => $users['active'] ?? 0,
                'total_roles' => $roles['total'] ?? 0,
                'total_pages' => $pages['total'] ?? 0,
                'published_pages' => $pages['published'] ?? 0,
                'total_blocks' => $blocks['total'] ?? 0,
                'active_blocks' => $blocks['active'] ?? 0,
                'total_modules' => $moduleCount,
                'system_status' => 'Online',
                'recent_activity' => $this->getRecentActivity()
            ];
        } catch (\PDOException $e) {
            error_log("Dashboard stats error: " . $e->getMessage());
            return $this->getDefaultStats();
        }
    }
    
    private function getDefaultStats(): array {
        return [
            'total_users' => 0,
            'active_users' => 0,
            'total_roles' => 0,
            'total_pages' => 0,
            'published_pages' => 0,
            'total_blocks' => 0,
            'active_blocks' => 0,
            'total_modules' => 0,
            'system_status' => 'Online',
            'recent_activity' => []
        ];
    }
    
    private function countModules(): int {
        $count = 0;
        $types = ['public', 'admin', 'api', 'user', 'ai'];
        foreach ($types as $type) {
            $path = ROOT . '/Body/' . $type;
            if (is_dir($path)) {
                foreach (scandir($path) as $item) {
                    if ($item !== '.' && $item !== '..' && is_dir($path . '/' . $item)) {
                        $count++;
                    }
                }
            }
        }
        return $count;
    }
    
    private function getRecentActivity(): array {
        return [
            ['action' => 'System initialized', 'time' => date('Y-m-d H:i:s')],
            ['action' => 'Admin panel accessed', 'time' => date('Y-m-d H:i:s')],
            ['action' => 'Dashboard loaded', 'time' => date('Y-m-d H:i:s')]
        ];
    }
    
    public function getAnalytics(): array {
        return [
            'page_views' => 0,
            'unique_visitors' => 0,
            'bounce_rate' => 0
        ];
    }
}
