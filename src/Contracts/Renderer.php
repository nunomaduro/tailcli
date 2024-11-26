<?php

declare(strict_types=1);

namespace Termwind\Contracts;

use Termwind\Components\Element;
use Termwind\ValueObjects\Node;

interface Renderer
{
    /**
     * Gets HTML content from a given node and converts to the content element.
     */
    public function toElement(Node $node): Element;
}
