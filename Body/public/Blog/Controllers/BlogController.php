<?php

declare(strict_types=1);

require_once ROOT . '/Brain/Core/Controller.php';

/**
 * Public Blog Controller
 * 
 * Handles public blog display and operations
 */
class BlogController extends Controller
{
    private object $blogModel;

    public function __construct(object $registry)
    {
        parent::__construct($registry);
        $this->blogModel = $this->loadModel('public', 'Blog', 'blog');
    }

    /**
     * Display blog listing
     */
    public function index(): void
    {
        $page = (int) ($_GET['page'] ?? 1);
        $limit = 10;
        $offset = ($page - 1) * $limit;
        
        $search = trim($_GET['search'] ?? '');
        $tag = trim($_GET['tag'] ?? '');
        
        // Build conditions
        $conditions = ['status' => 'published'];
        if (!empty($search)) {
            $conditions['search'] = $search;
        }
        if (!empty($tag)) {
            $conditions['tag'] = $tag;
        }

        // Get blogs
        $blogs = $this->blogModel->findAll($conditions, $limit, $offset);
        $total = $this->blogModel->count($conditions);
        $totalPages = ceil($total / $limit);

        $this->set('title', 'Blog');
        $this->set('blogs', $blogs);
        $this->set('pagination', [
            'current_page' => $page,
            'total_pages' => $totalPages,
            'total_items' => $total,
            'items_per_page' => $limit,
            'has_prev' => $page > 1,
            'has_next' => $page < $totalPages,
            'prev_page' => $page > 1 ? $page - 1 : null,
            'next_page' => $page < $totalPages ? $page + 1 : null
        ]);
        $this->set('filters', [
            'search' => $search,
            'tag' => $tag
        ]);
        
        $this->loadView('public', 'Blog', 'index');
    }

    /**
     * Display single blog post
     */
    public function show(): void
    {
        $slug = $_GET['slug'] ?? '';
        
        if (empty($slug)) {
            $this->redirect('/blog');
            return;
        }

        $blog = $this->blogModel->findBySlug($slug);
        
        if (!$blog) {
            $this->redirect('/blog');
            return;
        }

        // Increment view count
        $this->blogModel->incrementViewCount($blog['id']);

        // Get related posts
        $related = $this->blogModel->findRelated($blog['id'], 3);

        $this->set('title', $blog['title']);
        $this->set('blog', $blog);
        $this->set('related', $related);
        
        $this->loadView('public', 'Blog', 'show');
    }

    /**
     * Get blog posts as JSON
     */
    public function json(): void
    {
        $limit = (int) ($_GET['limit'] ?? 10);
        $blogs = $this->blogModel->findPublished($limit);
        
        $this->jsonResponse([
            'blogs' => $blogs,
            'total' => count($blogs)
        ]);
    }
}
