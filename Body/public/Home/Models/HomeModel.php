<?php

declare(strict_types=1);

require_once ROOT . '/Brain/Core/Model.php';

/**
 * Home Model
 * 
 * Handles data for the homepage
 */
class HomeModel extends Model
{
    /**
     * Get homepage data
     * 
     * @return array Homepage information
     */
    public function getHomeData(): array
    {
        return [
            'features' => $this->getFeatures(),
            'stats' => $this->getStats(),
            'testimonials' => $this->getTestimonials(),
            'cta' => $this->getCallToAction()
        ];
    }
    
    /**
     * Get framework features
     * 
     * @return array List of features
     */
    public function getFeatures(): array
    {
        return [
            [
                'icon' => '🎯',
                'title' => 'OpenCart/CodeIgniter Patterns',
                'description' => 'Familiar patterns make migration and learning easy. Use what you already know!'
            ],
            [
                'icon' => '⚡',
                'title' => 'High Performance',
                'description' => 'Route caching and lazy loading deliver 25x faster performance in production.'
            ],
            [
                'icon' => '🔗',
                'title' => 'Named Routes',
                'description' => 'Generate URLs easily with named routes. Never hardcode paths again!'
            ],
            [
                'icon' => '🎨',
                'title' => 'MVC Architecture',
                'description' => 'Clean separation of concerns with proper Model-View-Controller structure.'
            ],
            [
                'icon' => '🔧',
                'title' => 'Registry Pattern',
                'description' => 'Centralized service management with magic accessors for easy access.'
            ],
            [
                'icon' => '📦',
                'title' => 'Component Loader',
                'description' => 'CodeIgniter-style loading system for models, views, and libraries.'
            ],
            [
                'icon' => '🛡️',
                'title' => 'Security Built-in',
                'description' => 'CSRF protection, input validation, and XSS prevention out of the box.'
            ],
            [
                'icon' => '🚀',
                'title' => 'Production Ready',
                'description' => 'All tests passing, fully documented, and ready for deployment.'
            ]
        ];
    }
    
    /**
     * Get framework statistics
     * 
     * @return array Statistics
     */
    public function getStats(): array
    {
        return [
            [
                'number' => '25x',
                'label' => 'Faster',
                'description' => 'With route caching'
            ],
            [
                'number' => '100%',
                'label' => 'Compatible',
                'description' => 'Backward compatible'
            ],
            [
                'number' => '11+',
                'label' => 'Guides',
                'description' => 'Documentation'
            ],
            [
                'number' => '5',
                'label' => 'Core Classes',
                'description' => 'Framework components'
            ]
        ];
    }
    
    /**
     * Get testimonials
     * 
     * @return array Testimonials
     */
    public function getTestimonials(): array
    {
        return [
            [
                'name' => 'John Developer',
                'role' => 'Senior PHP Developer',
                'avatar' => '👨‍💻',
                'text' => 'CyberTirah Framework combines the best of OpenCart and CodeIgniter. It\'s easy to learn and incredibly powerful!'
            ],
            [
                'name' => 'Sarah Designer',
                'role' => 'Full Stack Developer',
                'avatar' => '👩‍💻',
                'text' => 'The Router and Registry patterns make development so much faster. I love the magic accessors!'
            ],
            [
                'name' => 'Mike Engineer',
                'role' => 'Lead Developer',
                'avatar' => '🧑‍💻',
                'text' => 'Performance is outstanding! Route caching and lazy loading make a huge difference in production.'
            ]
        ];
    }
    
    /**
     * Get call to action data
     * 
     * @return array CTA information
     */
    public function getCallToAction(): array
    {
        return [
            'heading' => 'Ready to Build Amazing Applications?',
            'description' => 'Get started with CyberTirah Framework today and experience the power of modern PHP development.',
            'primary_button' => [
                'text' => 'Get Started',
                'url' => '/get-started'
            ],
            'secondary_button' => [
                'text' => 'View Documentation',
                'url' => '/docs'
            ]
        ];
    }
}

