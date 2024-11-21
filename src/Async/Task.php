<?php

declare(strict_types=1);

namespace Termwind\Async;

use Closure;

final class Task
{
    protected const SERIALIZATION_TOKEN = '[[serialized::';

    protected int $pid;

    protected int $status;

    protected Connection $connection;

    protected ?Closure $successCallback = null;

    protected Closure $callable;

    protected string $output = '';

    public static function set(callable $callable): self
    {
        return new self($callable);
    }

    public function __construct(callable $callable)
    {
        $this->callable = $callable(...);
    }

    public function setConnection(Connection $connection): self
    {
        $this->connection = $connection;

        return $this;
    }

    public function execute(): string|bool
    {
        $output = ($this->callable)();

        if (is_string($output)) {
            return $output;
        }

        return self::SERIALIZATION_TOKEN.serialize($output);
    }

    public function output(): mixed
    {
        foreach ($this->connection->read() as $output) {
            $this->output .= $output;
        }

        $this->connection->close();

        $output = $this->output;

        if (str_starts_with($output, self::SERIALIZATION_TOKEN)) {
            $output = unserialize(
                substr($output, strlen(self::SERIALIZATION_TOKEN))
            );
        }
        if ($output === null) {
            return true;
        }

        return $output;
    }

    public function pid(): int
    {
        return $this->pid;
    }

    public function setPid(int $pid): self
    {
        $this->pid = $pid;

        return $this;
    }

    public function isFinished(): bool
    {
        $this->output .= $this->connection->read()->current();

        $status = pcntl_waitpid($this->pid(), $status, WNOHANG | WUNTRACED);

        if ($status === $this->pid) {
            return true;
        }

        if ($status !== 0) {
            throw new TaskException('Could not manage async task');
        }

        return false;
    }
}
