<?php
declare(strict_types=1);
require_once ROOT . '/Brain/Core/Model.php';

class ModuleGeneratorModel extends Model
{
    public function generateModule(array $data): array {
        try {
            if (empty($data['module_name'])) {
                return ['success' => false, 'message' => 'Module name is required'];
            }
            
            $moduleName = ucfirst($data['module_name']);
            $moduleType = $data['module_type'] ?? 'public';
            $basePath = ROOT . "/Body/{$moduleType}/{$moduleName}";
            
            if (is_dir($basePath)) {
                return ['success' => false, 'message' => "Module '{$moduleName}' already exists in {$moduleType}"];
            }
            
            // Create directories
            mkdir($basePath, 0755, true);
            mkdir($basePath . '/Controllers', 0755, true);
            if ($data['include_model']) mkdir($basePath . '/Models', 0755, true);
            if ($data['include_view']) mkdir($basePath . '/Views', 0755, true);
            
            // Generate Controller
            $controllerContent = $this->generateController($moduleName, $data);
            file_put_contents($basePath . "/Controllers/" . strtolower($moduleName) . ".php", $controllerContent);
            
            // Generate Model
            if ($data['include_model']) {
                $modelContent = $this->generateModel($moduleName, $data);
                file_put_contents($basePath . "/Models/{$moduleName}Model.php", $modelContent);
            }
            
            // Generate Views
            if ($data['include_view']) {
                $viewContent = $this->generateView($moduleName, $data);
                file_put_contents($basePath . "/Views/index.ct", $viewContent);
                
                if ($data['create_crud']) {
                    file_put_contents($basePath . "/Views/create.ct", $this->generateCreateView($moduleName));
                    file_put_contents($basePath . "/Views/edit.ct", $this->generateEditView($moduleName));
                }
            }
            
            // Generate Routes
            $routesContent = $this->generateRoutes($moduleName, $moduleType, $data);
            file_put_contents($basePath . "/routes.json", $routesContent);
            
            // Clear route cache
            $cacheFile = ROOT . '/Storage/cache/routes.php';
            if (file_exists($cacheFile)) unlink($cacheFile);
            
            return ['success' => true, 'message' => "Module '{$moduleName}' generated successfully in {$moduleType}!"];
        } catch (\Exception $e) {
            error_log("Generate module error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to generate module: ' . $e->getMessage()];
        }
    }
    
    private function generateController(string $name, array $data): string {
        $controllerName = ucfirst($name) . 'Controller';
        $hasModel = $data['include_model'] ? 'true' : 'false';
        $isCrud = $data['create_crud'];
        
        $content = "<?php\ndeclare(strict_types=1);\nrequire_once ROOT . '/Brain/Core/Controller.php';\n\n";
        $content .= "class {$controllerName} extends Controller\n{\n";
        $content .= "    public function index(): void {\n";
        
        if ($isCrud && $data['include_model']) {
            $modelPath = $data['module_type'] . "/{$name}/{$name}";
            $content .= "        \$this->load->model('{$modelPath}');\n";
            $content .= "        \$data = ['title' => '{$name} Management', 'items' => \$this->model_" . strtolower($name) . "->getAll()];\n";
        } else {
            $content .= "        \$data = ['title' => '{$name}'];\n";
        }
        
        if ($data['include_view']) {
            $viewPath = $data['module_type'] . "/{$name}";
            $content .= "        \$this->load->view('{$viewPath}/index', \$data);\n";
        } else {
            $content .= "        echo 'Welcome to {$name} Module';\n";
        }
        
        $content .= "    }\n";
        
        if ($isCrud) {
            $content .= "\n    public function create(): void {\n";
            $content .= "        \$data = ['title' => 'Create {$name}'];\n";
            $content .= "        \$this->load->view('{$data['module_type']}/{$name}/create', \$data);\n";
            $content .= "    }\n";
            
            $content .= "\n    public function store(): void {\n";
            $content .= "        \$this->load->model('{$data['module_type']}/{$name}/{$name}');\n";
            $content .= "        \$result = \$this->model_" . strtolower($name) . "->create(\$_POST);\n";
            $content .= "        header('Location: /{$data['module_type']}/" . strtolower($name) . "');\n";
            $content .= "    }\n";
            
            $content .= "\n    public function edit(): void {\n";
            $content .= "        \$id = (int)(\$_GET['id'] ?? 0);\n";
            $content .= "        \$this->load->model('{$data['module_type']}/{$name}/{$name}');\n";
            $content .= "        \$item = \$this->model_" . strtolower($name) . "->getById(\$id);\n";
            $content .= "        \$data = ['title' => 'Edit {$name}', 'item' => \$item];\n";
            $content .= "        \$this->load->view('{$data['module_type']}/{$name}/edit', \$data);\n";
            $content .= "    }\n";
            
            $content .= "\n    public function update(): void {\n";
            $content .= "        \$id = (int)(\$_POST['id'] ?? 0);\n";
            $content .= "        \$this->load->model('{$data['module_type']}/{$name}/{$name}');\n";
            $content .= "        \$result = \$this->model_" . strtolower($name) . "->update(\$id, \$_POST);\n";
            $content .= "        header('Location: /{$data['module_type']}/" . strtolower($name) . "');\n";
            $content .= "    }\n";
            
            $content .= "\n    public function delete(): void {\n";
            $content .= "        \$id = (int)(\$_GET['id'] ?? 0);\n";
            $content .= "        \$this->load->model('{$data['module_type']}/{$name}/{$name}');\n";
            $content .= "        \$result = \$this->model_" . strtolower($name) . "->delete(\$id);\n";
            $content .= "        header('Location: /{$data['module_type']}/" . strtolower($name) . "');\n";
            $content .= "    }\n";
        }
        
        $content .= "}\n";
        return $content;
    }
    
    private function generateModel(string $name, array $data): string {
        $modelName = ucfirst($name) . 'Model';
        $tableName = $data['table_name'] ?: strtolower($name);
        
        $content = "<?php\ndeclare(strict_types=1);\nrequire_once ROOT . '/Brain/Core/Model.php';\n\n";
        $content .= "class {$modelName} extends Model\n{\n";
        $content .= "    protected string \$table = '{$tableName}';\n\n";
        
        if ($data['create_crud']) {
            $content .= "    public function getAll(): array {\n";
            $content .= "        try {\n";
            $content .= "            \$stmt = \$this->db->prepare(\"SELECT * FROM {\$this->table} ORDER BY id DESC\");\n";
            $content .= "            \$stmt->execute();\n";
            $content .= "            return \$stmt->fetchAll(\\PDO::FETCH_ASSOC);\n";
            $content .= "        } catch (\\PDOException \$e) {\n";
            $content .= "            error_log(\"Get all error: \" . \$e->getMessage());\n";
            $content .= "            return [];\n";
            $content .= "        }\n";
            $content .= "    }\n\n";
            
            $content .= "    public function getById(int \$id): ?array {\n";
            $content .= "        try {\n";
            $content .= "            \$stmt = \$this->db->prepare(\"SELECT * FROM {\$this->table} WHERE id = ?\");\n";
            $content .= "            \$stmt->execute([\$id]);\n";
            $content .= "            \$result = \$stmt->fetch(\\PDO::FETCH_ASSOC);\n";
            $content .= "            return \$result ?: null;\n";
            $content .= "        } catch (\\PDOException \$e) {\n";
            $content .= "            error_log(\"Get by id error: \" . \$e->getMessage());\n";
            $content .= "            return null;\n";
            $content .= "        }\n";
            $content .= "    }\n\n";
            
            $content .= "    public function create(array \$data): array {\n";
            $content .= "        try {\n";
            $content .= "            // Add your create logic here\n";
            $content .= "            return ['success' => true, 'message' => 'Created successfully'];\n";
            $content .= "        } catch (\\PDOException \$e) {\n";
            $content .= "            error_log(\"Create error: \" . \$e->getMessage());\n";
            $content .= "            return ['success' => false, 'message' => 'Database error'];\n";
            $content .= "        }\n";
            $content .= "    }\n\n";
            
            $content .= "    public function update(int \$id, array \$data): array {\n";
            $content .= "        try {\n";
            $content .= "            // Add your update logic here\n";
            $content .= "            return ['success' => true, 'message' => 'Updated successfully'];\n";
            $content .= "        } catch (\\PDOException \$e) {\n";
            $content .= "            error_log(\"Update error: \" . \$e->getMessage());\n";
            $content .= "            return ['success' => false, 'message' => 'Database error'];\n";
            $content .= "        }\n";
            $content .= "    }\n\n";
            
            $content .= "    public function delete(int \$id): array {\n";
            $content .= "        try {\n";
            $content .= "            \$stmt = \$this->db->prepare(\"DELETE FROM {\$this->table} WHERE id = ?\");\n";
            $content .= "            \$success = \$stmt->execute([\$id]);\n";
            $content .= "            return ['success' => \$success, 'message' => \$success ? 'Deleted successfully' : 'Failed to delete'];\n";
            $content .= "        } catch (\\PDOException \$e) {\n";
            $content .= "            error_log(\"Delete error: \" . \$e->getMessage());\n";
            $content .= "            return ['success' => false, 'message' => 'Database error'];\n";
            $content .= "        }\n";
            $content .= "    }\n";
        }
        
        $content .= "}\n";
        return $content;
    }
    
    private function generateView(string $name, array $data): string {
        $isCrud = $data['create_crud'];
        $content = "<h1>{$name} Management</h1>\n";
        
        if ($isCrud) {
            $content .= "<a href=\"/" . $data['module_type'] . "/" . strtolower($name) . "/create\">Create New</a>\n";
            $content .= "<table>\n<thead><tr><th>ID</th><th>Name</th><th>Actions</th></tr></thead>\n<tbody>\n";
            $content .= "<?php foreach (\$items as \$item): ?>\n";
            $content .= "<tr><td>{\$item['id']}</td><td>{\$item['name']}</td><td><a href=\"/" . $data['module_type'] . "/" . strtolower($name) . "/edit?id={\$item['id']}\">Edit</a> <a href=\"/" . $data['module_type'] . "/" . strtolower($name) . "/delete?id={\$item['id']}\">Delete</a></td></tr>\n";
            $content .= "<?php endforeach; ?>\n";
            $content .= "</tbody>\n</table>\n";
        } else {
            $content .= "<p>Welcome to {$name} module!</p>\n";
        }
        
        return $content;
    }
    
    private function generateCreateView(string $name): string {
        return "<h1>Create {$name}</h1>\n<form method=\"POST\" action=\"/admin/" . strtolower($name) . "/store\">\n<input type=\"text\" name=\"name\" placeholder=\"Name\" required>\n<button type=\"submit\">Create</button>\n</form>\n";
    }
    
    private function generateEditView(string $name): string {
        return "<h1>Edit {$name}</h1>\n<form method=\"POST\" action=\"/admin/" . strtolower($name) . "/update\">\n<input type=\"hidden\" name=\"id\" value=\"<?= \$item['id'] ?>\">\n<input type=\"text\" name=\"name\" value=\"<?= \$item['name'] ?>\" required>\n<button type=\"submit\">Update</button>\n</form>\n";
    }
    
    private function generateRoutes(string $name, string $type, array $data): string {
        $prefix = $type === 'public' ? '' : "/{$type}";
        $handler = "{$type}/{$name}/" . ucfirst($name);
        $routeName = strtolower($type . '.' . $name);
        $path = strtolower($name);
        
        $routes = [
            ["method" => "GET", "path" => "{$prefix}/{$path}", "handler" => "{$handler}@index", "name" => "{$routeName}.index"]
        ];
        
        if ($data['create_crud']) {
            $routes[] = ["method" => "GET", "path" => "{$prefix}/{$path}/create", "handler" => "{$handler}@create", "name" => "{$routeName}.create"];
            $routes[] = ["method" => "POST", "path" => "{$prefix}/{$path}/store", "handler" => "{$handler}@store", "name" => "{$routeName}.store"];
            $routes[] = ["method" => "GET", "path" => "{$prefix}/{$path}/edit", "handler" => "{$handler}@edit", "name" => "{$routeName}.edit"];
            $routes[] = ["method" => "POST", "path" => "{$prefix}/{$path}/update", "handler" => "{$handler}@update", "name" => "{$routeName}.update"];
            $routes[] = ["method" => "GET", "path" => "{$prefix}/{$path}/delete", "handler" => "{$handler}@delete", "name" => "{$routeName}.delete"];
        }
        
        return json_encode(["routes" => $routes], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }
}

