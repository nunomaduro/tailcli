<?php

declare(strict_types=1);

namespace Termwind\Actions;

use Termwind\Exceptions\StyleNotFound;
use Termwind\Repositories\Styles as StyleRepository;
use Termwind\Terminal;
use Termwind\ValueObjects\Styles;

/**
 * @internal
 */
final class ApplyBorder
{
    private const BORDER_SQUARE = [
        'tl' => '┌',
        'tr' => '┐',
        'bl' => '└',
        'br' => '┘',
    ];

    private const BORDER_ROUNDED = [
        'tl' => '╭',
        'tr' => '╮',
        'bl' => '╰',
        'br' => '╯',
    ];

    // Todo support text alignment

    public static function format(string $text, int $padding = 2, bool $rounded = true): string
    {
        $lines = explode("\n", $text);

        $width = max(array_map('strlen', $lines)) + ($padding * 2) + 2;

        $borderStyle = $rounded ? self::BORDER_ROUNDED : self::BORDER_SQUARE;
        $header = $borderStyle['tl'] . str_repeat('─', $width) . $borderStyle['tr'];
        $footer = $borderStyle['bl'] . str_repeat('─', $width) . $borderStyle['br'];

        $lines = array_map(function (string $line) use ($width): string {
            return self::formatLine($line, $width - 2);
        }, $lines);

        $verticalPadding = (int) round($padding / 2);
        if ($verticalPadding > 0) {
            $lines = array_merge(array_fill(0, $verticalPadding, self::formatLine('', $width - 2)), $lines);
            $lines = array_merge($lines, array_fill(0, $verticalPadding, self::formatLine('', $width - 2)));
        }

        return implode("\n", array_merge([$header], $lines, [$footer]));
    }

    private static function formatLine(string $line, int $width): string
    {
        $line = str_pad($line, $width, ' ', STR_PAD_BOTH);

        return '│ ' . $line . ' │';
    }
}
