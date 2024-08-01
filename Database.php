<?php

namespace FpDbTest;

use InvalidArgumentException;
use FpDbTest\QueryBuilder\PlaceholderTypeEnum;
use FpDbTest\QueryBuilder\TypeFormatter;
use mysqli;

class Database implements DatabaseInterface
{
    private mysqli $mysqli;
    public TypeFormatter $typeFormatter;
    public const string SKIP = '~SKIP~';

    public function __construct(mysqli $mysqli)
    {
        $this->mysqli = $mysqli;
        $this->typeFormatter = new TypeFormatter($mysqli);
    }

    public function buildQuery(string $query, array $args = []): string
    {
        $query = $this->mysqli->real_escape_string($query);
        if ($this->hasNestedBraces($query)) {
            throw new InvalidArgumentException('Nested conditional blocks are not allowed.');
        }

        $result = '';

        $conditionalBlocks = preg_split('/(\{[^{}]*\})/', $query, -1, PREG_SPLIT_DELIM_CAPTURE);
        foreach ($conditionalBlocks as $block) {
            $block = trim($block, '{}');

            $result .= $this->parseConditionalBlocks($block, $args);
        }

        return $result;
    }

    protected function parseConditionalBlocks(string $block, array &$args = []): string
    {
        $result = '';
        $blockParts = preg_split('/(\?[\w#]?)/', $block, -1, PREG_SPLIT_DELIM_CAPTURE);
        foreach ($blockParts as $part) {
            if (preg_match('/^\?[\w#]?$/', $part) !== 1) {
                $result .= $part;
                continue;
            }

            $value = array_shift($args);
            if ($value === null) {
                throw new InvalidArgumentException('Number of placeholders does not match number of values');
            }
            if ($value === $this->skip()) {
                return '';
            }

            $type = null;
            if ($part !== '?') {
                $typeString = mb_substr($part, 1);
                $type = PlaceholderTypeEnum::tryFrom($typeString);
                if ($type === null) {
                    $allowedTypes = PlaceholderTypeEnum::getValuesString();
                    throw new InvalidArgumentException("Invalid placeholder type: {$typeString}. Allowed types: {$allowedTypes}");
                }
            }

            $result .= $this->typeFormatter->format($value, $type);
        }

        return $result;
    }

    public function hasNestedBraces(string $string): bool
    {
        return preg_match('/\{[^{}]*\{[^{}]*\}[^{}]*\}/', $string) === 1;
    }

    public function skip(): string
    {
        return self::SKIP;
    }
}
