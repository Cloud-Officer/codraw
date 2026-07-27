<?php

declare(strict_types=1);

namespace Draw\Bundle\NewRelicBundle\Tests\Listener;

use Draw\Bundle\NewRelicBundle\Listener\CommandListener;
use Draw\Bundle\NewRelicBundle\NewRelic\Config;
use Draw\Bundle\NewRelicBundle\NewRelic\NewRelicInteractorInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Event\ConsoleErrorEvent;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @internal
 */
class CommandListenerTest extends TestCase
{
    public function testCommandMarkedAsBackgroundJob(): void
    {
        $parameters = [
            '--foo' => true,
            '--foobar' => ['baz', 'baz_2'],
            'name' => 'bar',
        ];

        $definition = new InputDefinition([
            new InputOption('foo'),
            new InputOption('foobar', 'fb', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY),
            new InputArgument('name', InputArgument::REQUIRED),
        ]);

        $interactor = $this->createMock(NewRelicInteractorInterface::class);
        $interactor->expects($this->once())->method('setTransactionName')->with(static::equalTo('test:newrelic'));
        $interactor->expects($this->once())->method('enableBackgroundJob');

        $parameterCalls = [];
        $interactor->expects($this->exactly(4))
            ->method('addCustomParameter')
            ->willReturnCallback(static function (string $name, $value) use (&$parameterCalls): bool {
                $parameterCalls[] = [$name, $value];

                return true;
            })
        ;

        $command = new Command('test:newrelic');
        $input = new ArrayInput($parameters, $definition);

        $output = static::createStub(OutputInterface::class);

        $event = new ConsoleCommandEvent($command, $input, $output);

        $listener = new CommandListener(new Config('App name', 'Token'), $interactor, []);
        $listener->onConsoleCommand($event);

        static::assertSame([
            ['--foo', true],
            ['--foobar[0]', 'baz'],
            ['--foobar[1]', 'baz_2'],
            ['name', 'bar'],
        ], $parameterCalls);
    }

    public function testIgnoreBackgroundJob(): void
    {
        $interactor = $this->createMock(NewRelicInteractorInterface::class);
        $interactor->expects($this->never())->method('startTransaction');

        $command = new Command('test:ignored-commnand');
        $input = new ArrayInput([], new InputDefinition([]));

        $output = static::createStub(OutputInterface::class);

        $event = new ConsoleCommandEvent($command, $input, $output);

        $listener = new CommandListener(new Config('App name', 'Token'), $interactor, ['test:ignored-command']);
        $listener->onConsoleCommand($event);
    }

    public function testConsoleError(): void
    {
        $exception = new \Exception('', 1);

        $newrelic = $this->createMock(Config::class);
        $interactor = $this->createMock(NewRelicInteractorInterface::class);
        $interactor->expects($this->once())->method('noticeThrowable')->with($exception);

        $command = new Command('test:exception');

        $input = new ArrayInput([], new InputDefinition([]));
        $output = static::createStub(OutputInterface::class);

        $event = new ConsoleErrorEvent($input, $output, $exception, $command);

        $listener = new CommandListener($newrelic, $interactor, ['test:exception']);
        $listener->onConsoleError($event);
    }

    public function testConsoleErrorsWithThrowable(): void
    {
        $exception = new \Error();

        $newrelic = $this->createMock(Config::class);
        $interactor = $this->createMock(NewRelicInteractorInterface::class);
        $interactor->expects($this->once())->method('noticeThrowable')->with($exception);
        $command = new Command('test:exception');

        $input = new ArrayInput([], new InputDefinition([]));
        $output = static::createStub(OutputInterface::class);

        $event = new ConsoleErrorEvent($input, $output, $exception, $command);

        $listener = new CommandListener($newrelic, $interactor, ['test:exception']);
        $listener->onConsoleError($event);
    }
}
