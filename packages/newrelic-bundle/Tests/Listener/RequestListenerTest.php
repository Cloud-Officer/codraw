<?php

declare(strict_types=1);

namespace Draw\Bundle\NewRelicBundle\Tests\Listener;

use Draw\Bundle\NewRelicBundle\Listener\RequestListener;
use Draw\Bundle\NewRelicBundle\NewRelic\Config;
use Draw\Bundle\NewRelicBundle\NewRelic\NewRelicInteractorInterface;
use Draw\Bundle\NewRelicBundle\TransactionNamingStrategy\TransactionNamingStrategyInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * @internal
 */
class RequestListenerTest extends TestCase
{
    public function testSubRequest(): void
    {
        $interactor = $this->createMock(NewRelicInteractorInterface::class);
        $interactor->expects($this->never())->method('setTransactionName');

        $namingStrategy = $this->createMock(TransactionNamingStrategyInterface::class);

        $kernel = static::createStub(HttpKernelInterface::class);

        $event = new RequestEvent($kernel, new Request(), HttpKernelInterface::SUB_REQUEST);

        $listener = new RequestListener(new Config('App name', 'Token'), $interactor, [], [], $namingStrategy);
        $listener->setApplicationName($event);
    }

    public function testMasterRequest(): void
    {
        $interactor = $this->createMock(NewRelicInteractorInterface::class);
        $interactor->expects($this->once())->method('setTransactionName');

        $namingStrategy = $this->createMock(TransactionNamingStrategyInterface::class);
        $namingStrategy->expects($this->once())->method('getTransactionName')->willReturn('foobar');

        $kernel = static::createStub(HttpKernelInterface::class);

        $event = new RequestEvent($kernel, new Request(), HttpKernelInterface::MAIN_REQUEST);

        $listener = new RequestListener(new Config('App name', 'Token'), $interactor, [], [], $namingStrategy);
        $listener->setTransactionName($event);
    }

    public function testPathIsIgnored(): void
    {
        $interactor = $this->createMock(NewRelicInteractorInterface::class);
        $interactor->expects($this->once())->method('ignoreTransaction');

        $namingStrategy = $this->createMock(TransactionNamingStrategyInterface::class);

        $kernel = static::createStub(HttpKernelInterface::class);
        $request = new Request([], [], [], [], [], ['REQUEST_URI' => '/ignored_path']);

        $event = new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST);

        $listener = new RequestListener(new Config('App name', 'Token'), $interactor, [], ['/ignored_path'], $namingStrategy);
        $listener->setIgnoreTransaction($event);
    }

    public function testRouteIsIgnored(): void
    {
        $interactor = $this->createMock(NewRelicInteractorInterface::class);
        $interactor->expects($this->once())->method('ignoreTransaction');

        $namingStrategy = $this->createMock(TransactionNamingStrategyInterface::class);

        $kernel = static::createStub(HttpKernelInterface::class);
        $request = new Request([], [], ['_route' => 'ignored_route']);

        $event = new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST);

        $listener = new RequestListener(new Config('App name', 'Token'), $interactor, ['ignored_route'], [], $namingStrategy);
        $listener->setIgnoreTransaction($event);
    }

    public function testSymfonyCacheEnabled(): void
    {
        $interactor = $this->createMock(NewRelicInteractorInterface::class);
        $interactor->expects($this->once())->method('startTransaction');

        $namingStrategy = $this->createMock(TransactionNamingStrategyInterface::class);

        $kernel = static::createStub(HttpKernelInterface::class);

        $event = new RequestEvent($kernel, new Request(), HttpKernelInterface::MAIN_REQUEST);

        $listener = new RequestListener(new Config('App name', 'Token'), $interactor, [], [], $namingStrategy, true);
        $listener->setApplicationName($event);
    }

    public function testSymfonyCacheDisabled(): void
    {
        $interactor = $this->createMock(NewRelicInteractorInterface::class);
        $interactor->expects($this->never())->method('startTransaction');

        $namingStrategy = $this->createMock(TransactionNamingStrategyInterface::class);

        $kernel = static::createStub(HttpKernelInterface::class);

        $event = new RequestEvent($kernel, new Request(), HttpKernelInterface::MAIN_REQUEST);

        $listener = new RequestListener(new Config('App name', 'Token'), $interactor, [], [], $namingStrategy, false);
        $listener->setApplicationName($event);
    }
}
