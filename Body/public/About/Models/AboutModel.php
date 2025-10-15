<?php

declare(strict_types=1);

require_once ROOT . '/Brain/Core/Model.php';

/**
 * About Model
 * 
 * Handles data for the About section
 */
class AboutModel extends Model
{
    /**
     * Get About page data
     * 
     * @return array About page information
     */
    public function getAboutData(): array
    {
        return [
            'content' => 'CyberTirah Framework is a modern, powerful PHP framework inspired by OpenCart and CodeIgniter patterns. Built with PHP 8.3+, it provides developers with familiar patterns, high performance, and comprehensive features for building robust web applications.',
            
            'mission' => 'To provide developers with a powerful, easy-to-use framework that combines the best patterns from popular frameworks with modern PHP features and exceptional performance.',
            
            'vision' => 'To become the go-to PHP framework for developers who value clean code, familiar patterns, and production-ready solutions.',
            
            'stats' => [
                [
                    'number' => '25x',
                    'label' => 'Faster Performance',
                    'description' => 'With route caching enabled'
                ],
                [
                    'number' => '5',
                    'label' => 'Core Components',
                    'description' => 'Router, Registry, Loader, Controller, Model'
                ],
                [
                    'number' => '100%',
                    'label' => 'Backward Compatible',
                    'description' => 'No breaking changes'
                ],
                [
                    'number' => '11+',
                    'label' => 'Documentation Guides',
                    'description' => 'Comprehensive documentation'
                ]
            ],
            
            'team' => $this->getTeamMembers()
        ];
    }
    
    /**
     * Get team members
     * 
     * @return array List of team members
     */
    public function getTeamMembers(): array
    {
        return [
            [
                'name' => 'Development Team',
                'role' => 'Core Framework Developers',
                'bio' => 'Passionate about creating clean, efficient, and maintainable code.',
                'avatar' => '👨‍💻'
            ],
            [
                'name' => 'Design Team',
                'role' => 'UI/UX Designers',
                'bio' => 'Focused on creating intuitive and beautiful user experiences.',
                'avatar' => '🎨'
            ],
            [
                'name' => 'Documentation Team',
                'role' => 'Technical Writers',
                'bio' => 'Ensuring developers have comprehensive guides and references.',
                'avatar' => '📚'
            ],
            [
                'name' => 'Community',
                'role' => 'Contributors',
                'bio' => 'Amazing developers from around the world contributing to the project.',
                'avatar' => '🌍'
            ]
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
                'description' => 'Familiar patterns make migration and learning easy'
            ],
            [
                'icon' => '⚡',
                'title' => 'High Performance',
                'description' => 'Route caching and lazy loading for optimal speed'
            ],
            [
                'icon' => '🔗',
                'title' => 'Named Routes',
                'description' => 'Easy URL generation and management'
            ],
            [
                'icon' => '🎨',
                'title' => 'MVC Architecture',
                'description' => 'Clean separation of concerns'
            ],
            [
                'icon' => '🔧',
                'title' => 'Registry Pattern',
                'description' => 'Centralized service management'
            ],
            [
                'icon' => '📦',
                'title' => 'Component Loader',
                'description' => 'CodeIgniter-style loading system'
            ],
            [
                'icon' => '🛡️',
                'title' => 'Security Built-in',
                'description' => 'CSRF, validation, sanitization'
            ],
            [
                'icon' => '🚀',
                'title' => 'Production Ready',
                'description' => 'All tests passing, fully documented'
            ]
        ];
    }
    
    /**
     * Get timeline/history
     * 
     * @return array Timeline events
     */
    public function getTimeline(): array
    {
        return [
            [
                'year' => '2025',
                'event' => 'v2.0.0 Release',
                'description' => 'Major update with OpenCart/CodeIgniter patterns, enhanced routing, and comprehensive documentation'
            ],
            [
                'year' => '2024',
                'event' => 'Framework Inception',
                'description' => 'Initial development of CyberTirah Framework with modular architecture'
            ]
        ];
    }
}

