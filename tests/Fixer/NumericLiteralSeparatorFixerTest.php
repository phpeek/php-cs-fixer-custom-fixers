<?php

declare(strict_types = 1);

namespace Tests\Fixer;

use PhpCsFixerCustomFixers\Fixer\NumericLiteralSeparatorFixer;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @covers \PhpCsFixerCustomFixers\Fixer\NumericLiteralSeparatorFixer
 */
final class NumericLiteralSeparatorFixerTest extends TestCase
{
    /**
     * @param string      $expected
     * @param null|string $input
     * @param null|array  $configuration
     *
     * @dataProvider provideFixCases
     * @requires PHP 7.4
     */
    public function testFix(string $expected, ?string $input = null, ?array $configuration = null): void
    {
        if ($input === null) {
            $input = $expected;
        }

        $fixer = new NumericLiteralSeparatorFixer();
        $fixer->configure($configuration);

        static::assertSame(
            $expected,
            $fixer->getNewContent($input)
        );
    }

    public function provideFixCases(): iterable
    {
        yield ['1234567890'];
        yield [
            '1234567890',
            '1_234_567_890',
        ];
        yield [
            '1234567890',
            '1_234_567_890',
            ['integer' => false],
        ];
        yield [
            '1_234_567_890',
            '1234567890',
            ['integer' => true],
        ];
        yield [
            '123_456',
            '123456',
            ['integer' => true],
        ];
        yield [
            '1234567890.12',
            '1_234_567_890.12',
        ];
        yield [
            '1234567890.12',
            '1_234_567_890.12',
            ['double' => false],
        ];
        yield [
            '1_234_567_890.12',
            '1234567890.12',
            ['double' => true],
        ];
        yield [
            '12_345.67889',
            '12345.67889',
            ['double' => true],
        ];
        yield [
            '12_345.6',
            '12345.6',
            ['double' => true],
        ];
        yield [
            '0b01010100011010000110010101101111',
            '0b01010100_01101000_01100101_01101111',
        ];
        yield [
            '0b01010100011010000110010101101111',
            '0b01010100_01101000_01100101_01101111',
            ['binary' => false],
        ];
        yield [
            '0b01010100_01101000_01100101_01101111',
            '0b01010100011010000110010101101111',
            ['binary' => true],
        ];
        yield [
            '0x42726F776E',
            '0x42_72_6F_77_6E',
        ];
        yield [
            '0x42726F776E',
            '0x42_72_6F_77_6E',
            ['hex' => false],
        ];
        yield [
            '0x42_72_6F_77_6E',
            '0x42726F776E',
            ['hex' => true],
        ];
        yield [
            '1_234_567_890',
            '1_2_3_4_5_6_7_8_9_0',
            ['integer' => true],
        ];
    }
}
