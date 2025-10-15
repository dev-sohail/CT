<?php

declare(strict_types=1);

require_once ROOT . '/Brain/Core/Model.php';

/**
 * AI Status Model
 */
class StatusModel extends Model
{
    /**
     * Get AI service status
     */
    public function getStatus(): array
    {
        return [
            'service' => 'CyberTirah AI',
            'status' => 'online',
            'version' => '1.0.0',
            'uptime' => '99.9%',
            'last_check' => date('Y-m-d H:i:s'),
            'endpoints' => [
                'process' => '/ai/process',
                'chat' => '/ai/chat',
                'status' => '/ai/status',
                'capabilities' => '/ai/capabilities'
            ],
            'health' => 'healthy'
        ];
    }
    
    /**
     * Get AI capabilities
     */
    public function getCapabilities(): array
    {
        return [
            'service' => 'CyberTirah AI',
            'capabilities' => [
                'text_generation' => [
                    'enabled' => true,
                    'description' => 'Generate text based on prompts'
                ],
                'chat' => [
                    'enabled' => true,
                    'description' => 'Interactive chat conversations'
                ],
                'sentiment_analysis' => [
                    'enabled' => false,
                    'description' => 'Analyze sentiment of text'
                ],
                'text_classification' => [
                    'enabled' => false,
                    'description' => 'Classify text into categories'
                ],
                'entity_extraction' => [
                    'enabled' => false,
                    'description' => 'Extract entities from text'
                ]
            ],
            'models' => [
                'cybertirah-ai-v1' => [
                    'description' => 'General purpose AI model',
                    'status' => 'active'
                ]
            ],
            'limits' => [
                'max_tokens' => 4000,
                'rate_limit' => '100 requests/minute',
                'max_context_length' => 8000
            ]
        ];
    }
}

