<?php

declare(strict_types = 1);

namespace Tests\Fixer;

use PhpCsFixerCustomFixers\Fixer\CommentSurroundedBySpacesFixer;
use PhpCsFixerCustomFixers\Fixer\NoCommentedOutCodeFixer;

/**
 * @internal
 *
 * @covers \PhpCsFixerCustomFixers\Fixer\CommentedOutFunctionFixer
 */
final class CommentedOutFunctionFixerTest extends AbstractFixerTestCase
{
    public function testPriority(): void
    {
        static::assertGreaterThan((new NoCommentedOutCodeFixer())->getPriority(), $this->fixer->getPriority());
        static::assertGreaterThan((new CommentSurroundedBySpacesFixer())->getPriority(), $this->fixer->getPriority());
    }

    public function testConfiguration(): void
    {
        $options = $this->fixer->getConfigurationDefinition()->getOptions();
        static::assertArrayHasKey(0, $options);
        static::assertSame('functions', $options[0]->getName());
    }

    public function testIsRisky(): void
    {
        static::assertTrue($this->fixer->isRisky());
    }

    /**
     * @param string      $expected
     * @param null|string $input
     * @param null|array  $configuration
     *
     * @dataProvider provideFixCases
     */
    public function testFix(string $expected, ?string $input = null, ?array $configuration = null): void
    {
        $this->fixer->configure($configuration);
        $this->doTest($expected, $input);
    }

    public function provideFixCases(): iterable
    {
        yield ['<?php $foo = var_dump[0]("Bar");'];
        yield ['<?php $foo = $printingHelper->var_dump($bar);'];
        yield ['<?php $foo = PrintingHelper::var_dump($bar);'];
        yield ['<?php $foo = PrintingHelper\var_dump($bar);'];
        yield ['<?php define("var_dump", "foo"); var_dump; bar($baz);'];
        yield ['<?php namespace Foo; function var_dump($bar) { return $baz; }'];

        yield [
            '<?php /*var_dump($x);*/',
            '<?php var_dump($x);',
        ];

        yield [
            '<?php /*VAR_DUMP($x);*/',
            '<?php VAR_DUMP($x);',
        ];

        yield [
            '<?php /*\var_dump($x);*/',
            '<?php \var_dump($x);',
        ];

        yield [
            '<?php //\/* foo */var_dump/** bar */($x);',
            '<?php \/* foo */var_dump/** bar */($x);',
        ];

        yield [
            '<?php var_dump($x); /*foo($y);*/',
            '<?php var_dump($x); foo($y);',
            ['functions' => ['foo']],
        ];

        yield [
            '<?php
                /*var_dump(foo(
                    100,
                    bar($x + 4)
                ));*/
            ',
            '<?php
                var_dump(foo(
                    100,
                    bar($x + 4)
                ));
            ',
        ];

        yield [
            '<?php foo(/*var_dump($x)*/);',
            '<?php foo(var_dump($x));',
        ];

        yield [
            '<?php //var_dump($x/*, $y*/);',
            '<?php var_dump($x/*, $y*/);',
        ];

        yield [
            '<?php //var_dump($x/*, $y*/);
foo();',
            '<?php var_dump($x/*, $y*/);foo();',
        ];

        yield [
            '<?php //var_dump($x/*, $y*/);
 /* foo */ foo();',
            '<?php var_dump($x/*, $y*/); /* foo */ foo();',
        ];

        yield [
            '<?php //var_dump($x/*, $y*/);
                foo();',
            '<?php var_dump($x/*, $y*/);
                foo();',
        ];

        yield [
            '<?php
                //var_dump(foo(
//                    100, /* 10 * 10 */
//                    bar($x + 4) // comment
//                ));
baz();
            ',
            '<?php
                var_dump(foo(
                    100, /* 10 * 10 */
                    bar($x + 4) // comment
                ));baz();
            ',
        ];
    }
}
