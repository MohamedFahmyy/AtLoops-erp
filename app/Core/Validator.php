<?php

declare(strict_types=1);

namespace App\Core;

use PDO;

class Validator
{
    private array $data;
    private array $rules;
    private array $errors = [];

    public function __construct(array $data, array $rules)
    {
        $this->data = $data;
        $this->rules = $rules;
    }

    public static function make(array $data, array $rules): self
    {
        return new self($data, $rules);
    }

    public function validate(): bool
    {
        $this->errors = [];

        foreach ($this->rules as $field => $ruleList) {
            $rules = is_array($ruleList) ? $ruleList : explode('|', $ruleList);
            $value = $this->data[$field] ?? null;

            foreach ($rules as $rule) {
                $parameters = [];
                if (str_contains($rule, ':')) {
                    [$ruleName, $paramStr] = explode(':', $rule, 2);
                    $parameters = explode(',', $paramStr);
                } else {
                    $ruleName = $rule;
                }

                $this->applyRule($field, $value, $ruleName, $parameters);
            }
        }

        return empty($this->errors);
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public function firstError(string $field): ?string
    {
        return $this->errors[$field][0] ?? null;
    }

    private function addError(string $field, string $message): void
    {
        $this->errors[$field][] = $message;
    }

    private function applyRule(string $field, mixed $value, string $rule, array $parameters): void
    {
        $isEmpty = ($value === null || $value === '' || (is_array($value) && empty($value)));

        if ($rule === 'required') {
            if ($isEmpty) {
                $this->addError($field, __("validation.required", ['field' => $field]));
            }
            return;
        }

        // If field is empty and not required, skip other rules
        if ($isEmpty) {
            return;
        }

        switch ($rule) {
            case 'email':
                if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $this->addError($field, __("validation.email", ['field' => $field]));
                }
                break;

            case 'numeric':
                if (!is_numeric($value)) {
                    $this->addError($field, __("validation.numeric", ['field' => $field]));
                }
                break;

            case 'integer':
                if (filter_var($value, FILTER_VALIDATE_INT) === false) {
                    $this->addError($field, __("validation.integer", ['field' => $field]));
                }
                break;

            case 'min':
                $min = (int)($parameters[0] ?? 0);
                if (is_numeric($value) && (float)$value < $min) {
                    $this->addError($field, __("validation.min_numeric", ['field' => $field, 'min' => $min]));
                } elseif (is_string($value) && mb_strlen($value) < $min) {
                    $this->addError($field, __("validation.min_string", ['field' => $field, 'min' => $min]));
                }
                break;

            case 'max':
                $max = (int)($parameters[0] ?? 0);
                if (is_numeric($value) && (float)$value > $max) {
                    $this->addError($field, __("validation.max_numeric", ['field' => $field, 'max' => $max]));
                } elseif (is_string($value) && mb_strlen($value) > $max) {
                    $this->addError($field, __("validation.max_string", ['field' => $field, 'max' => $max]));
                }
                break;

            case 'in':
                if (!in_array((string)$value, $parameters, true)) {
                    $this->addError($field, __("validation.in", ['field' => $field]));
                }
                break;

            case 'date':
                if (!strtotime((string)$value)) {
                    $this->addError($field, __("validation.date", ['field' => $field]));
                }
                break;

            case 'confirmed':
                $confirmationField = $field . '_confirmation';
                if (($this->data[$confirmationField] ?? null) !== $value) {
                    $this->addError($field, __("validation.confirmed", ['field' => $field]));
                }
                break;

            case 'unique':
                $table = $parameters[0] ?? '';
                $column = $parameters[1] ?? $field;
                $ignoreId = $parameters[2] ?? null;

                if ($table && $column) {
                    try {
                        $pdo = Database::pdo();
                        $sql = "SELECT COUNT(*) FROM `{$table}` WHERE `{$column}` = :val";
                        $params = ['val' => $value];

                        if ($ignoreId !== null && $ignoreId !== '') {
                            $sql .= " AND id != :ignore_id";
                            $params['ignore_id'] = $ignoreId;
                        }

                        $stmt = $pdo->prepare($sql);
                        $stmt->execute($params);
                        if ((int)$stmt->fetchColumn() > 0) {
                            $this->addError($field, __("validation.unique", ['field' => $field]));
                        }
                    } catch (\Throwable $e) {
                        // Fail safe on validation error
                    }
                }
                break;
        }
    }
}
