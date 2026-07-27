<?php

declare(strict_types=1);

namespace Draw\Bundle\NewRelicBundle\Logging;

use Monolog\Handler\NewRelicHandler;
use Monolog\LogRecord;
use Psr\Log\LogLevel;

class AdaptiveHandler extends NewRelicHandler
{
    public function __construct(
        string $level = LogLevel::ERROR,
        bool $bubble = true,
        ?string $appName = null,
        bool $explodeArrays = false,
        ?string $transactionName = null,
    ) {
        parent::__construct($level, $bubble, $appName, $explodeArrays, $transactionName);
    }

    protected function write(LogRecord $record): void
    {
        if (!$this->isNewRelicEnabled()) {
            return;
        }

        parent::write($record);
    }
}
