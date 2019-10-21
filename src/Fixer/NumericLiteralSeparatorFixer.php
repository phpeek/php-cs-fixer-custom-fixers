<?php

declare(strict_types = 1);

namespace PhpCsFixerCustomFixers\Fixer;

use PhpCsFixer\Fixer\ConfigurationDefinitionFixerInterface;
use PhpCsFixer\FixerConfiguration\FixerConfigurationResolver;
use PhpCsFixer\FixerConfiguration\FixerOptionBuilder;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\FixerDefinition\VersionSpecification;
use PhpCsFixer\FixerDefinition\VersionSpecificCodeSample;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;

final class NumericLiteralSeparatorFixer extends AbstractFixer implements ConfigurationDefinitionFixerInterface
{
    /** @var null|bool */
    private $integerSeparator = false;

    /** @var null|bool */
    private $doubleSeparator = false;

    /** @var null|bool */
    private $binarySeparator = false;

    /** @var null|bool */
    private $hexSeparator = false;

    public function getDefinition(): FixerDefinitionInterface
    {
        return new FixerDefinition(
            'Nullable parameters must be written in the consistent style.',
            [new VersionSpecificCodeSample(
                '<?php
echo 1_000_000_000;
',
                new VersionSpecification(70400)
            )]
        );
    }

    public function getConfigurationDefinition(): FixerConfigurationResolver
    {
        return new FixerConfigurationResolver([
            (new FixerOptionBuilder('integer', 'whether add or remove thousands separator.'))
                ->setAllowedTypes(['bool', 'null'])
                ->setDefault($this->integerSeparator)
                ->getOption(),
            (new FixerOptionBuilder('double', 'whether add or remove thousands separator.'))
                ->setAllowedTypes(['bool', 'null'])
                ->setDefault($this->doubleSeparator)
                ->getOption(),
            (new FixerOptionBuilder('binary ', 'whether add or remove separator.'))
                ->setAllowedTypes(['bool', 'null'])
                ->setDefault($this->binarySeparator)
                ->getOption(),
            (new FixerOptionBuilder('hex', 'whether add or remove separator.'))
                ->setAllowedTypes(['bool', 'null'])
                ->setDefault($this->hexSeparator)
                ->getOption(),
        ]);
    }

    public function configure(?array $configuration = null): void
    {
        $this->integerSeparator = $configuration['integer'] ?? $this->integerSeparator;
        $this->doubleSeparator = $configuration['double'] ?? $this->doubleSeparator;
        $this->binarySeparator = $configuration['binary'] ?? $this->binarySeparator;
        $this->hexSeparator = $configuration['hex'] ?? $this->hexSeparator;
    }

    public function isCandidate(Tokens $tokens): bool
    {
        return \PHP_VERSION_ID >= 70400 && $tokens->isAnyTokenKindsFound([T_DNUMBER, T_LNUMBER]);
    }

    public function isRisky(): bool
    {
        return false;
    }

    public function getPriority(): int
    {
        // must be run before NoUnreachableDefaultArgumentValueFixer
        return 1;
    }

    public function fix(\SplFileInfo $file, Tokens $tokens): void
    {
        foreach ($tokens as $index => $token) {
            if (!$token->isGivenKind([T_DNUMBER, T_LNUMBER])) {
                continue;
            }

            $content = $token->getContent();
            $newContent = $this->getNewContent($content);

            if ($content !== $newContent) {
                $tokens->offsetSet($index, new Token([$token->getId(), $newContent]));
            }
        }
    }

    public function getNewContent(string $content): string
    {
        if (\strpos($content, '0b') === 0) {
            if ($this->binarySeparator === true) {
                return $this->addSeparatorsForSubstring($content, 8, 2, \strlen($content));
            }
            if ($this->binarySeparator === false) {
                return $this->removeSeparators($content);
            }
        }

        if (\strpos($content, '0x') === 0) {
            if ($this->hexSeparator === true) {
                return $this->addSeparatorsForSubstring($content, 2, 2, \strlen($content));
            }
            if ($this->hexSeparator === false) {
                return $this->removeSeparators($content);
            }
        }

        $dotPosition = \strpos($content, '.');
        if ($dotPosition !== false) {
            if ($this->doubleSeparator === true) {
                return $this->addSeparatorsForSubstring($content, 3, 0, $dotPosition);
            }
            if ($this->doubleSeparator === false) {
                return $this->removeSeparators($content);
            }
        }

        if ($this->integerSeparator === true) {
            return $this->addSeparators($content, 3);
        }
        if ($this->integerSeparator === false) {
            return $this->removeSeparators($content);
        }

        return $content;
    }

    private function addSeparatorsForSubstring(string $content, int $groupSize, int $startPosition, int $endPosition)
    {
        return \substr($content, 0, $startPosition)
            . $this->addSeparators(\substr($content, $startPosition, $endPosition - $startPosition), $groupSize)
            . \substr($content, $endPosition);
    }

    private function addSeparators(string $content, int $groupSize)
    {
        $content = $this->removeSeparators($content);
        $content = \strrev($content);
        $content = \preg_replace(\sprintf('/[\da-fA-F]{%d}(?!$)/', $groupSize), '$0_', $content);

        return \strrev($content);
    }

    private function removeSeparators(string $content): string
    {
        return \str_replace('_', '', $content);
    }
}
