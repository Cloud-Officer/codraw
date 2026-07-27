<?php

declare(strict_types=1);

namespace Draw\Bundle\NewRelicBundle\Tests\NewRelic;

use Draw\Bundle\NewRelicBundle\NewRelic\LoggingInteractorDecorator;
use Draw\Bundle\NewRelicBundle\NewRelic\NewRelicInteractorInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @internal
 */
class LoggingInteractorDecoratorTest extends TestCase
{
    #[DataProvider('provideGenericCases')]
    public function testGeneric(string $method, array $arguments, $return): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $decorated = $this->createMock(LoggingInteractorDecorator::class);
        $interactor = new LoggingInteractorDecorator($decorated, $logger);

        $logger->expects($this->once())->method('debug');
        $call = $decorated->expects($this->once())->method($method)
            ->with(...$arguments)
        ;
        if (null !== $return) {
            $call->willReturn($return);
        }

        $result = $interactor->{$method}(...$arguments);

        static::assertSame($return, $result);
    }

    public static function provideGenericCases(): iterable
    {
        $reflection = new \ReflectionClass(NewRelicInteractorInterface::class);
        foreach ($reflection->getMethods() as $method) {
            if (!$method->isPublic()) {
                continue;
            }
            if ($method->isStatic()) {
                continue;
            }

            $arguments = array_map(
                static fn (\ReflectionParameter $parameter) => static::getTypeStub($parameter->getType()),
                $method->getParameters()
            );

            $return = $method->hasReturnType() ? static::getTypeStub($method->getReturnType()) : null;

            yield [$method->getName(), $arguments, $return];
        }
    }

    private static function getTypeStub(?\ReflectionType $type)
    {
        if (null === $type) {
            return uniqid('', true);
        }

        switch ($type->getName()) {
            case 'string':
                return uniqid('', true);
            case 'bool':
                return (bool) random_int(0, 1);
            case 'float':
                return random_int(0, 100) / random_int(1, 10);
            case 'int':
                return random_int(0, 100);
            case 'void':
                return null;
            case 'Throwable':
                return new \Exception();
            case 'callable':
                return static function () {};
            case 'array':
                return array_fill(0, 2, uniqid('', true));
            default:
                throw new \UnexpectedValueException('Unknown type. '.$type->getName());
        }
    }
}
