<?php

declare(strict_types=1);

namespace Draw\Bundle\NewRelicBundle\Tests\Twig;

use Draw\Bundle\NewRelicBundle\NewRelic\Config;
use Draw\Bundle\NewRelicBundle\NewRelic\NewRelicInteractorInterface;
use Draw\Bundle\NewRelicBundle\Twig\NewRelicExtension;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
class NewRelicExtensionTest extends TestCase
{
    private Config&MockObject $newRelic;

    private NewRelicInteractorInterface&MockObject $interactor;

    protected function setUp(): void
    {
        $this->newRelic = $this->getMockBuilder(Config::class)
            ->onlyMethods(['getCustomMetrics', 'getCustomParameters'])
            ->disableOriginalConstructor()
            ->getMock()
        ;
        $this->interactor = $this->createMock(NewRelicInteractorInterface::class);
    }

    /**
     * Tests the initial values returned by state methods.
     */
    public function testInitialSetup(): void
    {
        $extension = new NewRelicExtension(
            $this->newRelic,
            $this->interactor
        );

        static::assertFalse($extension->isHeaderCalled());
        static::assertFalse($extension->isFooterCalled());
        static::assertFalse($extension->isUsed());
    }

    public function testHeaderException(): void
    {
        $extension = new NewRelicExtension(
            $this->newRelic,
            $this->interactor
        );

        $this->newRelic->expects($this->once())
            ->method('getCustomMetrics')
            ->willReturn([])
        ;

        $this->newRelic->expects($this->once())
            ->method('getCustomParameters')
            ->willReturn([])
        ;

        $this->expectException(\RuntimeException::class);

        $extension->getNewrelicBrowserTimingHeader();
        $extension->getNewrelicBrowserTimingHeader();
    }

    public function testFooterException(): void
    {
        $extension = new NewRelicExtension(
            $this->newRelic,
            $this->interactor
        );

        $this->newRelic->expects($this->once())
            ->method('getCustomMetrics')
            ->willReturn([])
        ;

        $this->newRelic->expects($this->once())
            ->method('getCustomParameters')
            ->willReturn([])
        ;

        $this->expectException(\RuntimeException::class);

        $extension->getNewrelicBrowserTimingHeader();
        $extension->getNewrelicBrowserTimingHeader();
    }

    public function testPreparingOfInteractor(): void
    {
        $headerValue = '__HEADER__TIMING__';
        $footerValue = '__FOOTER__TIMING__';

        $extension = new NewRelicExtension(
            $this->newRelic,
            $this->interactor,
            true
        );

        $this->newRelic->expects($this->once())
            ->method('getCustomMetrics')
            ->willReturn([
                'a' => 'b',
                'c' => 'd',
            ])
        ;

        $this->newRelic->expects($this->once())
            ->method('getCustomParameters')
            ->willReturn([
                'e' => 'f',
                'g' => 'h',
                'i' => 'j',
            ])
        ;

        $this->interactor->expects($this->once())
            ->method('disableAutoRum')
        ;

        $this->interactor->expects($this->exactly(2))
            ->method('addCustomMetric')
        ;

        $this->interactor->expects($this->exactly(3))
            ->method('addCustomParameter')
        ;

        $this->interactor->expects($this->once())
            ->method('getBrowserTimingHeader')
            ->willReturn($headerValue)
        ;

        $this->interactor->expects($this->once())
            ->method('getBrowserTimingFooter')
            ->willReturn($footerValue)
        ;

        static::assertSame($headerValue, $extension->getNewrelicBrowserTimingHeader());
        static::assertTrue($extension->isHeaderCalled());
        static::assertFalse($extension->isFooterCalled());

        static::assertSame($footerValue, $extension->getNewrelicBrowserTimingFooter());
        static::assertTrue($extension->isHeaderCalled());
        static::assertTrue($extension->isFooterCalled());
    }
}
