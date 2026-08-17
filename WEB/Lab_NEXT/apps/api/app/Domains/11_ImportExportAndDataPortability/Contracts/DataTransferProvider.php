<?php

namespace App\Domains\ImportExportAndDataPortability\Contracts;

use App\Domains\CoreIdentityAndAccessKernel\Models\User;

interface DataTransferProvider
{
    public function domain(): string;

    public function export(User $user): array;

    public function import(User $user, array $data): array;
}