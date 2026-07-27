<?php

declare(strict_types=1);

namespace Draw\Bundle\NewRelicBundle\TransactionNamingStrategy;

use Symfony\Component\HttpFoundation\Request;

/**
 * @author Magnus Nordlander
 */
class RouteNamingStrategy implements TransactionNamingStrategyInterface
{
    public function getTransactionName(Request $request): string
    {
        return $request->get('_route') ?: 'Unknown Symfony route';
    }
}
