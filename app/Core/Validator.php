<?php

namespace App\Core;

class Validator
{
    protected ?Database $db;

    protected array $errors = [];

    public function __construct(?Database $db = null)
    {
        $this->db = $db;
    }

    public function validate(array $data, array $rules): array
    {
        $this->errors = [];

        foreach ($rules as $field => $ruleString) {
            $value = $data[$field] ?? null;
            $fieldRules = array_filter(explode('|', $ruleString));

            foreach ($fieldRules as $rule) {
                [$name, $parameter] = array_pad(explode(':', $rule, 2), 2, null);

                if (!$this->applyRule($field, $value, $name, $parameter, $data)) {
                    break;
                }
            }
        }

        return [
            'valid' => $this->errors === [],
            'errors' => $this->errors,
        ];
    }

    protected function applyRule(string $field, mixed $value, string $rule, ?string $parameter, array $data): bool
    {
        return match ($rule) {
            'required' => $this->validateRequired($field, $value),
            'email' => $this->validateEmail($field, $value),
            'min' => $this->validateMin($field, $value, (int) $parameter),
            'max' => $this->validateMax($field, $value, (int) $parameter),
            'match' => $this->validateMatch($field, $value, $parameter, $data),
            'unique' => $this->validateUnique($field, $value, $parameter),
            default => true,
        };
    }

    protected function validateRequired(string $field, mixed $value): bool
    {
        if ($value === null || (is_string($value) && trim($value) === '') || $value === []) {
            $this->addError($field, 'The ' . $field . ' field is required.');
            return false;
        }

        return true;
    }

    protected function validateEmail(string $field, mixed $value): bool
    {
        if ($value === null || $value === '') {
            return true;
        }

        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->addError($field, 'The ' . $field . ' field must be a valid email address.');
            return false;
        }

        return true;
    }

    protected function validateMin(string $field, mixed $value, int $min): bool
    {
        if ($value === null || mb_strlen((string) $value) >= $min) {
            return true;
        }

        $this->addError($field, 'The ' . $field . ' field must be at least ' . $min . ' characters.');
        return false;
    }

    protected function validateMax(string $field, mixed $value, int $max): bool
    {
        if ($value === null || mb_strlen((string) $value) <= $max) {
            return true;
        }

        $this->addError($field, 'The ' . $field . ' field must be at most ' . $max . ' characters.');
        return false;
    }

    protected function validateMatch(string $field, mixed $value, ?string $parameter, array $data): bool
    {
        $targetField = $parameter ?? '';
        $targetValue = $data[$targetField] ?? null;

        if ($value !== $targetValue) {
            $this->addError($field, 'The ' . $field . ' field must match ' . $targetField . '.');
            return false;
        }

        return true;
    }

    protected function validateUnique(string $field, mixed $value, ?string $parameter): bool
    {
        if ($value === null || $value === '' || $parameter === null || $this->db === null) {
            return true;
        }

        [$table, $column] = array_pad(explode(',', $parameter, 2), 2, null);

        if (!$table || !$column) {
            return true;
        }

        $result = $this->db->fetch(
            sprintf('SELECT COUNT(*) AS aggregate FROM %s WHERE %s = :value LIMIT 1', $table, $column),
            ['value' => $value]
        );

        if (($result['aggregate'] ?? 0) > 0) {
            $this->addError($field, 'The ' . $field . ' field has already been taken.');
            return false;
        }

        return true;
    }

    protected function addError(string $field, string $message): void
    {
        $this->errors[$field][] = $message;
    }
}
