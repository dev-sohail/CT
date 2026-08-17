<?php

namespace App\Domains\SharedRestApiGateway\Http\Controllers;

use App\Domains\SharedRestApiGateway\OpenApi\OpenApiSpec;
use Illuminate\Http\JsonResponse;

class OpenApiController extends ApiController
{
    public function __construct(private readonly OpenApiSpec $spec) {}

    public function json(): JsonResponse
    {
        return response()->json($this->spec->build());
    }

    public function html()
    {
        $url = route('api.docs.json');

        return response(
            <<<HTML
            <!doctype html>
            <html lang="en">
            <head>
              <meta charset="utf-8">
              <meta name="viewport" content="width=device-width, initial-scale=1">
              <title>CTLabs API Reference</title>
              <link rel="stylesheet" href="https://unpkg.com/swagger-ui-dist@5/swagger-ui.css">
              <style>body{margin:0}</style>
            </head>
            <body>
              <div id="swagger-ui"></div>
              <script src="https://unpkg.com/swagger-ui-dist@5/swagger-ui-bundle.js"></script>
              <script>
                SwaggerUIBundle({
                  url: {$url},
                  dom_id: '#swagger-ui',
                  deepLinking: true,
                  persistAuthorization: true
                });
              </script>
            </body>
            </html>
            HTML,
            200,
            ['Content-Type' => 'text/html']
        );
    }
}
