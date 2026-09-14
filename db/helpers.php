<?php

function pgArrayParse($value): array
{
    if (!$value || $value === '{}') {
        return [];
    }

    return array_map('trim', explode(',', trim($value, '{}')));
}

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}
