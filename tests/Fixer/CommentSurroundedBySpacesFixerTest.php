<?php

declare(strict_types = 1);

namespace Tests\Fixer;

use PhpCsFixer\Fixer\Comment\MultilineCommentOpeningClosingFixer;

/**
 * @internal
 *
 * @covers \PhpCsFixerCustomFixers\Fixer\CommentSurroundedBySpacesFixer
 */
final class CommentSurroundedBySpacesFixerTest extends AbstractFixerTestCase
{
    public function testPriority(): void
    {
        static::assertLessThan((new MultilineCommentOpeningClosingFixer())->getPriority(), $this->fixer->getPriority());
    }

    public function testIsRisky(): void
    {
        static::assertFalse($this->fixer->isRisky());
    }

    /**
     * @param string      $expected
     * @param null|string $input
     *
     * @dataProvider provideFixCases
     */
    public function testFix(string $expected, ?string $input = null): void
    {
        $this->doTest($expected, $input);
    }

    public function provideFixCases(): iterable
    {
        yield ['<?php $a; //'];
        yield ['<?php $a; ////'];
        yield ['<?php $a; /**/'];
        yield ['<?php $a; // foo'];
        yield ['<?php $a; # foo'];
        yield ['<?php $a; /* foo */'];
        yield ['<?php $a; /** foo */'];
        yield ['<?php $a; /**  foo  */'];
        yield ["<?php AA; /**\tfoo\t*/"];

        yield [
            '<?php
                /*
                 * foo
                 */',
        ];

        yield [
            '<?php $a; //  foo',
        ];

        yield [
            '<?php $a; // foo',
            '<?php $a; //foo',
        ];

        yield [
            '<?php $a; # foo',
            '<?php $a; #foo',
        ];

        yield [
            '<?php $a; /* foo */',
            '<?php $a; /*foo */',
        ];

        yield [
            '<?php $a; /* foo */',
            '<?php $a; /* foo*/',
        ];

        yield [
            '<?php $a; /* foo */',
            '<?php $a; /*foo*/',
        ];

        yield [
            '<?php $a; /* foo  */',
            '<?php $a; /*foo  */',
        ];

        yield [
            '<?php $a; /*  foo */',
            '<?php $a; /*  foo*/',
        ];

        yield [
            '<?php $a; /** foo */',
            '<?php $a; /**foo*/',
        ];

        yield [
            '<?php $a; /** foo */',
            '<?php $a; /** foo*/',
        ];

        yield [
            '<?php $a; /**** foo ****/',
            '<?php $a; /****foo****/',
        ];

        yield [
            '<?php $a; /* foo */// bar',
            '<?php $a; /*foo*///bar',
        ];

        yield [
            '<?php
                // foo
                //
                // bar
            ',
            '<?php
                //foo
                //
                //bar
            ',
        ];
    }
}
