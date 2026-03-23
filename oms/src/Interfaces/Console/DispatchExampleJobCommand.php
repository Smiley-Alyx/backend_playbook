<?php

declare(strict_types=1);

namespace App\Interfaces\Console;

use App\Infrastructure\Queue\Message\ExampleJob;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsCommand(
    name: 'app:dispatch-example-job',
    description: 'Dispatch ExampleJob message to async transport',
)]
final class DispatchExampleJobCommand extends Command
{
    public function __construct(private readonly MessageBusInterface $bus)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('message', InputArgument::REQUIRED, 'Message payload');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $message = (string) $input->getArgument('message');

        $this->bus->dispatch(new ExampleJob(message: $message, attempt: 0));

        $output->writeln('Dispatched ExampleJob');

        return Command::SUCCESS;
    }
}
