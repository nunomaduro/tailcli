<?php

use Termwind\Components\Element;
use Termwind\Contracts\Renderer;
use Termwind\Exceptions\InvalidRenderer;
use Termwind\Html\ElementRenderer;
use function Termwind\registerRenderer;
use Termwind\Termwind;
use Termwind\ValueObjects\Node;

it('checks that default renderers are registered', function ($name) {
    expect(ElementRenderer::hasRenderer($name))
        ->toBeTrue();
})->with(['code', 'pre', 'table']);

it('adds valid custom renderer', function () {
    expect(ElementRenderer::hasRenderer('custom'))
        ->toBeFalse();

    registerRenderer('custom', CustomRenderer::class);

    expect(ElementRenderer::hasRenderer('custom'))
        ->toBeTrue();
});

it('throws exception when adding invalid custom renderer', function () {
    $this->expectException(InvalidRenderer::class);

    registerRenderer('foo', 'bar');
});

final class CustomRenderer implements Renderer
{
    public function toElement(Node $node): Element
    {
        $lines = explode("\n", $node->getHtml());
        if (reset($lines) === '') {
            array_shift($lines);
        }

        if (end($lines) === '') {
            array_pop($lines);
        }

        $maxStrLen = array_reduce(
            $lines,
            static fn (int $max, string $line) => ($max < strlen($line)) ? strlen($line) : $max,
            0
        );

        $styles = $node->getClassAttribute();
        $html = array_map(
            static fn (string $line) => (string) Termwind::div(str_pad($line, $maxStrLen + 3), $styles),
            $lines
        );

        return Termwind::raw(
            implode('', $html)
        );
    }
}
