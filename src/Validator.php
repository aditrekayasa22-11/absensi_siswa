<?php

declare(strict_types=1);

final class Validator
{
    /** @var array<string, mixed> */
    private array $data;

    /** @var array<string, string> */
    private array $errors = [];

    /**
     * @param array<string, mixed> $data
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function required(string $field, string $label): self
    {
        $value = $this->value($field);
        if ($value === '') {
            $this->add($field, "{$label} wajib diisi.");
        }

        return $this;
    }

    public function email(string $field, string $label): self
    {
        $value = $this->value($field);
        if ($value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->add($field, "Format {$label} tidak valid.");
        }

        return $this;
    }

    public function min(string $field, int $length, string $label): self
    {
        $value = $this->value($field);
        if ($value !== '' && mb_strlen($value) < $length) {
            $this->add($field, "{$label} minimal {$length} karakter.");
        }

        return $this;
    }

    public function max(string $field, int $length, string $label): self
    {
        $value = $this->value($field);
        if (mb_strlen($value) > $length) {
            $this->add($field, "{$label} maksimal {$length} karakter.");
        }

        return $this;
    }

    public function numeric(string $field, string $label): self
    {
        $value = $this->value($field);
        if ($value !== '' && !ctype_digit($value)) {
            $this->add($field, "{$label} hanya boleh berisi angka.");
        }

        return $this;
    }

    public function same(string $field, string $otherField, string $label): self
    {
        if ($this->value($field) !== $this->value($otherField)) {
            $this->add($field, "{$label} tidak sama.");
        }

        return $this;
    }

    public function add(string $field, string $message): self
    {
        if (!isset($this->errors[$field])) {
            $this->errors[$field] = $message;
        }

        return $this;
    }

    public function fails(): bool
    {
        return $this->errors !== [];
    }

    /**
     * @return array<string, string>
     */
    public function errors(): array
    {
        return $this->errors;
    }

    private function value(string $field): string
    {
        $value = $this->data[$field] ?? '';
        return is_scalar($value) ? trim((string) $value) : '';
    }
}
