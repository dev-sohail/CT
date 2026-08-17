<?php

namespace App\Domains\SharedRestApiGateway\OpenApi;

class OpenApiSpec
{
    public function build(): array
    {
        return [
            'openapi' => '3.0.3',
            'info' => [
                'title' => 'CTLabs Personal Life OS API',
                'version' => 'v1',
                'description' => 'REST API for the CTLabs personal life operating system. Every endpoint returns the envelope `{data, meta, errors}`. Authenticate with `Authorization: Bearer <token>` from `POST /api/v1/login`.',
            ],
            'servers' => [
                ['url' => '/api/v1', 'description' => 'Current host'],
            ],
            'security' => [
                ['bearerAuth' => []],
            ],
            'components' => [
                'securitySchemes' => [
                    'bearerAuth' => [
                        'type' => 'http',
                        'scheme' => 'bearer',
                        'bearerFormat' => 'sanctum-token',
                    ],
                ],
                'schemas' => $this->schemas(),
            ],
            'paths' => $this->paths(),
        ];
    }

    private function schemas(): array
    {
        return [
            'Envelope' => [
                'type' => 'object',
                'properties' => [
                    'data' => ['nullable' => true],
                    'meta' => ['type' => 'object', 'properties' => ['version' => ['type' => 'string'], 'timestamp' => ['type' => 'string']]],
                    'errors' => ['nullable' => true],
                ],
            ],
            'ErrorEnvelope' => [
                'type' => 'object',
                'properties' => [
                    'data' => ['type' => 'null'],
                    'meta' => ['type' => 'object'],
                    'errors' => [
                        'type' => 'object',
                        'properties' => [
                            'code' => ['type' => 'integer'],
                            'message' => ['type' => 'string'],
                            'details' => ['nullable' => true],
                        ],
                    ],
                ],
            ],
            'Pagination' => [
                'type' => 'object',
                'properties' => [
                    'current_page' => ['type' => 'integer'],
                    'per_page' => ['type' => 'integer'],
                    'from' => ['type' => 'integer', 'nullable' => true],
                    'to' => ['type' => 'integer', 'nullable' => true],
                    'total' => ['type' => 'integer'],
                    'last_page' => ['type' => 'integer'],
                ],
            ],
            'LoginRequest' => [
                'type' => 'object',
                'required' => ['email', 'password'],
                'properties' => [
                    'email' => ['type' => 'string', 'format' => 'email'],
                    'password' => ['type' => 'string', 'format' => 'password'],
                ],
            ],
            'Page' => [
                'type' => 'object',
                'properties' => [
                    'id' => ['type' => 'integer'],
                    'section_id' => ['type' => 'integer', 'nullable' => true],
                    'project_id' => ['type' => 'integer', 'nullable' => true],
                    'title' => ['type' => 'string'],
                    'content' => ['type' => 'string', 'nullable' => true],
                    'status' => ['type' => 'string'],
                    'is_favorite' => ['type' => 'boolean'],
                ],
            ],
            'SearchResult' => [
                'type' => 'object',
                'properties' => [
                    'id' => ['type' => 'integer'],
                    'model_type' => ['type' => 'string'],
                    'model_id' => ['type' => 'integer'],
                    'title' => ['type' => 'string'],
                    'snippet' => ['type' => 'string', 'nullable' => true],
                ],
            ],
            'Document' => [
                'type' => 'object',
                'properties' => [
                    'id' => ['type' => 'integer'],
                    'name' => ['type' => 'string'],
                    'mime_type' => ['type' => 'string', 'nullable' => true],
                    'size' => ['type' => 'integer'],
                    'folder' => ['type' => 'string', 'nullable' => true],
                    'current_version' => ['type' => 'integer'],
                ],
            ],
            'Tag' => [
                'type' => 'object',
                'properties' => [
                    'id' => ['type' => 'integer'],
                    'name' => ['type' => 'string'],
                    'slug' => ['type' => 'string'],
                    'color' => ['type' => 'string', 'nullable' => true],
                    'count' => ['type' => 'integer', 'nullable' => true],
                ],
            ],
            'Notification' => [
                'type' => 'object',
                'properties' => [
                    'id' => ['type' => 'string'],
                    'type' => ['type' => 'string'],
                    'data' => ['type' => 'object'],
                    'read_at' => ['type' => 'string', 'format' => 'date-time', 'nullable' => true],
                    'created_at' => ['type' => 'string', 'format' => 'date-time'],
                ],
            ],
            'Setting' => [
                'type' => 'object',
                'properties' => [
                    'id' => ['type' => 'integer'],
                    'key' => ['type' => 'string'],
                    'value' => ['nullable' => true],
                    'updated_at' => ['type' => 'string', 'format' => 'date-time'],
                ],
            ],
            'AuditLog' => [
                'type' => 'object',
                'properties' => [
                    'id' => ['type' => 'integer'],
                    'action' => ['type' => 'string'],
                    'subject_type' => ['type' => 'string', 'nullable' => true],
                    'subject_id' => ['type' => 'integer', 'nullable' => true],
                    'created_at' => ['type' => 'string', 'format' => 'date-time'],
                ],
            ],
        ];
    }

    private function paths(): array
    {
        return [
            '/login' => [
                'post' => [
                    'summary' => 'Authenticate and receive a bearer token',
                    'security' => [],
                    'tags' => ['Auth'],
                    'requestBody' => ['required' => true, 'content' => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/LoginRequest']]]],
                    'responses' => ['200' => ['description' => 'Authenticated', 'content' => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/Envelope']]]]],
                ],
            ],
            '/me' => [
                'get' => [
                    'summary' => 'Current authenticated user',
                    'tags' => ['Auth'],
                    'responses' => ['200' => ['description' => 'User profile']],
                ],
            ],
            '/search' => [
                'get' => [
                    'summary' => 'Unified cross-module search',
                    'tags' => ['Search'],
                    'parameters' => [
                        ['name' => 'q', 'in' => 'query', 'required' => true, 'schema' => ['type' => 'string', 'minLength' => 2]],
                        ['name' => 'type', 'in' => 'query', 'schema' => ['type' => 'string']],
                        ['name' => 'limit', 'in' => 'query', 'schema' => ['type' => 'integer', 'maximum' => 100]],
                    ],
                    'responses' => ['200' => ['description' => 'Ranked results', 'content' => ['application/json' => ['schema' => ['type' => 'object', 'properties' => ['data' => ['type' => 'array', 'items' => ['$ref' => '#/components/schemas/SearchResult']]]]]]]],
                ],
            ],
            '/settings' => [
                'get' => ['summary' => 'List all settings', 'tags' => ['Settings'], 'responses' => ['200' => ['description' => 'Settings']]],
                'post' => ['summary' => 'Batch set settings', 'tags' => ['Settings'], 'requestBody' => ['content' => ['application/json' => ['schema' => ['type' => 'object', 'properties' => ['settings' => ['type' => 'array', 'items' => ['type' => 'object']]]]]]], 'responses' => ['200' => ['description' => 'Updated settings']]],
            ],
            '/settings/{key}' => [
                'get' => ['summary' => 'Read one setting', 'tags' => ['Settings'], 'parameters' => [['name' => 'key', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'string']]], 'responses' => ['200' => ['description' => 'Setting'], '404' => ['description' => 'Not found']]],
                'put' => ['summary' => 'Upsert one setting', 'tags' => ['Settings'], 'parameters' => [['name' => 'key', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'string']]], 'requestBody' => ['content' => ['application/json' => ['schema' => ['type' => 'object', 'required' => ['value'], 'properties' => ['value' => []]]]]], 'responses' => ['200' => ['description' => 'Setting saved']]],
                'delete' => ['summary' => 'Delete one setting', 'tags' => ['Settings'], 'parameters' => [['name' => 'key', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'string']]], 'responses' => ['204' => ['description' => 'Deleted']]],
            ],
            '/notifications' => [
                'get' => ['summary' => 'List notifications', 'tags' => ['Notifications'], 'responses' => ['200' => ['description' => 'Notifications', 'content' => ['application/json' => ['schema' => ['type' => 'object', 'properties' => ['data' => ['type' => 'array', 'items' => ['$ref' => '#/components/schemas/Notification']]]]]]]]],
            ],
            '/notifications/read-all' => [
                'post' => ['summary' => 'Mark all notifications read', 'tags' => ['Notifications'], 'responses' => ['204' => ['description' => 'Done']]],
            ],
            '/documents' => [
                'get' => ['summary' => 'List documents', 'tags' => ['Documents'], 'responses' => ['200' => ['description' => 'Documents']]],
                'post' => ['summary' => 'Upload a document', 'tags' => ['Documents'], 'requestBody' => ['required' => true, 'content' => ['multipart/form-data' => ['schema' => ['type' => 'object', 'required' => ['file'], 'properties' => ['file' => ['type' => 'string', 'format' => 'binary'], 'folder' => ['type' => 'string'], 'name' => ['type' => 'string']]]]]], 'responses' => ['201' => ['description' => 'Created', 'content' => ['application/json' => ['schema' => ['type' => 'object', 'properties' => ['data' => ['$ref' => '#/components/schemas/Document']]]]]]]],
            ],
            '/documents/{document}/download' => [
                'get' => ['summary' => 'Download current version', 'tags' => ['Documents'], 'parameters' => [['name' => 'document', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer']]], 'responses' => ['200' => ['description' => 'Binary stream']]],
            ],
            '/tags' => [
                'get' => ['summary' => 'List tags with usage counts', 'tags' => ['Tags'], 'responses' => ['200' => ['description' => 'Tags']]],
                'post' => ['summary' => 'Create a tag', 'tags' => ['Tags'], 'requestBody' => ['content' => ['application/json' => ['schema' => ['type' => 'object', 'required' => ['name'], 'properties' => ['name' => ['type' => 'string'], 'color' => ['type' => 'string']]]]]], 'responses' => ['201' => ['description' => 'Created', 'content' => ['application/json' => ['schema' => ['type' => 'object', 'properties' => ['data' => ['$ref' => '#/components/schemas/Tag']]]]]]]],
            ],
            '/tags/attach' => [
                'post' => ['summary' => 'Attach tags to any taggable model', 'tags' => ['Tags'], 'requestBody' => ['content' => ['application/json' => ['schema' => ['type' => 'object', 'required' => ['taggable_type', 'taggable_id', 'names'], 'properties' => ['taggable_type' => ['type' => 'string'], 'taggable_id' => ['type' => 'integer'], 'names' => ['type' => 'array', 'items' => ['type' => 'string']]]]]]], 'responses' => ['200' => ['description' => 'Updated tags']]],
            ],
            '/tags/detach' => [
                'post' => ['summary' => 'Detach tags from a taggable model', 'tags' => ['Tags'], 'requestBody' => ['content' => ['application/json' => ['schema' => ['type' => 'object', 'required' => ['taggable_type', 'taggable_id', 'names'], 'properties' => ['taggable_type' => ['type' => 'string'], 'taggable_id' => ['type' => 'integer'], 'names' => ['type' => 'array', 'items' => ['type' => 'string']]]]]]], 'responses' => ['200' => ['description' => 'Remaining tags']]],
            ],
            '/audit-log' => [
                'get' => ['summary' => 'List audit trail', 'tags' => ['Audit'], 'parameters' => [['name' => 'action', 'in' => 'query', 'schema' => ['type' => 'string']], ['name' => 'subject_type', 'in' => 'query', 'schema' => ['type' => 'string']]], 'responses' => ['200' => ['description' => 'Audit entries', 'content' => ['application/json' => ['schema' => ['type' => 'object', 'properties' => ['data' => ['type' => 'array', 'items' => ['$ref' => '#/components/schemas/AuditLog']]]]]]]]],
            ],
        ];
    }
}
