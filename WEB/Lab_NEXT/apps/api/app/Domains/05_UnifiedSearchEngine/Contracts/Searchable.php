<?php

namespace App\Domains\UnifiedSearchEngine\Contracts;

interface Searchable
{
    public function searchOwnerId(): int;

    public function searchIndexTitle(): string;

    public function searchIndexContent(): string;

    public function searchIndexWeight(): int;
}
