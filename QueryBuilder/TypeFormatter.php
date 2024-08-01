<?php

namespace FpDbTest\QueryBuilder;

use mysqli;

class TypeFormatter
{
    protected mysqli $mysqli;

    public function __construct(mysqli $mysqli)
    {
        $this->mysqli = $mysqli;
    }

    public function format(mixed $value, ?PlaceholderTypeEnum $type = null): string|int|float
    {
        return match ($type) {
            PlaceholderTypeEnum::INT => is_null($value) ? 'NULL' : (int) $value,
            PlaceholderTypeEnum::FLOAT => is_null($value) ? 'NULL' : (float) $value,
            PlaceholderTypeEnum::ARRAY => $this->formatArray($value),
            PlaceholderTypeEnum::IDENTIFIER => $this->formatIdentifier($value),
            default => $this->formatUniversal($value),
        };
    }

    public function formatArray(array $array): string
    {
        $result = [];
        foreach ($array as $key => $value) {
            if (is_int($key)) {
                $result[] = $this->formatUniversal($value);
            } else {
                $result[] = $this->formatIdentifier($key) . ' = ' . $this->formatUniversal($value);
            }
        }
        return implode(', ', $result);
    }

    public function formatIdentifier(array|string $value): string
    {
        if (is_array($value)) {
            $result = [];
            foreach ($value as $valueItem) {
                $result[] = "`" . $this->mysqli->real_escape_string($valueItem) . "`";
            }
            return implode(', ', $result);
        }

        return "`" . $this->mysqli->real_escape_string($value) . "`";
    }

    public function formatUniversal(mixed $value): string|int|float
    {
        if (is_null($value)) {
            return 'NULL';
        }

        if (is_float($value)) {
            return (float) $value;
        }
        if (is_int($value)) {
            return (int) $value;
        }
        if (is_bool($value)) {
            return $value ? '1' : '0';
        }
        if (is_string($value)) {
            return "'" . $this->mysqli->real_escape_string($value) . "'";
        }

        return $value;
    }
}
