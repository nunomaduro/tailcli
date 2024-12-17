<?php

use Symfony\Component\Console\Output\OutputInterface;

use function Termwind\parse;
use function Termwind\render;
use function Termwind\renderUsing;

it('can render complex html', function () {
    $html = parse(<<<'HTML'
        <div class="bg-white">
            <a class="ml-2">link text</a> and <a href="link">link text</a>
        </div>
    HTML
    );

    expect($html)->toBe('<bg=white>  <bg=white>link text</> and <href=link;bg=white>link text</></>');
});

it('can render strings', function () {
    $html = parse('text');

    expect($html)->toBe('text');
});

it('can render style modifier with text modifier', function () {
    $html = parse(<<<'HTML'
        <div class="bg-white underline uppercase">Hello world</div>
    HTML
    );

    expect($html)->toBe("<bg=white>\e[4mHELLO WORLD\e[0m</>");
});

it('can render to custom output', function () {
    $html = render(<<<'HTML'
        <div class="bg-white">
            <a class="ml-2">link text</a><a class="ml-2" href="link">link text</a>
        </div>
    HTML
    );

    expect($this->output->fetch())->toBe("  link text  link text\n");
});

it('renders element inside another one', function () {
    $html = parse('<div>Hello <strong>world</strong></div>');

    expect($html)->toBe("Hello \e[1mworld\e[0m");
});

it('renders element inside another one with extra spaces and line breaks', function () {
    $html = parse(<<<'HTML'
        <div class="bg-red">
            Hello <strong>world</strong> <a href="#">click here</a>
        </div>
    HTML
    );

    expect($html)->toBe("<bg=red>Hello <bg=red>\e[1mworld\e[0m</> <href=#;bg=red>click here</></>");
});

it('renders element and ignores the classes of the same type', function () {
    $html = parse(<<<'HTML'
        <div class="ml-3 ml-1">Hello World</div>
    HTML
    );

    expect($html)->toBe(' Hello World');
});

it('does not render comment html strings', function () {
    $html = parse(<<<'HTML'
        <div>
            <!-- This is a comment -->
            <div>Hello World</div>
        </div>
    HTML
    );

    expect($html)->toBe('Hello World');
});

it('can inherit styles', function () {
    $html = parse(<<<'HTML'
        <div class="bg-red-300 text-white px-10">
            <span class="mr-1">Hello</span>
            <strong class="text-blue">world</strong>
        </div>
    HTML
    );

    expect($html)->toBe("<bg=#fca5a5;fg=white>          <bg=#fca5a5;fg=white>Hello</> <fg=blue;bg=#fca5a5>\e[1mworld\e[0m</>          </>");
});

it('can extend colors', function () {
    $html = parse(<<<'HTML'
        <div class="my-1 ml-3 px-2 bg-green-300 text-black">
            🍃 Termwind now have the capability to <b>extend</b> colors!
        </div>
    HTML
    );

    expect($html)->toBe("\n   <bg=#86efac;fg=black>  🍃 Termwind now have the capability to <bg=#86efac;fg=black;options=bold>extend</> colors!  </>\n");
});

it('can extend with multiple childs and colors', function () {
    $html = parse(<<<'HTML'
         <div class="my-1 ml-3 px-2 bg-green-300 text-black">
            Termwind <span class="text-red-500"><span class="text-blue-300">now <span class="text-indigo-500">have</span> the</span> capability</span> to extend colors!
        </div>
    HTML
    );

    expect($html)->toBe("\n   <bg=#86efac;fg=black>  Termwind <fg=#ef4444;bg=#86efac><fg=#93c5fd;bg=#86efac>now <fg=#6366f1;bg=#86efac>have</> the</> capability</> to extend colors!  </>\n");
});

it('can inherit styles within multiple levels', function () {
    $html = parse(<<<'HTML'
        <div class="bg-red-700 px-5 my-1 mx-2">
            <div class="text-blue-300 ml-2">
                <div>
                    <div><b>Termwind</b> is great!</div>
                </div>
            </div>
        </div>
    HTML
    );

    expect($html)->toBe("\n  <bg=#b91c1c>       <fg=#93c5fd;bg=#b91c1c><bg=#b91c1c;fg=#93c5fd><bg=#b91c1c;fg=#93c5fd><bg=#b91c1c;fg=#93c5fd;options=bold>Termwind</> is great!</></></>     </>  \n");
});

it('trims the text properly when having bg and text colors', function () {
    $html = parse(<<<'HTML'
        <div class="w-5">
            <span class="bg-green-500">P<b>a</b>ss</span>
            <span class="text-gray-200">A this should not show</span>
        </div>
    HTML);

    expect($html)->toBe('<bg=#22c55e>P<bg=#22c55e;options=bold>a</>ss</><fg=#e5e7eb>A</>');
});

it('trims the text properly when having escape codes', function () {
    $html = parse(<<<'HTML'
        <div class="w-5">
            <span class="line-through">Pass</span>
            <span class="text-gray-200">A this should not show</span>
        </div>
    HTML);

    expect($html)->toBe("\e[9mPass\e[0m<fg=#e5e7eb>A</>");
});

it('can inherit margins and paddings', function () {
    putenv('COLUMNS=20');

    $html = parse(<<<'HTML'
        <div class="px-1">
            <div class="mx-1">
                <div class="mx-1">
                    <span class="w-1/2 mr-1 px-1">A</span>
                    <span class="w-1/2 ml-1 px-1">B</span>
                </div>
            </div>
        </div>
    HTML);

    expect($html)->toBe('    A       B       ');
});

it('can inherit width on multiple levels', function () {
    putenv('COLUMNS=30');

    $html = parse(<<<'HTML'
        <div class="w-full">
            <div class="w-24 bg-white mx-2">
                <div class="mx-1">
                    <div class="mx-1 px-1">
                        <span class="w-1/2 px-1 mx-1 bg-red">A</span>
                        <span class="w-1/2 px-1 mx-1 bg-blue">B</span>
                    </div>
                </div>
            </div>
        </div>
    HTML);

    expect($html)->toBe('  <bg=white> <bg=white> <bg=white>  <bg=red> A     </>  <bg=blue> B     </>  </> </> </>    ');
});

it('can inherit margins on multiple lines', function () {
    $html = parse(<<<'HTML'
        <div class="ml-2">
            <div class="bg-red">AAAAAAAAAA</div>
            <div class="bg-blue">BBBBBBBBBB</div>
        </div>
    HTML);

    expect($html)->toBe("  <bg=red>AAAAAAAAAA</>\n  <bg=blue>BBBBBBBBBB</>");
});

it('can inherit padding on multiple lines', function () {
    $html = parse(<<<'HTML'
        <div class="pl-2">
            <div class="bg-red">AAAAAAAAAA</div>
            <div class="bg-blue">BBBBBBBBBB</div>
        </div>
    HTML);

    expect($html)->toBe("  <bg=red>AAAAAAAAAA</>\n  <bg=blue>BBBBBBBBBB</>");
});

it('can inherit width on multiple lines', function () {
    $html = parse(<<<'HTML'
        <div class="w-10">
            <div class="w-full bg-red">AAAAAAAAAA</div>
            <div class="w-full bg-blue">BBBBBBBBBB</div>
        </div>
    HTML);

    expect($html)->toBe("<bg=red>AAAAAAAAAA</>\n<bg=blue>BBBBBBBBBB</>");
});

it('can inherit font-bold', function () {
    $html = parse(<<<'HTML'
        <div class="font-bold text-red-500">
            <div>A</div>
            <div>B</div>
        </div>
    HTML);

    expect($html)->toBe("<fg=#ef4444;options=bold><fg=#ef4444;options=bold>A</>\n<fg=#ef4444;options=bold>B</></>");
});

it('renders a div and table', function () {
    $html = parse(<<<'HTML'
        <div class="ml-2">
            <div>Results:</div>
            <table>
                <tr>
                    <td>A</td>
                    <td>B</td>
                </tr>
            </table>
        </div>
    HTML);

    expect($html)->toBe("  Results:\n  +---+---+\n  | A | B |\n  +---+---+");
});

it('renders an emoji correctly with line-breaks correctly', function () {
    $html = parse(<<<'HTML'
        <div class="w-8">
            <div class="w-full mb-1"></div>
            <div class="w-full">
                <span class="mr-1 px-1 text-red-500">⚽️</span>
                <span>A</span>
            </div>
        </div>
    HTML);

    expect($html)->toBe("        \n\n<fg=#ef4444> ⚽️ </> A ");
});

it('renders East Asian characters chains of text-center with spaces correctly', function () {
    $html = parse(<<<'HTML'
        <div class="w-32">
            <div class="w-full bg-yellow text-center">你好 世界</div>
            <div class="w-full bg-yellow text-center">ハローワールド</div>
        </div>
    HTML);

    expect($html)->toBe("<bg=yellow>           你好 世界            </>\n<bg=yellow>         ハローワールド         </>");
});

it('renders East Asian characters chains of space between with spaces correctly', function () {
    $html = parse(<<<'HTML'
        <div class="w-32">
            <div class="flex mx-2">
                <span>Termwind</span>
                <span class="flex-1"></span>
                <span>你好 世界</span>
            </div>
            <div class="flex mx-2">
                <span>Termwind</span>
                <span class="flex-1"></span>
                <span>ハローワールド</span>
            </div>
        </div>
    HTML);

    expect($html)->toBe("  Termwind           你好 世界  \n  Termwind      ハローワールド  ");
});

it('renders multiple chains of w-full with margins and text-alignment', function () {
    $html = parse(<<<'HTML'
        <div class="w-10">
            <div class="w-full">Test</div>
            <div class="mt-2 w-full text-right">Test 2</div>
        </div>
    HTML);

    expect($html)->toBe("Test      \n\n\n    Test 2");
});

it('defaults to VERBOSITY_NORMAL', function () {
    $output = Mockery::mock(OutputInterface::class);

    $output->shouldReceive('writeln')
        ->once()
        ->with('Foo Bar', OutputInterface::OUTPUT_NORMAL);

    renderUsing($output);

    render('Foo Bar');
});

it('allows to use custom verbosities', function () {
    $output = Mockery::mock(OutputInterface::class);

    $output->shouldReceive('writeln')
        ->once()
        ->with('Foo Bar', OutputInterface::VERBOSITY_DEBUG);

    renderUsing($output);

    render('Foo Bar', OutputInterface::VERBOSITY_DEBUG);
});

it('do not display debug messages when verbosity is normal', function () {
    // default: $this->output->setVerbosity(OutputInterface::VERBOSITY_NORMAL);

    $html = render(<<<'HTML'
            <div class="bg-white">
                <a class="ml-2">link text</a><a class="ml-2" href="link">link text</a>
            </div>
        HTML,
        OutputInterface::VERBOSITY_DEBUG,
    );

    expect($this->output->fetch())->toBe('');
});

it('displays debug messages when verbosity is debug', function () {
    $this->output->setVerbosity(OutputInterface::VERBOSITY_DEBUG);

    $html = render(<<<'HTML'
            <div class="bg-white">
                <a class="ml-2">link text</a><a class="ml-2" href="link">link text</a>
            </div>
        HTML,
        OutputInterface::VERBOSITY_DEBUG,
    );

    expect($this->output->fetch())->toBe("  link text  link text\n");
});
