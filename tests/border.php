<?php

test('bordered', function () {
    $html = parse(<<<'HTML'
        <div class="border-black">
            Hello World!
        </div>
    HTML);

    expect($html)->toBe(<<<'TEXT'
    ╭──────────────────╮
    │                  │
    │   Hello World!   │
    │                  │
    ╰──────────────────╯
    TEXT);
});
