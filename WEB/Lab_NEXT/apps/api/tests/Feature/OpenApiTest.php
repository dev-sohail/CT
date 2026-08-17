<?php

namespace Tests\Feature;

class OpenApiTest extends FeatureTestCase
{
    public function test_openapi_json_is_served_publicly(): void
    {
        $response = $this->getJson('/api/v1/openapi.json')->assertOk();

        $json = $response->json();
        $this->assertSame('3.0.3', $json['openapi']);
        $this->assertSame('CTLabs Personal Life OS API', $json['info']['title']);
        $this->assertArrayHasKey('/login', $json['paths']);
        $this->assertArrayHasKey('/search', $json['paths']);
        $this->assertArrayHasKey('bearerAuth', $json['components']['securitySchemes']);
    }

    public function test_docs_html_is_served(): void
    {
        $this->get('/api/v1/docs')
            ->assertOk()
            ->assertSee('swagger-ui')
            ->assertSee('CTLabs API Reference');
    }
}
