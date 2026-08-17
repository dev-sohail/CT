<?php

namespace App\Domains\DashboardAndWidgetFramework\Contracts;

class WidgetData
{
    public function __construct(
        public readonly string $key,
        public readonly string $title,
        public readonly array $data,
        public readonly ?string $subtitle = null,
        public readonly ?int $refresh_interval = null,
    ) {
    }
}