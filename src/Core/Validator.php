<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Validates a flat array of string data against a set of rules.
 * Returns field => first-error-message; empty array means valid.
 *
 * Supported rules:
 *   required          — must not be empty after trimming
 *   email             — must pass FILTER_VALIDATE_EMAIL
 *   min:N             — mb_strlen >= N  (character count)
 *   max:N             — mb_strlen <= N  (character count)
 *   min_bytes:N       — strlen >= N     (byte count, e.g. bcrypt passwords)
 *   max_bytes:N       — strlen <= N     (byte count)
 *   confirmed         — must equal data[field_confirmation]
 */
final class Validator
{
    /**
     * @param array<string, mixed>        $data
     * @param array<string, list<string>> $rules
     * @return array<string, string>
     */
    public static function make(array $data, array $rules): array
    {
        $errors = [];

        foreach ($rules as $field => $fieldRules) {
            $value = isset($data[$field]) ? (string) $data[$field] : '';

            foreach ($fieldRules as $rule) {
                $error = self::applyRule($field, $value, $rule, $data);
                if ($error !== null) {
                    $errors[$field] = $error;
                    break;
                }
            }
        }

        return $errors;
    }

    /** @param array<string, mixed> $data */
    private static function applyRule(string $field, string $value, string $rule, array $data): ?string
    {
        [$name, $param] = str_contains($rule, ':')
            ? explode(':', $rule, 2)
            : [$rule, ''];

        return match ($name) {
            'required'  => trim($value) === '' ? 'This field is required.' : null,
            'email'     => filter_var($value, FILTER_VALIDATE_EMAIL) === false
                               ? 'Enter a valid email address.' : null,
            'min'       => mb_strlen($value) < (int) $param
                               ? "Must be at least {$param} characters." : null,
            'max'       => mb_strlen($value) > (int) $param
                               ? "Must not exceed {$param} characters." : null,
            'min_bytes' => strlen($value) < (int) $param
                               ? "Must be at least {$param} characters." : null,
            'max_bytes' => strlen($value) > (int) $param
                               ? "Must not exceed {$param} characters." : null,
            'confirmed' => $value !== (string) ($data[$field . '_confirmation'] ?? '')
                               ? 'Confirmation does not match.' : null,
            default     => null,
        };
    }
}
