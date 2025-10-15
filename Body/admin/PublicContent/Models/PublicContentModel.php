<?php
declare(strict_types=1);
require_once ROOT . '/Brain/Core/Model.php';

class PublicContentModel extends Model
{
    private string $settingsFile;
    
    public function __construct() {
        parent::__construct();
        $this->settingsFile = ROOT . '/Storage/cache/public_settings.json';
    }
    
    public function getPublicModules(): array {
        $modules = [];
        $publicPath = ROOT . '/Body/public';
        
        if (!is_dir($publicPath)) return [];
        
        foreach (scandir($publicPath) as $module) {
            if ($module === '.' || $module === '..' || $module === 'Error') continue;
            $modulePath = $publicPath . '/' . $module;
            if (!is_dir($modulePath)) continue;
            
            $modules[] = [
                'name' => $module,
                'path' => '/Body/public/' . $module,
                'has_controller' => is_dir($modulePath . '/Controllers'),
                'has_view' => is_dir($modulePath . '/Views'),
                'has_routes' => file_exists($modulePath . '/routes.json'),
                'route_count' => $this->countRoutes($modulePath)
            ];
        }
        
        return $modules;
    }
    
    public function getPublishedPages(): array {
        try {
            $stmt = $this->db->prepare("SELECT page_id, title, slug, created_at FROM pages WHERE status = 'published' ORDER BY created_at DESC LIMIT 10");
            $stmt->execute();
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log("Get published pages error: " . $e->getMessage());
            return [];
        }
    }
    
    public function getActiveBlocks(): array {
        try {
            $stmt = $this->db->prepare("SELECT block_id, block_name, block_slug, location FROM html_blocks WHERE status = 'active' ORDER BY location");
            $stmt->execute();
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log("Get active blocks error: " . $e->getMessage());
            return [];
        }
    }
    
    public function getStats(): array {
        try {
            $pagesStmt = $this->db->prepare("SELECT COUNT(*) as total, SUM(CASE WHEN status = 'published' THEN 1 ELSE 0 END) as published FROM pages");
            $pagesStmt->execute();
            $pagesStats = $pagesStmt->fetch(\PDO::FETCH_ASSOC);
            
            $blocksStmt = $this->db->prepare("SELECT COUNT(*) as total, SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active FROM html_blocks");
            $blocksStmt->execute();
            $blocksStats = $blocksStmt->fetch(\PDO::FETCH_ASSOC);
            
            return [
                'total_modules' => count($this->getPublicModules()),
                'published_pages' => $pagesStats['published'] ?? 0,
                'total_pages' => $pagesStats['total'] ?? 0,
                'active_blocks' => $blocksStats['active'] ?? 0,
                'total_blocks' => $blocksStats['total'] ?? 0
            ];
        } catch (\PDOException $e) {
            error_log("Get stats error: " . $e->getMessage());
            return ['total_modules' => 0, 'published_pages' => 0, 'total_pages' => 0, 'active_blocks' => 0, 'total_blocks' => 0];
        }
    }
    
    public function getSettings(): array {
        if (file_exists($this->settingsFile)) {
            $content = file_get_contents($this->settingsFile);
            $settings = json_decode($content, true);
            if ($settings) return $settings;
        }
        
        return [
            'site_name' => 'CyberTirah Framework',
            'site_tagline' => 'Modern PHP Framework',
            'maintenance_mode' => false,
            'allow_registration' => true,
            'featured_module' => 'Home',
            'footer_text' => '© 2024 CyberTirah. All rights reserved.'
        ];
    }
    
    public function saveSettings(array $data): array {
        try {
            $settings = [
                'site_name' => trim($data['site_name'] ?? ''),
                'site_tagline' => trim($data['site_tagline'] ?? ''),
                'maintenance_mode' => isset($data['maintenance_mode']),
                'allow_registration' => isset($data['allow_registration']),
                'featured_module' => trim($data['featured_module'] ?? ''),
                'footer_text' => trim($data['footer_text'] ?? '')
            ];
            
            file_put_contents($this->settingsFile, json_encode($settings, JSON_PRETTY_PRINT));
            return ['success' => true, 'message' => 'Settings saved successfully'];
        } catch (\Exception $e) {
            error_log("Save settings error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to save settings'];
        }
    }
    
    private function countRoutes(string $modulePath): int {
        $routesFile = $modulePath . '/routes.json';
        if (!file_exists($routesFile)) return 0;
        
        $content = file_get_contents($routesFile);
        $routes = json_decode($content, true);
        return isset($routes['routes']) ? count($routes['routes']) : 0;
    }
}

