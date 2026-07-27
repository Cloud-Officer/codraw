<?php

declare(strict_types=1);

namespace Draw\Bundle\NewRelicBundle\TransactionNamingStrategy;

use Symfony\Component\HttpFoundation\Request;

interface TransactionNamingStrategyInterface
{
    public function getTransactionName(Request $request): string;
}
