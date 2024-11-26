<?php

declare(strict_types=1);

namespace Termwind\Html;

use Termwind\Components\Element;
use Termwind\Contracts\Renderer;
use Termwind\Exceptions\InvalidRenderer;
use Termwind\ValueObjects\Node;

/**
 * @interal
 */
final class ElementRenderer
{
    /**
     * @var array<string, class-string<Renderer>>
     */
    private static array $renderers = [
        'table' => TableRenderer::class,
        'code' => CodeRenderer::class,
        'pre' => PreRenderer::class,
    ];

    /**
     * @return array<string, class-string<Renderer>>
     */
    public static function renderers(): array
    {
        return self::$renderers;
    }

    /**
     * Checks if there is any renderer registered for the node name
     *
     * @param  string  $name
     * @return bool
     */
    public static function hasRenderer(string $name): bool
    {
        return array_key_exists($name, self::$renderers);
    }

    /**
     * Registers a new renderer
     *
     * @param  string  $name
     * @param  string  $renderer
     * @return void
     *
     * @thows InvalidRenderer
     */
    public static function register(string $name, string $renderer): void
    {
        if (! is_a($renderer, Renderer::class, true)) {
            throw new InvalidRenderer();
        }

        self::$renderers[$name] = $renderer;
    }

    /**
     * Renders the given Node
     *
     * @param  Node  $node
     * @return Element
     *
     * @thows InvalidRenderer
     */
    public static function render(Node $node): Element
    {
        $nodeName = $node->getName();

        if (! self::hasRenderer($nodeName)) {
            throw new InvalidRenderer();
        }

        /** @var Renderer $renderer */
        $renderer = (new self::$renderers[$nodeName]);

        return $renderer->toElement($node);
    }
}
