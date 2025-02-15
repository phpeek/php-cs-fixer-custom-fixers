<?php

declare(strict_types = 1);

namespace Tests\Fixer;

/**
 * @internal
 *
 * @covers \PhpCsFixerCustomFixers\Fixer\SingleSpaceBeforeStatementFixer
 */
final class SingleSpaceBeforeStatementFixerTest extends AbstractFixerTestCase
{
    public function testPriority(): void
    {
        static::assertSame(0, $this->fixer->getPriority());
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
        yield ['<?php $isNotFoo = !require "foo.php";'];
        yield ['<?php foo(new stdClass());'];
        yield ['<?php $content = @include "foo.php";'];
        yield ['<?php $items = [new Item(), new Item()];'];
        yield ['<?php class Foo {public function bar() {}}'];
        yield ['<?php foo(
                          new Item()
                      );'];
        yield ['<?php
                      new Foo();'];

        yield [
            '<?php new Foo();',
            '<?php  new Foo();',
        ];

        yield [
            '<?php class Foo { public function bar() {} }',
            '<?php class Foo { public    function bar() {} }',
        ];

        yield [
            '<?php $items = [new Item(), new Item()];',
            '<?php $items = [new Item(),new Item()];',
        ];

        yield [
            '<?php
                $x = new stdClass();
                $bar. require("bar_description.txt");
                $baz = 
                       new Baz();
            ',
            '<?php
                $x =   new stdClass();
                $bar.require("bar_description.txt");
                $baz = 
                       new Baz();
            ',
        ];

        yield [
            '<?php $items = [new Item(), new Item()]; ?> and <?php class Foo { public function bar() {} } ?>',
            '<?php $items = [new Item(),new Item()]; ?> and <?php class Foo { public    function bar() {} } ?>',
        ];

        yield [
            '<?php $items = [new Item(), new Item()]; ?> and <?php /* class: */class Foo { public function bar() {} } ?>',
            '<?php $items = [new Item(),new Item()]; ?> and <?php /* class: */class Foo { public    function bar() {} } ?>',
        ];
    }
}
