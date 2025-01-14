<?php

declare(strict_types = 1);

namespace Tests\Fixer;

use PhpCsFixer\Fixer\FunctionNotation\MethodArgumentSpaceFixer;

/**
 * @internal
 *
 * @covers \PhpCsFixerCustomFixers\Fixer\DataProviderReturnTypeFixer
 */
final class DataProviderReturnTypeFixerTest extends AbstractFixerTestCase
{
    public function testPriority(): void
    {
        static::assertGreaterThan((new MethodArgumentSpaceFixer())->getPriority(), $this->fixer->getPriority());
    }

    public function testIsRisky(): void
    {
        static::assertTrue($this->fixer->isRisky());
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
        yield 'data provider with iterable return type' => [
            '<?php
class FooTest extends TestCase {
    /**
     * @dataProvider provideFooCases
     */
    public function testFoo() {}
    public function provideFooCases() : iterable {}
}',
        ];

        $template = '<?php
class FooTest extends TestCase {
    /**
     * @dataProvider provideFooCases
     */
    public function testFoo() {}
    /**
     * @dataProvider provider
     */
    public function testBar() {}
    public function provideFooCases()%s {}
    public function provider()%s {}
    public function notProvider(): array {}
}';

        $cases = [
            'data provider without return type' => [
                ': iterable',
                '',
            ],
            'data provider with array return type' => [
                ': iterable',
                ': array',
            ],
            'data provider with return type and comment' => [
                ': /* TODO: add more cases */ iterable',
                ': /* TODO: add more cases */ array',
            ],
            'data provider with return type namespaced class' => [
                ': iterable',
                ': Foo\Bar',
            ],
            'data provider with return type namespaced class starting with iterable' => [
                ': iterable',
                ': iterable \ Foo',
            ],
            'data provider with return type namespaced class and comments' => [
                ': iterable',
                ': Foo/* Some info */\/* More info */Bar',
            ],
            'data provider with iterable return type in different case' => [
                ': iterable',
                ': Iterable',
            ],
        ];

        foreach ($cases as $key => $case) {
            yield $key => \array_map(
                static function (string $code) use ($template): string {
                    return \sprintf($template, $code, $code);
                },
                $case
            );
        }

        yield 'advanced case' => [
            '<?php
class FooTest extends TestCase {
    /**
     * @dataProvider provideFooCases
     * @dataProvider provideFooCases2
     */
    public function testFoo()
    {
        /**
         * @dataProvider someFunction
         */
        $foo = /** foo */ function ($x) { return $x + 1; };
        /**
         * @dataProvider someFunction2
         */
        /* foo */someFunction2();
    }
    /**
     * @dataProvider provideFooCases3
     */
    public function testBar() {}

    public function provideFooCases(): iterable {}
    public function provideFooCases2(): iterable {}
    public function provideFooCases3(): iterable {}
    public function someFunction() {}
    public function someFunction2() {}
}',
            '<?php
class FooTest extends TestCase {
    /**
     * @dataProvider provideFooCases
     * @dataProvider provideFooCases2
     */
    public function testFoo()
    {
        /**
         * @dataProvider someFunction
         */
        $foo = /** foo */ function ($x) { return $x + 1; };
        /**
         * @dataProvider someFunction2
         */
        /* foo */someFunction2();
    }
    /**
     * @dataProvider provideFooCases3
     */
    public function testBar() {}

    public function provideFooCases() {}
    public function provideFooCases2() {}
    public function provideFooCases3() {}
    public function someFunction() {}
    public function someFunction2() {}
}',
        ];

        foreach (['abstract', 'final', 'private', 'protected', 'static', '/* private */'] as $modifier) {
            yield \sprintf('test function with %s modifier', $modifier) => [
                \sprintf('<?php
                    class FooTest extends TestCase {
                        /**
                         * @dataProvider provideFooCases
                         */
                        %s function testFoo() {}
                        public function provideFooCases(): iterable {}
                    }
                ', $modifier),
                \sprintf('<?php
                    class FooTest extends TestCase {
                        /**
                         * @dataProvider provideFooCases
                         */
                        %s function testFoo() {}
                        public function provideFooCases() {}
                    }
                ', $modifier),
            ];
        }
    }
}
