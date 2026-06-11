<?php

declare(strict_types=1);

/** @return list<string> */
function validator_unique_tables(): array
{
    return [
        'users',
        'tags',
        'posts',
        'deals',
        'invoices',
        'social_accounts',
        'notifications',
        'media_files',
    ];
}

function validator_validate(array $data, array $rules): array
{
    $errors = [];

    foreach ($rules as $field => $fieldRules) {
        $value = $data[$field] ?? null;
        $ruleList = is_array($fieldRules) ? $fieldRules : explode('|', (string) $fieldRules);

        foreach ($ruleList as $rule) {
            $rule = (string) $rule;

            if ($rule === 'required' && ($value === null || trim((string) $value) === '')) {
                $errors[$field][] = 'The ' . $field . ' field is required.';
                continue;
            }

            if (($value === null || $value === '') && $rule !== 'required') {
                continue;
            }

            if ($rule === 'email' && filter_var($value, FILTER_VALIDATE_EMAIL) === false) {
                $errors[$field][] = 'The ' . $field . ' must be a valid email.';
            } elseif (strpos($rule, 'min:') === 0) {
                $min = (int) substr($rule, 4);
                if (mb_strlen((string) $value) < $min) {
                    $errors[$field][] = 'The ' . $field . ' must be at least ' . $min . ' characters.';
                }
            } elseif (strpos($rule, 'max:') === 0) {
                $max = (int) substr($rule, 4);
                if (mb_strlen((string) $value) > $max) {
                    $errors[$field][] = 'The ' . $field . ' must not exceed ' . $max . ' characters.';
                }
            } elseif ($rule === 'confirmed') {
                $confirmationKey = $field . '_confirmation';
                if (($data[$confirmationKey] ?? null) !== $value) {
                    $errors[$field][] = 'The ' . $field . ' confirmation does not match.';
                }
            } elseif (strpos($rule, 'unique:') === 0) {
                $parts = explode(',', substr($rule, 7));
                $table = $parts[0] ?? '';
                $column = $parts[1] ?? $field;
                if ($table !== '' && in_array($table, validator_unique_tables(), true)) {
                    $exists = db_fetch(
                        'SELECT 1 FROM ' . db_quote_table($table)
                        . ' WHERE ' . db_quote_column($column) . ' = :value LIMIT 1',
                        ['value' => $value]
                    );
                    if ($exists !== null) {
                        $errors[$field][] = 'The ' . $field . ' has already been taken.';
                    }
                }
            } elseif (strpos($rule, 'in:') === 0) {
                $allowed = explode(',', substr($rule, 3));
                if (!in_array((string) $value, $allowed, true)) {
                    $errors[$field][] = 'The ' . $field . ' is invalid.';
                }
            } elseif ($rule === 'numeric' && !is_numeric($value)) {
                $errors[$field][] = 'The ' . $field . ' must be numeric.';
            } elseif ($rule === 'url' && filter_var($value, FILTER_VALIDATE_URL) === false) {
                $errors[$field][] = 'The ' . $field . ' must be a valid URL.';
            }
        }
    }

    return [
        'valid' => empty($errors),
        'errors' => $errors,
    ];
}
