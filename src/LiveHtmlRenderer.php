<?php

declare(strict_types=1);

namespace Termwind;

use Symfony\Component\Console\Cursor;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Terminal;

/**
 * @internal
 */
final class LiveHtmlRenderer
{
    /**
     * Renders the given html and creates a new Live instance
     */
    public ?string $currentMessage = null;

    protected int $options = OutputInterface::OUTPUT_NORMAL;

    protected HtmlRenderer $htmlRenderer;

    private OutputInterface $output;

    private Cursor $cursor;

    private int $width;

    private int $startingRow = 0;

    private int $endingRow = 0;

    public function __construct(?string $html = null, int $options = OutputInterface::OUTPUT_NORMAL)
    {
        $this->output = new ConsoleOutput;
        $this->htmlRenderer = new HtmlRenderer;
        $this->options = $options;
        $this->cursor = new Cursor($this->output);
        $this->width = (new Terminal)->getWidth();
        if ($html !== null) {
            $this->reRender($html);
        }
    }

    public function getScreenWidth(): int
    {
        return $this->width;
    }

    public function newLine(int $count = 1): void
    {
        $this->output->write(str_repeat(\PHP_EOL, $count));
    }

    public function reRender(string $html): void
    {
        $message = $this->convertHtmlToMessage($html);
        if ($message === $this->currentMessage) {
            return;
        }
        $this->cursor->hide();
        $previousMessage = $this->currentMessage;
        if ($previousMessage === null) {
            $this->captureFirstRow();
        }
        if ($previousMessage !== null) {
            if (strlen($previousMessage) > strlen($message)) {
                /**
                 * The new message is shorter than the previous message, so it needs to be cleared out as
                 * pasting the new render over the previous will leave some of the previous visible.
                 * We only do this if we have to because clearing brings jank.
                 * We hate jank.
                 */
                $this->clearPrevious();
            }
            $this->cursor->moveToPosition(1, $this->startingRow);
        }
        $this->currentMessage = $message;
        $this->htmlRenderer->parse($html)->render($this->options);
        $this->setEndRow($message);

    }

    //----------------------------------------------------------------------
    // Private Methods
    //----------------------------------------------------------------------
    private function setEndRow(?string $message): void
    {
        $this->endingRow = $this->cursor->getCurrentPosition()[1];
        /**
         *  Set the starting row ready for the next render
         *  It may have changed if we hit the bottom
         *  of the terminal window
         */
        $rows = $this->calculateMessageRows($message);
        $moveUp = $rows + 1;
        $this->startingRow = $this->endingRow - $moveUp;
    }

    private function captureFirstRow(): void
    {
        $this->startingRow = $this->cursor->getCurrentPosition()[1];
    }

    private function calculateMessageRows(?string $message): int
    {
        if ($message !== null) {
            return count(explode("\n", $message));
        }

        return 0;
    }

    private function clearPrevious(): void
    {
        $this->cursor->moveToPosition(1, $this->endingRow);
        $rows = $this->endingRow - $this->startingRow;
        for ($i = 0; $i < $rows; $i++) {
            $this->cursor->moveUp();
            $this->cursor->clearLine();
        }

    }

    private function convertHtmlToMessage(string $html): string
    {
        return $this->htmlRenderer->parse($html)->toString();
    }
}
