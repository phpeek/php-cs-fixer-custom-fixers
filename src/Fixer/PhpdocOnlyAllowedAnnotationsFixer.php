<?php

declare(strict_types = 1);

namespace PhpCsFixerCustomFixers\Fixer;

use PhpCsFixer\DocBlock\DocBlock;
use PhpCsFixer\Fixer\ConfigurationDefinitionFixerInterface;
use PhpCsFixer\FixerConfiguration\FixerConfigurationResolver;
use PhpCsFixer\FixerConfiguration\FixerOptionBuilder;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Preg;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;

final class PhpdocOnlyAllowedAnnotationsFixer extends AbstractFixer implements ConfigurationDefinitionFixerInterface
{
    /** @var string[] */
    private $elements = [];

    public function getDefinition(): FixerDefinitionInterface
    {
        return new FixerDefinition(
            'Only listed annotations can be in PHPDoc.',
            [new CodeSample(
                '<?php
/**
 * @author John Doe
 * @package foo
 * @subpackage bar
 * @version 1.0
 */
function foo_bar() {}
',
                ['elements' => ['author', 'version']]
            )]
        );
    }

    public function getConfigurationDefinition(): FixerConfigurationResolver
    {
        return new FixerConfigurationResolver([
            (new FixerOptionBuilder('elements', 'list of annotations to keep in PHPDoc'))
                ->setAllowedTypes(['array'])
                ->setDefault($this->elements)
                ->getOption(),
        ]);
    }

    public function configure(?array $configuration = null): void
    {
        $this->elements = $configuration['elements'] ?? $this->elements;
    }

    public function getPriority(): int
    {
        // must be run before NoEmptyPhpdocFixer
        return 6;
    }

    public function isCandidate(Tokens $tokens): bool
    {
        return $tokens->isTokenKindFound(T_DOC_COMMENT);
    }

    public function isRisky(): bool
    {
        return false;
    }

    public function fix(\SplFileInfo $file, Tokens $tokens): void
    {
        foreach ($tokens as $index => $token) {
            if (!$token->isGivenKind(T_DOC_COMMENT)) {
                continue;
            }

            $docBlock = new DocBlock($token->getContent());

            foreach ($docBlock->getAnnotations() as $annotation) {
                Preg::match('/@([a-zA-Z0-9\Q_-\\\E]+)/', $annotation->getContent(), $matches);

                if (\in_array($matches[1], $this->elements, true)) {
                    continue;
                }
                $annotation->remove();
            }

            if ($docBlock->getContent() === '') {
                $tokens->clearTokenAndMergeSurroundingWhitespace($index);
                continue;
            }

            $tokens[$index] = new Token([T_DOC_COMMENT, $docBlock->getContent()]);
        }
    }
}
