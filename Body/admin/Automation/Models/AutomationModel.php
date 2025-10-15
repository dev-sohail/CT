<?php
declare(strict_types=1);
require_once ROOT . '/Brain/Core/Model.php';

class AutomationModel extends Model
{
    public function listModules(): array {
        $modules = [];
        $bodyPath = ROOT . '/Body';
        $types = ['public', 'admin', 'api', 'user', 'ai', 'automate'];
        
        foreach ($types as $type) {
            $typePath = $bodyPath . '/' . $type;
            if (!is_dir($typePath)) continue;
            
            foreach (scandir($typePath) as $module) {
                if ($module === '.' || $module === '..') continue;
                $modulePath = $typePath . '/' . $module;
                if (!is_dir($modulePath)) continue;
                
                $modules[] = [
                    'name' => $module,
                    'type' => $type,
                    'path' => str_replace(ROOT, '', $modulePath),
                    'has_controller' => is_dir($modulePath . '/Controllers'),
                    'has_model' => is_dir($modulePath . '/Models'),
                    'has_view' => is_dir($modulePath . '/Views'),
                    'has_routes' => file_exists($modulePath . '/routes.json')
                ];
            }
        }
        
        return $modules;
    }
    
    public function getStats(): array {
        $modules = $this->listModules();
        $stats = [
            'total' => count($modules),
            'public' => 0,
            'admin' => 0,
            'api' => 0,
            'user' => 0,
            'ai' => 0,
            'automate' => 0
        ];
        
        foreach ($modules as $module) {
            if (isset($stats[$module['type']])) {
                $stats[$module['type']]++;
            }
        }
        
        return $stats;
    }
    
    public function scanAndUpdateModules(): array {
        try {
            $modules = $this->listModules();
            $logFile = ROOT . '/Storage/logs/modules_scan.json';
            
            $scanData = [
                'timestamp' => date('Y-m-d H:i:s'),
                'total_modules' => count($modules),
                'modules' => $modules
            ];
            
            file_put_contents($logFile, json_encode($scanData, JSON_PRETTY_PRINT));
            
            return ['success' => true, 'message' => 'Scanned ' . count($modules) . ' modules successfully'];
        } catch (\Exception $e) {
            error_log("Scan modules error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to scan modules'];
        }
    }
}

