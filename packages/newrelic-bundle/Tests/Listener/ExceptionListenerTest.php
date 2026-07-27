<?php

declare(strict_types=1);

namespace Draw\Bundle\NewRelicBundle\Tests\Listener;

use Draw\Bundle\NewRelicBundle\Listener\ExceptionListener;
use Draw\Bundle\NewRelicBundle\NewRelic\NewRelicInteractorInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * @internal
 */
class ExceptionListenerTest extends TestCase
{
    public function testOnKernelException(): void
    {
        $exception = new \Exception('Boom');

        $interactor = $this->createMock(NewRelicInteractorInterface::class);
        $interactor->expects($this->once())->method('noticeThrowable')->with($exception);

        $kernel = static::createStub(HttpKernelInterface::class);
        $request = new Request();

        $eventClass = ExceptionEvent::class;
        $event = new $eventClass($kernel, $request, HttpKernelInterface::SUB_REQUEST, $exception);

        $listener = new ExceptionListener($interactor);
        $listener->onKernelException($event);
    }

    public function testOnKernelExceptionWithHttp(): void
    {
        $exception = new BadRequestHttpException('Boom');

        $interactor = $this->createMock(NewRelicInteractorInterface::class);
        $interactor->expects($this->never())->method('noticeThrowable');

        $kernel = static::createStub(HttpKernelInterface::class);
        $request = new Request();

        $eventClass = ExceptionEvent::class;
        $event = new $eventClass($kernel, $request, HttpKernelInterface::SUB_REQUEST, $exception);

        $listener = new ExceptionListener($interactor);
        $listener->onKernelException($event);
    }
}
