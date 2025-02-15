<?php

declare(strict_types = 1);

namespace PhpCsFixerCustomFixers\Fixer;

use PhpCsFixer\DocBlock\Annotation;
use PhpCsFixer\DocBlock\DocBlock;
use PhpCsFixer\DocBlock\Line;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Preg;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;

final class PhpdocParamOrderFixer extends AbstractFixer
{
    public function getDefinition(): FixerDefinitionInterface
    {
        return new FixerDefinition(
            "`@param` annotations must be in the same order as function's parameters.",
            [new CodeSample('<?php
/**
 * @param int $b
 * @param int $a
 * @param int $c
 */
function foo($a, $b, $c) {}
')]
        );
    }

    public function getPriority(): int
    {
        return 5;
    }

    public function isCandidate(Tokens $tokens): bool
    {
        return $tokens->isAllTokenKindsFound([T_DOC_COMMENT, T_FUNCTION]);
    }

    public function isRisky(): bool
    {
        return false;
    }

    public function fix(\SplFileInfo $file, Tokens $tokens): void
    {
        for ($index = 0; $index < $tokens->count(); $index++) {
            if (!$tokens[$index]->isGivenKind(T_DOC_COMMENT)) {
                continue;
            }

            $functionIndex = $tokens->getTokenNotOfKindSibling($index, 1, [[T_ABSTRACT], [T_COMMENT], [T_FINAL], [T_PRIVATE], [T_PROTECTED], [T_PUBLIC], [T_STATIC], [T_WHITESPACE]]);
            if ($functionIndex === null || !$tokens[$functionIndex]->isGivenKind(T_FUNCTION)) {
                continue;
            }

            $paramNames = $this->getParamNames($tokens, $functionIndex);

            $docBlock = new DocBlock($tokens[$index]->getContent());
            $sorted = $this->getSortedAnnotations($docBlock->getAnnotations(), $paramNames);

            foreach ($sorted as $annotationIndex => $annotationContent) {
                /** @var Annotation $annotation */
                $annotation = $docBlock->getAnnotation($annotationIndex);
                $annotation->remove();

                /** @var Line $line */
                $line = $docBlock->getLine($annotation->getStart());
                $line->setContent($annotationContent);
            }

            if ($docBlock->getContent() === $tokens[$index]->getContent()) {
                continue;
            }

            $tokens[$index] = new Token([T_DOC_COMMENT, $docBlock->getContent()]);
        }
    }

    /**
     * @return string[]
     */
    private function getParamNames(Tokens $tokens, int $functionIndex): array
    {
        /** @var int $paramBlockStartIndex */
        $paramBlockStartIndex = $tokens->getNextTokenOfKind($functionIndex, ['(']);

        $paramBlockEndIndex = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_PARENTHESIS_BRACE, $paramBlockStartIndex);

        $paramNames = [];
        for ($index = $paramBlockStartIndex; $index < $paramBlockEndIndex; $index++) {
            if ($tokens[$index]->isGivenKind(T_VARIABLE)) {
                $paramNames[] = $tokens[$index]->getContent();
            }
        }

        return $paramNames;
    }

    private function getSortedAnnotations(array $annotations, array $paramNames): array
    {
        $paramFound = false;
        $annotationsBeforeParams = [];
        $paramsByName = \array_combine($paramNames, \array_fill(0, \count($paramNames), null));
        $superfluousParams = [];
        $annotationsAfterParams = [];

        foreach ($annotations as $annotation) {
            if ($annotation->getTag()->getName() === 'param') {
                $paramFound = true;
                foreach ($paramNames as $paramName) {
                    if (Preg::match(\sprintf('/@param\s+(?:[^\$](?:[^<\s]|<[^>]*>)*\s+)?(?:&|\.\.\.)?\s*(\Q%s\E)\b/', $paramName), $annotation->getContent(), $matches) === 1 && !isset($paramsByName[$matches[1]])) {
                        $paramsByName[$matches[1]] = $annotation->getContent();
                        continue 2;
                    }
                }
                $superfluousParams[] = $annotation->getContent();
                continue;
            }

            if ($paramFound) {
                $annotationsAfterParams[] = $annotation->getContent();
                continue;
            }

            $annotationsBeforeParams[] = $annotation->getContent();
        }

        return \array_merge($annotationsBeforeParams, \array_values(\array_filter($paramsByName)), $superfluousParams, $annotationsAfterParams);
    }
}
