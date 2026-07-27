<?php

declare(strict_types=1);

namespace Draw\Bundle\NewRelicBundle\Tests\Listener;

use Draw\Bundle\NewRelicBundle\Listener\ResponseListener;
use Draw\Bundle\NewRelicBundle\NewRelic\Config;
use Draw\Bundle\NewRelicBundle\NewRelic\NewRelicInteractorInterface;
use Draw\Bundle\NewRelicBundle\Twig\NewRelicExtension;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * @internal
 */
class ResponseListenerTest extends TestCase
{
    private NewRelicInteractorInterface $interactor;

    private Config $newRelic;

    private NewRelicExtension $extension;

    protected function setUp(): void
    {
        $this->interactor = $this->createMock(NewRelicInteractorInterface::class);
        $this->newRelic = $this->getMockBuilder(Config::class)
            ->onlyMethods(['getCustomEvents', 'getCustomMetrics', 'getCustomParameters'])
            ->disableOriginalConstructor()
            ->getMock()
        ;
        $this->extension = $this->getMockBuilder(NewRelicExtension::class)
            ->onlyMethods(['isHeaderCalled', 'isFooterCalled', 'isUsed'])
            ->disableOriginalConstructor()
            ->getMock()
        ;
    }

    public function testOnKernelResponseOnlyMasterRequestsAreProcessed(): void
    {
        $event = $this->createFilterResponseEventDummy(null, null, HttpKernelInterface::SUB_REQUEST);

        $object = new ResponseListener($this->newRelic, $this->interactor);
        $object->onKernelResponse($event);

        $this->newRelic->expects($this->never())->method('getCustomMetrics');
    }

    public function testOnKernelResponseWithOnlyCustomMetricsAndParameters(): void
    {
        $events = [
            'WidgetSale' => [
                [
                    'color' => 'red',
                    'weight' => 12.5,
                ],
                [
                    'color' => 'blue',
                    'weight' => 12.5,
                ],
            ],
        ];

        $metrics = [
            'foo_a' => 4.7,
            'foo_b' => 11,
        ];

        $parameters = [
            'foo_1' => 'bar_1',
            'foo_2' => 'bar_2',
        ];

        $this->newRelic->expects($this->once())->method('getCustomEvents')->willReturn($events);
        $this->newRelic->expects($this->once())->method('getCustomMetrics')->willReturn($metrics);
        $this->newRelic->expects($this->once())->method('getCustomParameters')->willReturn($parameters);

        $metricCalls = [];
        $this->interactor->expects($this->exactly(2))
            ->method('addCustomMetric')
            ->willReturnCallback(static function (string $name, float $value) use (&$metricCalls): bool {
                $metricCalls[] = [$name, $value];

                return true;
            })
        ;

        $parameterCalls = [];
        $this->interactor->expects($this->exactly(2))
            ->method('addCustomParameter')
            ->willReturnCallback(static function (string $name, $value) use (&$parameterCalls): bool {
                $parameterCalls[] = [$name, $value];

                return true;
            })
        ;

        $eventCalls = [];
        $this->interactor->expects($this->exactly(2))
            ->method('addCustomEvent')
            ->willReturnCallback(static function (string $name, array $attributes) use (&$eventCalls): void {
                $eventCalls[] = [$name, $attributes];
            })
        ;

        $event = $this->createFilterResponseEventDummy();

        $object = new ResponseListener($this->newRelic, $this->interactor, false);
        $object->onKernelResponse($event);

        static::assertSame([['foo_a', 4.7], ['foo_b', 11.0]], $metricCalls);
        static::assertSame([['foo_1', 'bar_1'], ['foo_2', 'bar_2']], $parameterCalls);
        static::assertSame([
            ['WidgetSale', ['color' => 'red', 'weight' => 12.5]],
            ['WidgetSale', ['color' => 'blue', 'weight' => 12.5]],
        ], $eventCalls);
    }

    public function testOnKernelResponseInstrumentDisabledInRequest(): void
    {
        $this->setupNoCustomMetricsOrParameters();

        $this->interactor->expects($this->once())->method('disableAutoRUM');

        $event = $this->createFilterResponseEventDummy();

        $object = new ResponseListener($this->newRelic, $this->interactor, true);
        $object->onKernelResponse($event);
    }

    public function testSymfonyCacheEnabled(): void
    {
        $this->setupNoCustomMetricsOrParameters();

        $this->interactor->expects($this->once())->method('endTransaction');

        $event = $this->createFilterResponseEventDummy();

        $object = new ResponseListener($this->newRelic, $this->interactor, false, true);
        $object->onKernelResponse($event);
    }

    public function testSymfonyCacheDisabled(): void
    {
        $this->setupNoCustomMetricsOrParameters();

        $this->interactor->expects($this->never())->method('endTransaction');

        $event = $this->createFilterResponseEventDummy();

        $object = new ResponseListener($this->newRelic, $this->interactor, false, false);
        $object->onKernelResponse($event);
    }

    #[DataProvider('provideOnKernelResponseOnlyInstrumentHTMLResponsesCases')]
    public function testOnKernelResponseOnlyInstrumentHTMLResponses($content, $expectsSetContent, $contentType): void
    {
        $this->setupNoCustomMetricsOrParameters();

        $this->interactor->expects($this->once())->method('disableAutoRUM');
        $this->interactor->expects($this->any())->method('getBrowserTimingHeader')->willReturn('__Timing_Header__');
        $this->interactor->expects($this->any())->method('getBrowserTimingFooter')->willReturn('__Timing_Feader__');

        $response = $this->createResponseMock($content, $expectsSetContent, $contentType);
        $event = $this->createFilterResponseEventDummy(null, $response);

        $object = new ResponseListener($this->newRelic, $this->interactor, true);
        $object->onKernelResponse($event);
    }

    public static function provideOnKernelResponseOnlyInstrumentHTMLResponsesCases(): iterable
    {
        return [
            // unsupported content types
            [null, null, 'text/xml'],
            [null, null, 'text/plain'],
            [null, null, 'application/json'],

            ['content', 'content', 'text/html'],
            ['<div class="head">head</div>', '<div class="head">head</div>', 'text/html'],
            ['<header>content</header>', '<header>content</header>', 'text/html'],

            // head, body tags
            ['<head><title /></head>', '<head>__Timing_Header__<title /></head>', 'text/html'],
            ['<body><div /></body>', '<body><div />__Timing_Feader__</body>', 'text/html'],
            ['<head><title /></head><body><div /></body>', '<head>__Timing_Header__<title /></head><body><div />__Timing_Feader__</body>', 'text/html'],

            // with charset
            ['<head><title /></head><body><div /></body>', '<head>__Timing_Header__<title /></head><body><div />__Timing_Feader__</body>', 'text/html; charset=UTF-8'],
        ];
    }

    public function testInteractionWithTwigExtensionHeader(): void
    {
        $this->newRelic->expects($this->never())->method('getCustomMetrics');
        $this->newRelic->expects($this->never())->method('getCustomParameters');
        $this->newRelic->expects($this->once())->method('getCustomEvents')->willReturn([]);

        $this->interactor->expects($this->never())->method('disableAutoRUM');
        $this->interactor->expects($this->never())->method('getBrowserTimingHeader');
        $this->interactor->expects($this->once())->method('getBrowserTimingFooter')->willReturn('__Timing_Feader__');

        $this->extension->expects($this->exactly(2))->method('isUsed')->willReturn(true);
        $this->extension->expects($this->once())->method('isHeaderCalled')->willReturn(true);
        $this->extension->expects($this->once())->method('isFooterCalled')->willReturn(false);

        $request = $this->createRequestMock(true);
        $response = $this->createResponseMock('content', 'content', 'text/html');
        $event = $this->createFilterResponseEventDummy($request, $response);

        $object = new ResponseListener($this->newRelic, $this->interactor, true, false, $this->extension);
        $object->onKernelResponse($event);
    }

    public function testInteractionWithTwigExtensionFooter(): void
    {
        $this->newRelic->expects($this->never())->method('getCustomMetrics');
        $this->newRelic->expects($this->never())->method('getCustomParameters');
        $this->newRelic->expects($this->once())->method('getCustomEvents')->willReturn([]);

        $this->interactor->expects($this->never())->method('disableAutoRUM');
        $this->interactor->expects($this->once())->method('getBrowserTimingHeader')->willReturn('__Timing_Feader__');
        $this->interactor->expects($this->never())->method('getBrowserTimingFooter');

        $this->extension->expects($this->exactly(2))->method('isUsed')->willReturn(true);
        $this->extension->expects($this->once())->method('isHeaderCalled')->willReturn(false);
        $this->extension->expects($this->once())->method('isFooterCalled')->willReturn(true);

        $request = $this->createRequestMock(true);
        $response = $this->createResponseMock('content', 'content', 'text/html');
        $event = $this->createFilterResponseEventDummy($request, $response);

        $object = new ResponseListener($this->newRelic, $this->interactor, true, false, $this->extension);
        $object->onKernelResponse($event);
    }

    public function testInteractionWithTwigExtensionHeaderFooter(): void
    {
        $this->newRelic->expects($this->never())->method('getCustomMetrics');
        $this->newRelic->expects($this->never())->method('getCustomParameters');
        $this->newRelic->expects($this->once())->method('getCustomEvents')->willReturn([]);

        $this->interactor->expects($this->never())->method('disableAutoRUM');
        $this->interactor->expects($this->never())->method('getBrowserTimingHeader');
        $this->interactor->expects($this->never())->method('getBrowserTimingFooter');

        $this->extension->expects($this->exactly(2))->method('isUsed')->willReturn(true);
        $this->extension->expects($this->once())->method('isHeaderCalled')->willReturn(true);
        $this->extension->expects($this->once())->method('isFooterCalled')->willReturn(true);

        $request = $this->createRequestMock(true);
        $response = $this->createResponseMock('content', 'content', 'text/html');
        $event = $this->createFilterResponseEventDummy($request, $response);

        $object = new ResponseListener($this->newRelic, $this->interactor, true, false, $this->extension);
        $object->onKernelResponse($event);
    }

    private function setUpNoCustomMetricsOrParameters(): void
    {
        $this->newRelic->expects($this->once())->method('getCustomEvents')->willReturn([]);
        $this->newRelic->expects($this->once())->method('getCustomMetrics')->willReturn([]);
        $this->newRelic->expects($this->once())->method('getCustomParameters')->willReturn([]);

        $this->interactor->expects($this->never())->method('addCustomEvent');
        $this->interactor->expects($this->never())->method('addCustomMetric');
        $this->interactor->expects($this->never())->method('addCustomParameter');
    }

    private function createRequestMock($instrumentEnabled = true): Request
    {
        $mock = $this->getMockBuilder(Request::class)
            ->onlyMethods(['get'])
            ->getMock()
        ;
        $mock->attributes = new ParameterBag(['_instrument' => $instrumentEnabled]);

        $mock->expects($this->any())->method('get')->willReturn($instrumentEnabled);

        return $mock;
    }

    private function createResponseMock(
        $content = null,
        $expectsSetContent = null,
        $contentType = 'text/html',
    ): Response {
        $mock = $this->getMockBuilder(Response::class)
            ->onlyMethods(['getContent', 'setContent'])
            ->getMock()
        ;
        $mock->headers = new ResponseHeaderBag(['Content-Type' => $contentType]);

        $mock->expects($content ? $this->any() : $this->never())->method('getContent')->willReturn($content ?? false);

        if ($expectsSetContent) {
            $setContentCalls = [];
            $mock->expects($this->exactly(2))
                ->method('setContent')
                ->willReturnCallback(function ($content) use (&$setContentCalls, $mock, $expectsSetContent) {
                    $setContentCalls[] = $content;

                    if (2 === \count($setContentCalls)) {
                        $this->assertSame(['', $expectsSetContent], $setContentCalls);
                    }

                    return $mock;
                })
            ;
        } else {
            $mock->expects($this->never())->method('setContent');
        }

        return $mock;
    }

    private function createFilterResponseEventDummy(
        ?Request $request = null,
        ?Response $response = null,
        int $requestType = HttpKernelInterface::MAIN_REQUEST,
    ): ResponseEvent {
        $kernel = static::createStub(HttpKernelInterface::class);

        return new ResponseEvent($kernel, $request ?? new Request(), $requestType, $response ?? new Response());
    }
}
