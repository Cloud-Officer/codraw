<?php

declare(strict_types=1);

namespace Draw\Bundle\NewRelicBundle\Tests\Listener;

use Draw\Bundle\NewRelicBundle\Exception\DeprecationException;
use Draw\Bundle\NewRelicBundle\Listener\DeprecationListener;
use Draw\Bundle\NewRelicBundle\NewRelic\NewRelicInteractorInterface;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
class DeprecationListenerTest extends TestCase
{
    public function testDeprecationIsReported(): void
    {
        $interactor = $this->createMock(NewRelicInteractorInterface::class);
        $interactor->expects($this->once())->method('noticeThrowable')->with(
            static::isInstanceOf(DeprecationException::class)
        );

        $listener = new DeprecationListener($interactor);

        set_error_handler(static fn () => false);
        try {
            $listener->register();
            @trigger_error('This is a deprecation', \E_USER_DEPRECATED);
        } finally {
            $listener->unregister();
            restore_error_handler();
        }
    }

    public function testDeprecationIsReportedRegardlessErrorReporting(): void
    {
        $interactor = $this->createMock(NewRelicInteractorInterface::class);
        $interactor->expects($this->once())->method('noticeThrowable');

        $listener = new DeprecationListener($interactor);

        set_error_handler(static fn () => false);
        $e = error_reporting(0);
        try {
            $listener->register();
            @trigger_error('This is a deprecation', \E_USER_DEPRECATED);
        } finally {
            $listener->unregister();
            error_reporting($e);
            restore_error_handler();
        }
    }

    public function testOtherErrorAreIgnored(): void
    {
        $interactor = $this->createMock(NewRelicInteractorInterface::class);
        $interactor->expects($this->never())->method('noticeThrowable');

        $listener = new DeprecationListener($interactor);

        set_error_handler(static fn () => false);
        try {
            $listener->register();
            @trigger_error('This is a notice', \E_USER_NOTICE);
        } finally {
            $listener->unregister();
            restore_error_handler();
        }
    }

    public function testInitialHandlerIsCalled(): void
    {
        $interactor = $this->createMock(NewRelicInteractorInterface::class);
        $interactor->expects($this->once())->method('noticeThrowable');

        $handler = $this->createPartialMock(DummyHandler::class, ['__invoke']);
        $handler->expects($this->once())->method('__invoke');

        $listener = new DeprecationListener($interactor);

        set_error_handler($handler);
        try {
            $listener->register();
            @trigger_error('This is a deprecation', \E_USER_DEPRECATED);
        } finally {
            $listener->unregister();
            restore_error_handler();
        }
    }

    public function testUnregisterRemovesHandler(): void
    {
        $interactor = $this->createMock(NewRelicInteractorInterface::class);
        $interactor->expects($this->never())->method('noticeThrowable');

        $listener = new DeprecationListener($interactor);

        set_error_handler(static fn () => false);
        try {
            $listener->register();
            $listener->unregister();
            @trigger_error('This is a deprecation', \E_USER_DEPRECATED);
        } finally {
            restore_error_handler();
        }
    }

    public function testUnregisterRestorePreviousHandler(): void
    {
        $interactor = $this->createMock(NewRelicInteractorInterface::class);

        $handler = $this->createPartialMock(DummyHandler::class, ['__invoke']);
        $handler->expects($this->once())->method('__invoke');

        $listener = new DeprecationListener($interactor);

        set_error_handler($handler);
        try {
            $listener->register();
            $listener->unregister();
            @trigger_error('This is a deprecation', \E_USER_DEPRECATED);
        } finally {
            restore_error_handler();
        }
    }
}

class DummyHandler
{
    public function __invoke(int $errno, string $errstr, string $errfile, int $errline): bool
    {
        return false;
    }
}
