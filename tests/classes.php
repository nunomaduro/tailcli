<?php

use Termwind\Exceptions\ColorNotFound;
use Termwind\Exceptions\InvalidStyle;

use function Termwind\parse;

test('font bold', function () {
    $html = parse('<div class="font-bold">text</div>');

    expect($html)->toBe('<options=bold>text</>');
});

test('font normal', function () {
    $html = parse(<<<'HTML'
        <div class="font-bold">
            <div class="font-normal">text</div>
        </div>
    HTML);

    expect($html)->toBe('<options=bold><options=,>text</></>');
});

test('strong', function () {
    $html = parse('<div class="strong">text</div>');

    expect($html)->toBe("\e[1mtext\e[0m");
});

test('italic', function () {
    $html = parse('<div class="italic">text</div>');

    expect($html)->toBe("\e[3mtext\e[0m");
});

test('underline', function () {
    $html = parse('<div class="underline">text</div>');

    expect($html)->toBe("\e[4mtext\e[0m");
});

test('pl', function () {
    $html = parse('<div class="pl-2">text</div>');

    expect($html)->toBe('  text');
});

test('pr', function () {
    $html = parse('<div class="pr-2">text</div>');

    expect($html)->toBe('text  ');
});

test('px', function () {
    $html = parse('<div class="px-2">text</div>');

    expect($html)->toBe('  text  ');
});

test('pt', function () {
    $html = parse('<div class="pt-1">text</div>');
    expect($html)->toBe("    \ntext");
});

test('pb', function () {
    $html = parse('<div class="pb-1">text</div>');

    expect($html)->toBe("text\n    ");
});

test('py', function () {
    $html = parse('<div class="py-1">text</div>');

    expect($html)->toBe("    \ntext\n    ");
});

test('p', function () {
    $html = parse('<div class="p-1">text</div>');
    expect($html)->toBe("      \n text \n      ");
});

test('space-y', function () {
    $html = parse(<<<'HTML'
        <div class="space-y-2">
            <div>1</div>
            <div>2</div>
            <div>3</div>
        </div>
    HTML);

    expect($html)->toBe("1\n\n\n2\n\n\n3");
});

test('space-x', function () {
    $html = parse(<<<'HTML'
        <div class="space-x-2">
            <span>1</span>
            <span>2</span>
            <span>3</span>
        </div>
    HTML);

    expect($html)->toBe('1  2  3');
});

test('bg', function () {
    $html = parse('<div class="bg-red">text</div>');

    expect($html)->toBe('<bg=red>text</>');
});

test('bg-bright', function () {
    $html = parse('<div class="bg-brightred">text</div>');

    expect($html)->toBe('<bg=bright-red>text</>');
});

test('bg-color', function () {
    $html = parse('<div class="bg-red-400">text</div>');

    expect($html)->toBe('<bg=#f87171>text</>');
});

test('text-color', function () {
    $html = parse('<div class="text-red">text</div>');

    expect($html)->toBe('<fg=red>text</>');
});

test('text-color-bright', function () {
    $html = parse('<div class="text-brightred">text</div>');

    expect($html)->toBe('<fg=bright-red>text</>');
});

test('text-right', function () {
    $html = parse('<div class="w-10 text-right">text</div>');

    expect($html)->toBe('      text');
});

test('text-center', function () {
    $html = parse('<div class="w-9 text-center">text</div>');

    expect($html)->toBe('  text   ');
});

test('truncate', function () {
    $html = parse(<<<'HTML'
        <span>
            <span class="truncate-5">text text</span>
            <span class="truncate-20">text text</span>
        </span>
    HTML);

    expect($html)->toBe('text…text text');
});

test('truncate without params', function () {
    $html = parse(<<<'HTML'
        <span class="truncate w-5">truncate</span>
    HTML);

    expect($html)->toBe('trun…');
});

test('truncate without params and width', function () {
    $html = parse(<<<'HTML'
        <span class="truncate">text</span>
    HTML);

    expect($html)->toBe('text');
});

test('truncate with styled childs', function () {
    $html = parse(<<<'HTML'
        <span class="truncate w-12">text with <span class="bg-gray">style</span></span>
    HTML);

    expect($html)->toBe('text with <bg=gray>s</>…');
});

test('truncate with paddings', function () {
    $html = parse(<<<'HTML'
        <span class="truncate w-5 px-1">text</span>
    HTML);

    expect($html)->toBe(' te… ');
});

test('truncate with w-full', function () {
    putenv('COLUMNS=5');

    $html = parse(<<<'HTML'
        <span class="w-full truncate">texttext</span>
    HTML);

    expect($html)->toBe('text…');
});

test('truncate with w-division', function () {
    putenv('COLUMNS=6');

    $html = parse(<<<'HTML'
        <div class="w-full">
            <span class="w-1/2 truncate">texttext</span>
        </div>
    HTML);

    expect($html)->toBe('te…   ');
});

test('w', function () {
    $html = parse(<<<'HTML'
        <span>
            <span class="w-5">text-ignored</span>
            <span class="w-10">text text</span>
        </span>
    HTML);

    expect($html)->toBe('text-text text ');
});

test('w-0', function () {
    $html = parse(<<<'HTML'
        <span class="w-0">ABC</span>
    HTML);

    expect($html)->toBe('');
});

test('w-division', function () {
    putenv('COLUMNS=20');
    $html = parse(<<<'HTML'
        <span>
            <span class="w-1/2">text</span>
            <span class="w-1/2">text</span>
        </span>
    HTML);

    expect($html)->toBe('text      text      ');
});

test('w-auto', function () {
    $html = parse(<<<'HTML'
        <span class="w-auto">text</span>
    HTML);

    expect($html)->toBe('text');
});

test('invalid w-division', function () {
    expect(fn () => parse('<span class="w-invalid">text</span>'))
        ->toThrow(InvalidStyle::class);

    expect(fn () => parse('<span class="w-1/0">text</span>'))
        ->toThrow(InvalidStyle::class);
});

test('min-w', function () {
    putenv('COLUMNS=10');

    $html = parse(<<<'HTML'
        <div class="flex">
            <span class="flex-1 content-repeat-[.] min-w-1"></span>
            <span>Over size content</span>
        </div>
    HTML);

    expect($html)->toBe('.Over size content');
});

test('max-w', function () {
    putenv('COLUMNS=10');

    $html = parse(<<<'HTML'
        <div class="w-full max-w-5">text-ignored</div>
    HTML);

    expect($html)->toBe('text-');
});

test('ml', function () {
    $html = parse('<div class="ml-2">text</div>');

    expect($html)->toBe('  text');
});

test('mr', function () {
    $html = parse('<div class="mr-2">text</div>');

    expect($html)->toBe('text  ');
});

test('mx', function () {
    $html = parse('<div class="mx-2">text</div>');

    expect($html)->toBe('  text  ');
});

test('mt', function () {
    $html = parse('<div class="mt-2">text</div>');

    expect($html)->toBe("\n\ntext");
});

test('mb', function () {
    $html = parse('<div class="mb-2">text</div>');

    expect($html)->toBe("text\n\n");
});

test('my', function () {
    $html = parse('<div class="my-2">text</div>');

    expect($html)->toBe("\n\ntext\n\n");
});

test('m', function () {
    $html = parse('<div class="m-2">text</div>');

    expect($html)->toBe("\n\n  text  \n\n");
});

test('border-t', function () {
    expect(fn () => parse('<div class="border-t">text</div>'))
        ->toThrow(InvalidStyle::class);
});

test('uppercase', function () {
    $html = parse('<div class="uppercase">text</div>');

    expect($html)->toBe('TEXT');
});

test('lowercase', function () {
    $html = parse('<div class="lowercase">tEXT</div>');

    expect($html)->toBe('text');
});

test('capitalize', function () {
    $html = parse('<div class="capitalize">text text</div>');

    expect($html)->toBe('Text Text');
});

test('snakecase', function () {
    $html = parse('<div class="snakecase">TeXt text</div>');

    expect($html)->toBe('te_xt text');
});

test('line-through', function () {
    $html = parse('<div class="line-through">text</div>');

    expect($html)->toBe("\e[9mtext\e[0m");
});

test('bg-color-number', function () {
    $html = parse('<div class="bg-green-950">text</div>');

    expect($html)->toBe('<bg=#052e16>text</>');
});

test('text-color-number', function () {
    $html = parse('<div class="text-green-950">text</div>');

    expect($html)->toBe('<fg=#052e16>text</>');
});

test('invalid text-color-number', function () {
    expect(fn () => parse('<div class="text-green-10000">text</div>'))
        ->toThrow(ColorNotFound::class);
});

test('invisible', function () {
    $html = parse('<div class="invisible">text</div>');

    expect($html)->toBe("\e[8mtext\e[0m");
});

test('w-full', function () {
    putenv('COLUMNS=10');

    $html = parse('<div class="w-full">text</div>');

    expect($html)->toBe('text      ');
});

test('block', function () {
    $html = parse('<span class="block">Hello <span>World</span></span>');

    expect($html)->toBe('Hello World');

    $html = parse('<span>Hello <span class="block">World</span></span>');

    expect($html)->toBe("Hello \nWorld");
});

test('list-disc', function () {
    $html = parse('<ul class="list-disc"><li>Hello World</li></ul>');

    expect($html)->toBe('• Hello World');
});

test('list-square', function () {
    $html = parse('<ul class="list-square"><li>Hello World</li></ul>');

    expect($html)->toBe('▪ Hello World');
});

test('list-decimal', function () {
    $html = parse('<ol class="list-decimal"><li>Hello World</li></ol>');

    expect($html)->toBe('1. Hello World');
});

test('list-none', function () {
    $html = parse('<ul class="list-none"><li>Hello World</li></ul>');

    expect($html)->toBe('Hello World');
});

test('Invalid use of style list-none', function () {
    expect(fn () => parse('<span class="list-none">Hello <span>World</span></span>'))
        ->toThrow(InvalidStyle::class);
});

test('font-bold, italic and uppercase', function () {
    $html = parse('<div class="font-bold italic uppercase">tExT</div>');

    expect($html)->toBe("<options=bold>\e[3mTEXT\e[0m</>");
});

test('line-through and lowercase', function () {
    $html = parse('<div class="line-through lowercase">TExT</div>');
    expect($html)->toBe("\e[9mtext\e[0m");
});

test('underline and capitalize', function () {
    $html = parse('<div class="underline capitalize">hELLO wORLD</div>');

    expect($html)->toBe("\e[4mHello World\e[0m");
});

test('invisible and snakecase', function () {
    $html = parse('<div class="invisible snakecase">textTEXT</div>');

    expect($html)->toBe("\e[8mtext_text\e[0m");
});

test('width with styled children, where output includes color with dash', function () {
    $html = parse(<<<'HTML'
        <div class="w-10">
            <span class="bg-brightgreen">text</span>
        </div>
    HTML);

    expect($html)->toBe('<bg=bright-green>text</>      ');
});

test('width, bg, text-right', function () {
    $html = parse(<<<'HTML'
        <div class="w-15 text-right">
            <span class="bg-green-500">Pass</span>
            <span class="text-gray-200">Some Text</span>
        </div>
    HTML);

    expect($html)->toBe('  <bg=#22c55e>Pass</><fg=#e5e7eb>Some Text</>');
});

test('max-w with fraction childs', function () {
    $html = parse(<<<'HTML'
        <div class="w-20 max-w-12">
            <span class="w-1/2">Left</span>
            <span class="w-1/2 text-right">Right</span>
        </div>
    HTML);

    expect($html)->toBe('Left   Right');
});

test('w-full, bg, margin, text-color, text-right and font-bold', function () {
    putenv('COLUMNS=20');

    $html = parse(<<<'HTML'
        <div class="ml-2 mr-2 w-full text-right">
            <span class="bg-green-500 text-gray font-bold">Pass</span>
            <span>Some Text</span>
        </div>
    HTML);

    expect($html)->toBe('     <bg=#22c55e;fg=gray;options=bold>Pass</>Some Text  ');
});

test('append-text', function () {
    $html = parse('<div class="append-world">hello</div>');

    expect($html)->toBe('helloworld');
});

test('justify-between', function () {
    $html = parse(<<<'HTML'
        <div class="w-11 justify-between">
            <span>A</span>
            <span>B</span>
            <span>C</span>
        </div>
    HTML);

    expect($html)->toBe('A    B    C');
});

test('justify-between with no space available to add', function () {
    $html = parse(<<<'HTML'
        <div class="w-3 justify-between">
            <span>A</span>
            <span>B</span>
            <span>C</span>
        </div>
    HTML);

    expect($html)->toBe('ABC');
});

test('justify-between inherit with parent without classes', function () {
    $html = parse(<<<'HTML'
        <div class="w-9 mx-1">
            <div>
                <div class="justify-between">
                    <span>A</span>
                    <span>B</span>
                    <span>C</span>
                </div>
            </div>
        </div>
    HTML);

    expect($html)->toBe(' A   B   C ');
});

test('justify-between with only one child', function () {
    $html = parse(<<<'HTML'
        <div class="w-2 justify-between">
            <span>A</span>
        </div>
    HTML);

    expect($html)->toBe('A ');
});

test('justify-between with no childs', function () {
    $html = parse(<<<'HTML'
        <div class="w-10 justify-between"></div>
    HTML);

    expect($html)->toBe('          ');
});

test('justify-evenly', function () {
    $html = parse(<<<'HTML'
        <div class="w-11 justify-evenly">
            <span>A</span>
            <span>B</span>
            <span>C</span>
        </div>
    HTML);

    expect($html)->toBe('  A  B  C  ');
});

test('justify-evenly with no space available to add', function () {
    $html = parse(<<<'HTML'
        <div class="w-3 justify-evenly">
            <span>A</span>
            <span>B</span>
            <span>C</span>
        </div>
    HTML);

    expect($html)->toBe('ABC');
});

test('justify-around', function () {
    $html = parse(<<<'HTML'
        <div class="w-11 justify-around">
            <span>A</span>
            <span>B</span>
            <span>C</span>
        </div>
    HTML);

    expect($html)->toBe(' A   B   C ');
});

test('justify-around with no space available to add', function () {
    $html = parse(<<<'HTML'
        <div class="w-3 justify-around">
            <span>A</span>
            <span>B</span>
            <span>C</span>
        </div>
    HTML);

    expect($html)->toBe('ABC');
});

test('justify-around with no childs', function () {
    $html = parse(<<<'HTML'
        <div class="w-10 justify-around"></div>
    HTML);

    expect($html)->toBe('          ');
});

test('justify-center', function () {
    $html = parse(<<<'HTML'
        <div class="w-11 justify-center">
            <span>A</span>
            <span>B</span>
            <span>C</span>
        </div>
    HTML);

    expect($html)->toBe('    ABC    ');
});

test('justify-centr with no space available to add', function () {
    $html = parse(<<<'HTML'
        <div class="w-3 justify-center">
            <span>A</span>
            <span>B</span>
            <span>C</span>
        </div>
    HTML);

    expect($html)->toBe('ABC');
});

test('justify-center with only one child', function () {
    $html = parse(<<<'HTML'
        <div class="w-11 justify-center">
            <span>A</span>
        </div>
    HTML);

    expect($html)->toBe('     A     ');
});

test('flex', function () {
    $html = parse(<<<'HTML'
        <div>
            <div class="flex">Hello</div>
            <div class="flex">World</div>
        </div>
    HTML);

    expect($html)->toBe("Hello\nWorld");
});

test('flex and flex-1', function () {
    $html = parse(<<<'HTML'
        <div class="flex w-10">
            <span>A</span>
            <span class="flex-1"></span>
            <span>B</span>
        </div>
    HTML);

    expect($html)->toBe('A        B');
});

test('flex with multiple flex-1', function () {
    $html = parse(<<<'HTML'
        <div class="flex w-11">
            <span class="flex-1"></span>
            <span>-</span>
            <span class="flex-1"></span>
            <span>-</span>
        </div>
    HTML);

    expect($html)->toBe('    -     -');
});

test('flex, flex-1 and content above column size', function () {
    putenv('COLUMNS=10');

    $html = parse(<<<'HTML'
        <div class="flex">
            <span>a</span>
            <span class="flex-1">-</span>
            <span>over column size</span>
        </div>
    HTML);

    expect($html)->toBe('a-over column size');
});

test('flex, flex-1 with content-repeat and content above column size', function () {
    putenv('COLUMNS=10');

    $html = parse(<<<'HTML'
        <div class="flex">
            <span>a</span>
            <span class="flex-1 content-repeat-[.]"></span>
            <span>over column size</span>
        </div>
    HTML);

    expect($html)->toBe('aover column size');
});

test('hidden', function () {
    $html = parse(<<<'HTML'
        <div class="hidden">test</div>
    HTML);

    expect($html)->toBe('');
});

test('content-repeat', function () {
    $html = parse(<<<'HTML'
        <div class="w-9 content-repeat-['. -']"></div>
    HTML);

    expect($html)->toBe(str_repeat('. -', 3));
});
