<?php

declare(strict_types=1);

namespace Termwind;

use Closure;
use Symfony\Component\Console\Output\OutputInterface;
use Termwind\Async\Connection;
use Termwind\Async\Task;

final class AsyncHtmlRenderer
{
    protected Closure $task;

    private LiveHtmlRenderer $render;

    private string $failOverHtml = '';

    private int $interval = 0;

    private bool $isRunning = false;

    private bool $requiresSync = false;

    public function __construct(callable $task, int $options = OutputInterface::OUTPUT_NORMAL)
    {
        if (! function_exists('pcntl_fork')) {
            $this->requiresSync = true;
        }
        $this->task = $task(...);
        $this->render = new LiveHtmlRenderer('', $options);
    }

    public function getInterval(): int
    {
        return $this->interval;
    }

    public function getIsRunning(): bool
    {
        return $this->isRunning;
    }

    public function getScreenWidth(): int
    {
        return $this->render->getScreenWidth();
    }

    public function render(string $html): void
    {
        $this->render->reRender($html);
    }

    public function withFailOver(string $html): void
    {
        $this->failOverHtml = $html;
    }

    public function withTask(callable $task): self
    {
        $this->task = $task(...);

        return $this;
    }

    public function run(callable $render, int $us = 1000): mixed
    {
        if ($this->requiresSync) {
            return $this->executeSync($render);
        }

        return $this->executeAsync($render, $us);
    }

    //----------------------------------------------------------------------
    // Sync Fail Over
    //----------------------------------------------------------------------

    public function executeSync(callable $render): mixed
    {
        $this->isRunning = true;
        //Render first time
        $this->renderSync($render);
        //Execute
        $output = ($this->task)();
        $this->isRunning = false;
        //Render again
        $this->renderSync($render);
        if ($output) {
            return $output;
        }

        return true;
    }

    private function renderSync(callable $render): void
    {
        if ($this->failOverHtml !== '') {
            $this->render($this->failOverHtml);
        } else {
            $render();
        }
    }

    //----------------------------------------------------------------------
    // Async Fork methods
    //----------------------------------------------------------------------

    private function executeAsync(callable $render, int $us = 1000): mixed
    {
        $this->isRunning = true;

        $task = Task::set($this->task);
        $forkedTask = $this->forkTask($task);
        while (! $forkedTask->isFinished()) {
            $render();
            $this->interval++;
            usleep($us);
        }
        $this->isRunning = false;
        // Render one last time - in case the user needs getIsRunning() to be false
        $render();

        return $forkedTask->output();
    }

    private function forkTask(Task $task): Task
    {
        [$socketToParent, $socketToChild] = Connection::createPair();

        $processId = pcntl_fork();

        if ($this->currentlyInChildTask($processId)) {
            $socketToChild->close();
            try {
                $this->executeInChildTask($task, $socketToParent);
            } finally {
                $pid = getmypid();
                if ($pid !== false) {
                    posix_kill($pid, SIGKILL);
                }
            }
        }

        $socketToParent->close();
        $task->setPid($processId);
        $task->setConnection($socketToChild);

        return $task;
    }

    private function currentlyInChildTask(int $pid): bool
    {
        return $pid === 0;
    }

    private function executeInChildTask(Task $task, Connection $connectionToParent): void
    {
        $output = $task->execute();
        if (is_bool($output)) {
            $output = (string) $output;
        }
        $connectionToParent->write($output);
        $connectionToParent->close();
    }
}
