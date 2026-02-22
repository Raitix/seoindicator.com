<?php

namespace App\Services;

use App\Services\SERP\SERPOptions;
use App\Services\SERP\SERPResult;

interface SERPFetcherInterface
{
    public function fetch(string $keyword, SERPOptions $options): SERPResult;
}
