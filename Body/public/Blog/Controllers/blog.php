<?php

declare(strict_types=1);

require_once ROOT . '/Brain/Core/Controller.php';

/**
 * Blog Controller
 * 
 * Handles blog/news articles for the public site
 */
class BlogController extends Controller
{
    /**
     * Display blog list
     */
    public function index(): void
    {
        // Load model
        $this->load->model('public/Blog/Blog');
        
        // Get all posts with pagination
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $posts = $this->model_blog->getAllPosts($page);
        $totalPages = $this->model_blog->getTotalPages();
        
        // Prepare data
        $data = [
            'title' => 'Blog - CyberTirah Framework',
            'heading' => 'Latest News & Articles',
            'posts' => $posts,
            'current_page' => $page,
            'total_pages' => $totalPages
        ];
        
        // Load views
        $this->load->view('public/Common/header', $data);
        $this->load->view('public/Blog/index', $data);
        $this->load->view('public/Common/footer', $data);
    }
    
    /**
     * Display single blog post
     */
    public function show(): void
    {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        
        if (!$id) {
            Router::redirect('/blog');
            return;
        }
        
        // Load model
        $this->load->model('public/Blog/Blog');
        $post = $this->model_blog->getPostById($id);
        
        if (!$post) {
            Router::redirect('/blog');
            return;
        }
        
        // Get related posts
        $relatedPosts = $this->model_blog->getRelatedPosts($id, 3);
        
        // Prepare data
        $data = [
            'title' => htmlspecialchars($post['title']) . ' - CyberTirah Framework',
            'post' => $post,
            'related_posts' => $relatedPosts
        ];
        
        // Load views
        $this->load->view('public/Common/header', $data);
        $this->load->view('public/Blog/show', $data);
        $this->load->view('public/Common/footer', $data);
    }
    
    /**
     * Display posts by category
     */
    public function category(): void
    {
        $category = $_GET['category'] ?? '';
        
        if (!$category) {
            Router::redirect('/blog');
            return;
        }
        
        // Load model
        $this->load->model('public/Blog/Blog');
        $posts = $this->model_blog->getPostsByCategory($category);
        
        // Prepare data
        $data = [
            'title' => ucwords($category) . ' - Blog',
            'heading' => ucwords($category) . ' Articles',
            'posts' => $posts,
            'category' => $category
        ];
        
        // Load views
        $this->load->view('public/Common/header', $data);
        $this->load->view('public/Blog/index', $data);
        $this->load->view('public/Common/footer', $data);
    }
}

