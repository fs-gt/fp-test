<?php

namespace FpDbTest\QueryBuilder;


enum PlaceholderTypeEnum: string
{
    case INT = 'd';
    case FLOAT = 'f';
    case ARRAY = 'a';
    case IDENTIFIER = '#';

    public static function getValuesString(): string
    {
        $values = [];
        foreach (self::cases() as $case) {
            $values[] = $case->value;
        }
        return implode(', ', $values);
    }
}