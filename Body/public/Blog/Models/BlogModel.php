<?php

declare(strict_types=1);

require_once ROOT . '/Brain/Core/Model.php';

/**
 * Blog Model
 * 
 * Handles blog post data
 */
class BlogModel extends Model
{
    protected string $table = 'blog_posts';
    private int $perPage = 10;
    
    /**
     * Get all published posts
     * 
     * @param int $page Current page number
     * @return array List of posts
     */
    public function getAllPosts(int $page = 1): array
    {
        // For now, return sample data
        // In production, this would query the database
        return $this->getSamplePosts();
    }
    
    /**
     * Get single post by ID
     * 
     * @param int $id Post ID
     * @return array|null Post data
     */
    public function getPostById(int $id): ?array
    {
        $posts = $this->getSamplePosts();
        foreach ($posts as $post) {
            if ($post['id'] === $id) {
                return $post;
            }
        }
        return null;
    }
    
    /**
     * Get posts by category
     * 
     * @param string $category Category slug
     * @return array List of posts
     */
    public function getPostsByCategory(string $category): array
    {
        $allPosts = $this->getSamplePosts();
        return array_filter($allPosts, function($post) use ($category) {
            return $post['category'] === $category;
        });
    }
    
    /**
     * Get related posts
     * 
     * @param int $currentId Current post ID
     * @param int $limit Number of posts to return
     * @return array List of related posts
     */
    public function getRelatedPosts(int $currentId, int $limit = 3): array
    {
        $posts = $this->getSamplePosts();
        $related = array_filter($posts, function($post) use ($currentId) {
            return $post['id'] !== $currentId;
        });
        return array_slice($related, 0, $limit);
    }
    
    /**
     * Get total pages for pagination
     * 
     * @return int Total pages
     */
    public function getTotalPages(): int
    {
        return ceil(count($this->getSamplePosts()) / $this->perPage);
    }
    
    /**
     * Get sample blog posts (for demonstration)
     * 
     * @return array Sample posts
     */
    private function getSamplePosts(): array
    {
        return [
            [
                'id' => 1,
                'title' => 'Getting Started with CyberTirah Framework',
                'slug' => 'getting-started-cybertirah',
                'excerpt' => 'Learn how to set up and start building applications with CyberTirah Framework. This guide covers installation, configuration, and your first module.',
                'content' => 'CyberTirah Framework is a modern PHP framework that combines the best patterns from OpenCart and CodeIgniter. In this article, we\'ll walk through the basics of getting started...',
                'category' => 'tutorials',
                'author' => 'CyberTirah Team',
                'published_at' => '2025-01-10',
                'image' => '🚀'
            ],
            [
                'id' => 2,
                'title' => 'Understanding the Registry Pattern',
                'slug' => 'understanding-registry-pattern',
                'excerpt' => 'Deep dive into the Registry pattern and how CyberTirah implements OpenCart-style dependency injection.',
                'content' => 'The Registry pattern is at the heart of CyberTirah Framework. It provides centralized service management...',
                'category' => 'architecture',
                'author' => 'CyberTirah Team',
                'published_at' => '2025-01-08',
                'image' => '🔧'
            ],
            [
                'id' => 3,
                'title' => 'Building RESTful APIs',
                'slug' => 'building-restful-apis',
                'excerpt' => 'Learn how to create powerful RESTful APIs using CyberTirah\'s routing system and JSON responses.',
                'content' => 'APIs are crucial for modern web applications. CyberTirah makes it easy to build robust APIs...',
                'category' => 'tutorials',
                'author' => 'CyberTirah Team',
                'published_at' => '2025-01-05',
                'image' => '🌐'
            ],
            [
                'id' => 4,
                'title' => 'Performance Optimization Tips',
                'slug' => 'performance-optimization',
                'excerpt' => 'Discover how to optimize your CyberTirah applications for maximum performance with caching and lazy loading.',
                'content' => 'Performance is critical for user experience. Here are the best practices for optimizing your applications...',
                'category' => 'performance',
                'author' => 'CyberTirah Team',
                'published_at' => '2025-01-03',
                'image' => '⚡'
            ],
            [
                'id' => 5,
                'title' => 'Security Best Practices',
                'slug' => 'security-best-practices',
                'excerpt' => 'Essential security practices every CyberTirah developer should follow to build secure applications.',
                'content' => 'Security is not optional. Learn about XSS prevention, CSRF protection, and input validation...',
                'category' => 'security',
                'author' => 'CyberTirah Team',
                'published_at' => '2025-01-01',
                'image' => '🛡️'
            ]
        ];
    }
}

