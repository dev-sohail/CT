<?php

declare(strict_types=1);

require_once ROOT . '/Brain/Core/Controller.php';

/**
 * Blog Form Controller
 * 
 * Handles blog form operations for admin panel
 */
class BlogFormController extends Controller
{
    private object $blogModel;

    public function __construct(object $registry)
    {
        parent::__construct($registry);
        $this->blogModel = $this->loadModel('admin', 'Blog', 'blog');
    }

    /**
     * Display blog form
     */
    public function index(): void
    {
        $data = [
            'title' => 'Blog Management',
            'blog' => null,
            'errors' => []
        ];

        // Check if editing existing blog
        $id = $_GET['id'] ?? null;
        if ($id) {
            $blog = $this->blogModel->findById((int) $id);
            if ($blog) {
                $data['blog'] = $blog;
                $data['title'] = 'Edit Blog Post';
            }
        }

        $this->loadView('admin', 'Blog', 'form', $data);
    }

    /**
     * Process blog form submission
     */
    public function save(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/admin/blog/form');
            return;
        }

        $data = [
            'title' => trim($_POST['title'] ?? ''),
            'content' => trim($_POST['content'] ?? ''),
            'excerpt' => trim($_POST['excerpt'] ?? ''),
            'status' => $_POST['status'] ?? 'draft',
            'featured_image' => trim($_POST['featured_image'] ?? ''),
            'tags' => trim($_POST['tags'] ?? ''),
            'meta_title' => trim($_POST['meta_title'] ?? ''),
            'meta_description' => trim($_POST['meta_description'] ?? ''),
        ];

        // Validate data
        $errors = $this->validateBlogData($data);

        if (empty($errors)) {
            $id = $_POST['id'] ?? null;
            $success = false;
            $newId = false;
            
            if ($id) {
                // Update existing blog
                $data['updated_at'] = date('Y-m-d H:i:s');
                $success = $this->blogModel->update((int) $id, $data);
                $message = $success ? 'Blog post updated successfully!' : 'Failed to update blog post.';
            } else {
                // Create new blog
                $data['created_at'] = date('Y-m-d H:i:s');
                $data['updated_at'] = date('Y-m-d H:i:s');
                $data['author_id'] = $_SESSION['user_id'] ?? 1;
                $newId = $this->blogModel->insert($data);
                $message = $newId ? 'Blog post created successfully!' : 'Failed to create blog post.';
            }

            if ($success || $newId) {
                $_SESSION['success_message'] = $message;
                $this->redirect('/admin/blog/table');
                return;
            }
        }

        // Display form with errors
        $formData = [
            'title' => 'Blog Management',
            'blog' => $data,
            'errors' => $errors
        ];

        $this->loadView('admin', 'Blog', 'form', $formData);
    }

    /**
     * Delete blog post
     */
    public function delete(): void
    {
        $id = $_POST['id'] ?? $_GET['id'] ?? null;
        
        if (!$id) {
            $this->redirect('/admin/blog/table');
            return;
        }

        $success = $this->blogModel->delete((int) $id);
        $message = $success ? 'Blog post deleted successfully!' : 'Failed to delete blog post.';
        
        $_SESSION['success_message'] = $message;
        $this->redirect('/admin/blog/table');
    }

    /**
     * Validate blog data
     * 
     * @param array $data Blog data
     * @return array Validation errors
     */
    private function validateBlogData(array $data): array
    {
        $errors = [];

        if (empty($data['title'])) {
            $errors['title'] = 'Title is required';
        } elseif (strlen($data['title']) > 255) {
            $errors['title'] = 'Title must not exceed 255 characters';
        }

        if (empty($data['content'])) {
            $errors['content'] = 'Content is required';
        }

        if (!empty($data['excerpt']) && strlen($data['excerpt']) > 500) {
            $errors['excerpt'] = 'Excerpt must not exceed 500 characters';
        }

        if (!in_array($data['status'], ['draft', 'published', 'archived'])) {
            $errors['status'] = 'Invalid status';
        }

        if (!empty($data['featured_image']) && !filter_var($data['featured_image'], FILTER_VALIDATE_URL)) {
            $errors['featured_image'] = 'Invalid image URL';
        }

        if (!empty($data['meta_title']) && strlen($data['meta_title']) > 60) {
            $errors['meta_title'] = 'Meta title should not exceed 60 characters';
        }

        if (!empty($data['meta_description']) && strlen($data['meta_description']) > 160) {
            $errors['meta_description'] = 'Meta description should not exceed 160 characters';
        }

        return $errors;
    }

    /**
     * Get blog statistics
     */
    public function stats(): void
    {
        $stats = [
            'total' => $this->blogModel->count(),
            'published' => $this->blogModel->count(['status' => 'published']),
            'draft' => $this->blogModel->count(['status' => 'draft']),
            'archived' => $this->blogModel->count(['status' => 'archived'])
        ];

        $this->jsonResponse($stats);
    }
}
