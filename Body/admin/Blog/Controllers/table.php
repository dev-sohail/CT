<?php

declare(strict_types=1);

require_once ROOT . '/Brain/Core/Controller.php';

/**
 * Blog Table Controller
 * 
 * Handles blog listing and management for admin panel
 */
class BlogTableController extends Controller
{
    private object $blogModel;

    public function __construct(object $registry)
    {
        parent::__construct($registry);
        $this->blogModel = $this->loadModel('admin', 'Blog', 'blog');
    }

    /**
     * Display blog table
     */
    public function index(): void
    {
        $page = (int) ($_GET['page'] ?? 1);
        $limit = 10;
        $offset = ($page - 1) * $limit;
        
        $search = trim($_GET['search'] ?? '');
        $status = $_GET['status'] ?? '';
        
        // Build conditions
        $conditions = [];
        if (!empty($search)) {
            $conditions['search'] = $search;
        }
        if (!empty($status)) {
            $conditions['status'] = $status;
        }

        // Get blogs
        $blogs = $this->blogModel->findAll($conditions, $limit, $offset);
        $total = $this->blogModel->count($conditions);
        $totalPages = ceil($total / $limit);

        // Get statistics
        $stats = [
            'total' => $this->blogModel->count(),
            'published' => $this->blogModel->count(['status' => 'published']),
            'draft' => $this->blogModel->count(['status' => 'draft']),
            'archived' => $this->blogModel->count(['status' => 'archived'])
        ];

        $data = [
            'title' => 'Blog Management',
            'blogs' => $blogs,
            'stats' => $stats,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => $totalPages,
                'total_items' => $total,
                'items_per_page' => $limit,
                'has_prev' => $page > 1,
                'has_next' => $page < $totalPages,
                'prev_page' => $page > 1 ? $page - 1 : null,
                'next_page' => $page < $totalPages ? $page + 1 : null
            ],
            'filters' => [
                'search' => $search,
                'status' => $status
            ],
            'success_message' => $_SESSION['success_message'] ?? null,
            'error_message' => $_SESSION['error_message'] ?? null
        ];

        // Clear session messages
        unset($_SESSION['success_message'], $_SESSION['error_message']);

        $this->loadView('admin', 'Blog', 'table', $data);
    }

    /**
     * Toggle blog status
     */
    public function toggleStatus(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/admin/blog/table');
            return;
        }

        $id = (int) ($_POST['id'] ?? 0);
        $newStatus = $_POST['status'] ?? '';

        if (!$id || !in_array($newStatus, ['draft', 'published', 'archived'])) {
            $_SESSION['error_message'] = 'Invalid request';
            $this->redirect('/admin/blog/table');
            return;
        }

        $blog = $this->blogModel->findById($id);
        if (!$blog) {
            $_SESSION['error_message'] = 'Blog post not found';
            $this->redirect('/admin/blog/table');
            return;
        }

        $success = $this->blogModel->update($id, [
            'status' => $newStatus,
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        $message = $success ? 'Blog status updated successfully!' : 'Failed to update blog status.';
        
        if ($success) {
            $_SESSION['success_message'] = $message;
        } else {
            $_SESSION['error_message'] = $message;
        }

        $this->redirect('/admin/blog/table');
    }

    /**
     * Bulk actions
     */
    public function bulkAction(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/admin/blog/table');
            return;
        }

        $action = $_POST['bulk_action'] ?? '';
        $selectedIds = $_POST['selected_ids'] ?? [];

        if (empty($selectedIds) || empty($action)) {
            $_SESSION['error_message'] = 'No items selected or action specified';
            $this->redirect('/admin/blog/table');
            return;
        }

        $successCount = 0;
        $errorCount = 0;

        foreach ($selectedIds as $id) {
            $id = (int) $id;
            
            switch ($action) {
                case 'publish':
                    $success = $this->blogModel->update($id, [
                        'status' => 'published',
                        'updated_at' => date('Y-m-d H:i:s')
                    ]);
                    break;
                    
                case 'draft':
                    $success = $this->blogModel->update($id, [
                        'status' => 'draft',
                        'updated_at' => date('Y-m-d H:i:s')
                    ]);
                    break;
                    
                case 'archive':
                    $success = $this->blogModel->update($id, [
                        'status' => 'archived',
                        'updated_at' => date('Y-m-d H:i:s')
                    ]);
                    break;
                    
                case 'delete':
                    $success = $this->blogModel->delete($id);
                    break;
                    
                default:
                    $success = false;
                    break;
            }

            if ($success) {
                $successCount++;
            } else {
                $errorCount++;
            }
        }

        $message = "Bulk action completed: {$successCount} successful, {$errorCount} failed";
        $_SESSION['success_message'] = $message;

        $this->redirect('/admin/blog/table');
    }

    /**
     * Get blog data as JSON
     */
    public function json(): void
    {
        $blogs = $this->blogModel->findAll();
        
        // Format data for JSON response
        $formattedBlogs = array_map(function($blog) {
            return [
                'id' => $blog['id'],
                'title' => $blog['title'],
                'status' => $blog['status'],
                'created_at' => $blog['created_at'],
                'updated_at' => $blog['updated_at']
            ];
        }, $blogs);

        $this->jsonResponse([
            'blogs' => $formattedBlogs,
            'total' => count($formattedBlogs)
        ]);
    }

    /**
     * Export blogs
     */
    public function export(): void
    {
        $format = $_GET['format'] ?? 'csv';
        $blogs = $this->blogModel->findAll();

        switch ($format) {
            case 'csv':
                $this->exportCSV($blogs);
                break;
            case 'json':
                $this->exportJSON($blogs);
                break;
            default:
                $_SESSION['error_message'] = 'Unsupported export format';
                $this->redirect('/admin/blog/table');
        }
    }

    /**
     * Export as CSV
     */
    private function exportCSV(array $blogs): void
    {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="blogs_' . date('Y-m-d') . '.csv"');

        $output = fopen('php://output', 'w');
        
        // CSV headers
        fputcsv($output, ['ID', 'Title', 'Status', 'Created At', 'Updated At']);

        // CSV data
        foreach ($blogs as $blog) {
            fputcsv($output, [
                $blog['id'],
                $blog['title'],
                $blog['status'],
                $blog['created_at'],
                $blog['updated_at']
            ]);
        }

        fclose($output);
        exit;
    }

    /**
     * Export as JSON
     */
    private function exportJSON(array $blogs): void
    {
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="blogs_' . date('Y-m-d') . '.json"');

        echo json_encode($blogs, JSON_PRETTY_PRINT);
        exit;
    }
}
