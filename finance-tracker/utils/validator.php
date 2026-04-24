<?php
/**
 * File: utils/validator.php
 * Purpose: Input validation helpers
 */

class Validator
{
    private array $errors = [];
    private array $data   = [];

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    // ── Rule methods ──────────────────────────────────────

    public function required(string $field, string $label = ''): self
    {
        $label = $label ?: ucfirst(str_replace('_', ' ', $field));
        $value = trim($this->data[$field] ?? '');
        if ($value === '') {
            $this->errors[$field] = "$label is required.";
        }
        return $this;
    }

    public function email(string $field, string $label = ''): self
    {
        $label = $label ?: ucfirst($field);
        $value = trim($this->data[$field] ?? '');
        if ($value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field] = "$label must be a valid email address.";
        }
        return $this;
    }

    public function minLength(string $field, int $min, string $label = ''): self
    {
        $label = $label ?: ucfirst(str_replace('_', ' ', $field));
        $value = $this->data[$field] ?? '';
        if (strlen($value) > 0 && strlen($value) < $min) {
            $this->errors[$field] = "$label must be at least $min characters.";
        }
        return $this;
    }

    public function maxLength(string $field, int $max, string $label = ''): self
    {
        $label = $label ?: ucfirst(str_replace('_', ' ', $field));
        $value = $this->data[$field] ?? '';
        if (strlen($value) > $max) {
            $this->errors[$field] = "$label must not exceed $max characters.";
        }
        return $this;
    }

    public function numeric(string $field, string $label = ''): self
    {
        $label = $label ?: ucfirst(str_replace('_', ' ', $field));
        $value = $this->data[$field] ?? '';
        if ($value !== '' && !is_numeric($value)) {
            $this->errors[$field] = "$label must be a number.";
        }
        return $this;
    }

    public function positive(string $field, string $label = ''): self
    {
        $label = $label ?: ucfirst(str_replace('_', ' ', $field));
        $value = $this->data[$field] ?? '';
        if (is_numeric($value) && (float)$value <= 0) {
            $this->errors[$field] = "$label must be greater than zero.";
        }
        return $this;
    }

    public function inList(string $field, array $list, string $label = ''): self
    {
        $label = $label ?: ucfirst(str_replace('_', ' ', $field));
        $value = $this->data[$field] ?? '';
        if ($value !== '' && !in_array($value, $list, true)) {
            $this->errors[$field] = "$label contains an invalid value.";
        }
        return $this;
    }

    public function date(string $field, string $format = 'Y-m-d', string $label = ''): self
    {
        $label = $label ?: ucfirst(str_replace('_', ' ', $field));
        $value = $this->data[$field] ?? '';
        if ($value !== '') {
            $dt = DateTime::createFromFormat($format, $value);
            if (!$dt || $dt->format($format) !== $value) {
                $this->errors[$field] = "$label is not a valid date.";
            }
        }
        return $this;
    }

    public function matches(string $field, string $otherField, string $label = ''): self
    {
        $label = $label ?: ucfirst(str_replace('_', ' ', $field));
        if (($this->data[$field] ?? '') !== ($this->data[$otherField] ?? '')) {
            $this->errors[$field] = "$label does not match.";
        }
        return $this;
    }

    // ── Result methods ────────────────────────────────────

    public function passes(): bool
    {
        return empty($this->errors);
    }

    public function fails(): bool
    {
        return !$this->passes();
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public function firstError(): string
    {
        return reset($this->errors) ?: '';
    }

    /**
     * Return sanitized value for a field.
     */
    public function get(string $field, mixed $default = ''): mixed
    {
        return $this->data[$field] ?? $default;
    }
}
