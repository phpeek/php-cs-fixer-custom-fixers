<?php

declare(strict_types = 1);

namespace PhpCsFixerCustomFixers\Fixer;

use PhpCsFixer\Fixer\ConfigurationDefinitionFixerInterface;
use PhpCsFixer\FixerConfiguration\FixerConfigurationResolver;
use PhpCsFixer\FixerConfiguration\FixerOptionBuilder;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Tokenizer\Analyzer\FunctionsAnalyzer;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;

final class CommentedOutFunctionFixer extends AbstractFixer implements ConfigurationDefinitionFixerInterface
{
    /** @var string[] */
    private $functions = ['print_r', 'var_dump'];

    public function getDefinition(): FixerDefinitionInterface
    {
        return new FixerDefinition(
            'Defined functions must be commented out.',
            [new CodeSample('<?php
var_dump($x);
')],
            null,
            'when any of the functions has side effects or is overridden'
        );
    }

    public function getConfigurationDefinition(): FixerConfigurationResolver
    {
        return new FixerConfigurationResolver([
            (new FixerOptionBuilder('functions', 'list of functions to comment out'))
                ->setDefault($this->functions)
                ->setAllowedTypes(['array'])
                ->getOption(),
        ]);
    }

    public function configure(?array $configuration = null): void
    {
        $this->functions = $configuration['functions'] ?? $this->functions;
    }

    public function isCandidate(Tokens $tokens): bool
    {
        return $tokens->isTokenKindFound(T_STRING);
    }

    public function isRisky(): bool
    {
        return true;
    }

    public function getPriority(): int
    {
        // must be run before CommentSurroundedBySpacesFixer and NoCommentedOutCodeFixer
        return 1;
    }

    public function fix(\SplFileInfo $file, Tokens $tokens): void
    {
        $functionsAnalyzer = new FunctionsAnalyzer();

        for ($index = $tokens->count() - 1; $index > 0; $index--) {
            if (!$functionsAnalyzer->isGlobalFunctionCall($tokens, $index)) {
                continue;
            }

            if (!\in_array(\strtolower($tokens[$index]->getContent()), $this->functions, true)) {
                continue;
            }

            $indexStart = $index;

            /** @var int $prevIndex */
            $prevIndex = $tokens->getPrevMeaningfulToken($index);
            if ($tokens[$prevIndex]->isGivenKind(T_NS_SEPARATOR)) {
                $indexStart = $prevIndex;
            }

            /** @var int $indexParenthesisStart */
            $indexParenthesisStart = $tokens->getNextMeaningfulToken($index);

            $indexEnd = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_PARENTHESIS_BRACE, $indexParenthesisStart);

            /** @var int $indexSemicolon */
            $indexSemicolon = $tokens->getNextMeaningfulToken($indexEnd);
            if ($tokens[$indexSemicolon]->equals(';')) {
                $indexEnd = $indexSemicolon;
            }

            $comment = $this->getComment($tokens, $indexStart, $indexEnd);
            if (\substr($comment, -1) === "\n" && $tokens[$indexEnd + 1]->isWhitespace()) {
                $comment = \substr($comment, 0, -1);
                $tokens->offsetSet($indexEnd + 1, new Token([T_WHITESPACE, "\n" . $tokens[$indexEnd + 1]->getContent()]));
            }

            $newTokens = Tokens::fromCode('<?php ' . $comment);
            $newTokens->clearAt(0);

            $tokens->overrideRange(
                $indexStart,
                $indexEnd,
                $newTokens
            );
        }
    }

    private function getComment(Tokens $tokens, int $indexStart, int $indexEnd): string
    {
        $content = $tokens->generatePartialCode($indexStart, $indexEnd);

        if (\strpos($content, '*/') === false) {
            return '/*' . $content . '*/';
        }

        $content = '//' . \str_replace("\n", "\n//", $content);

        if ($this->isLineBreakRequired($tokens, $indexEnd)) {
            $content .= "\n";
        }

        return $content;
    }

    private function isLineBreakRequired(Tokens $tokens, int $index): bool
    {
        $nextIndex = $tokens->getNextMeaningfulToken($index);

        if ($nextIndex === null) {
            return false;
        }

        while ($index < $nextIndex) {
            $index++;
            if (\strpos($tokens[$index]->getContent(), "\n") !== false) {
                return false;
            }
        }

        return true;
    }
}
