<?php

class InputValidator
{
    private array $data;
    private array $errors = [];
    private array $validated = [];

    public function __construct(array $source)
    {
        $this->data = $source;
    }

    /* ===========================
     * Basic helpers
     * =========================== */

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->data);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }

    public function all(): array
    {
        return $this->validated;
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public function failed(): bool
    {
        return !empty($this->errors);
    }

    public function passes(): bool
    {
        return empty($this->errors);
    }

    /* ===========================
     * Core validation rules
     * =========================== */

    public function required(string $key): self
    {
        if (
            !isset($this->data[$key]) ||
            (is_string($this->data[$key]) && trim($this->data[$key]) === '') ||
            (is_array($this->data[$key]) && empty($this->data[$key]))
        ) {
            $this->errors[$key][] = 'This field is required.';
        }

        return $this;
    }

    public function string(string $key): self
    {
        if (isset($this->data[$key]) && !is_string($this->data[$key])) {
            $this->errors[$key][] = 'Must be a string.';
        } else {
            $this->validated[$key] = trim((string) $this->data[$key]);
        }

        return $this;
    }

    public function int(string $key): self
    {
        if (isset($this->data[$key]) && filter_var($this->data[$key], FILTER_VALIDATE_INT) === false) {
            $this->errors[$key][] = 'Must be an integer.';
        } else {
            $this->validated[$key] = (int) $this->data[$key];
        }

        return $this;
    }

    public function float(string $key): self
    {
        if (isset($this->data[$key]) && filter_var($this->data[$key], FILTER_VALIDATE_FLOAT) === false) {
            $this->errors[$key][] = 'Must be a number.';
        } else {
            $this->validated[$key] = (float) $this->data[$key];
        }

        return $this;
    }

    public function bool(string $key): self
    {
        if (isset($this->data[$key])) {
            $this->validated[$key] = filter_var(
                $this->data[$key],
                FILTER_VALIDATE_BOOLEAN,
                FILTER_NULL_ON_FAILURE
            );

            if ($this->validated[$key] === null) {
                $this->errors[$key][] = 'Must be boolean.';
            }
        }

        return $this;
    }

    public function array(string $key): self
    {
        if (isset($this->data[$key]) && !is_array($this->data[$key])) {
            $this->errors[$key][] = 'Must be an array.';
        } else {
            $this->validated[$key] = $this->data[$key] ?? [];
        }

        return $this;
    }

    /* ===========================
     * Constraint rules
     * =========================== */

    public function min(string $key, int|float $min): self
    {
        if (isset($this->validated[$key]) && $this->validated[$key] < $min) {
            $this->errors[$key][] = "Must be at least {$min}.";
        }

        return $this;
    }

    public function max(string $key, int|float $max): self
    {
        if (isset($this->validated[$key]) && $this->validated[$key] > $max) {
            $this->errors[$key][] = "Must not exceed {$max}.";
        }

        return $this;
    }

    public function in(string $key, array $allowed): self
    {
        if (isset($this->validated[$key]) && !in_array($this->validated[$key], $allowed, true)) {
            $this->errors[$key][] = 'Invalid value.';
        }

        return $this;
    }

    /* ===========================
     * Date & email
     * =========================== */

    public function date(string $key, string $format = 'Y-m-d'): self
    {
        if (isset($this->data[$key])) {
            $d = DateTime::createFromFormat($format, $this->data[$key]);
            if (!$d || $d->format($format) !== $this->data[$key]) {
                $this->errors[$key][] = 'Invalid date format.';
            } else {
                $this->validated[$key] = $d;
            }
        }

        return $this;
    }

    public function email(string $key): self
    {
        if (isset($this->data[$key]) && !filter_var($this->data[$key], FILTER_VALIDATE_EMAIL)) {
            $this->errors[$key][] = 'Invalid email address.';
        } else {
            $this->validated[$key] = $this->data[$key];
        }

        return $this;
    }

    public function custom(string $key, callable $callback): self
    {
        if (!isset($this->validated[$key]) && isset($this->data[$key])) {
            $this->validated[$key] = $this->data[$key];
        }

        if (isset($this->validated[$key])) {
            $result = $callback($this->validated[$key], $this->data);

            if ($result !== true) {
                $this->errors[$key][] = is_string($result)
                    ? $result
                    : 'Invalid value.';
            }
        }

        return $this;
    }

    /* ===========================
    * Nested key helpers
    * =========================== */

    private function getValueByPath(array $data, string $path): mixed
    {
        foreach (explode('.', $path) as $segment) {
            if (!isset($data[$segment])) {
                return null;
            }
            $data = $data[$segment];
        }
        return $data;
    }

    private function resolveWildcardPaths(string $path): array
    {
        if (!str_contains($path, '*')) {
            return [$path];
        }

        $parts = explode('.', $path);
        $paths = [''];

        foreach ($parts as $part) {
            $newPaths = [];

            foreach ($paths as $base) {
                if ($part === '*') {
                    $array = $this->getValueByPath($this->data, trim($base, '.'));
                    if (!is_array($array)) {
                        continue;
                    }

                    foreach (array_keys($array) as $key) {
                        $newPaths[] = trim("$base.$key", '.');
                    }
                } else {
                    $newPaths[] = trim("$base.$part", '.');
                }
            }

            $paths = $newPaths;
        }

        return $paths;
    }

    public function requiredNested(string $path): self
    {
        foreach ($this->resolveWildcardPaths($path) as $resolved) {
            $value = $this->getValueByPath($this->data, $resolved);

            if ($value === null || $value === '') {
                $this->errors[$resolved][] = 'This field is required.';
            } else {
                $this->validated[$resolved] = $value;
            }
        }

        return $this;
    }

    public function floatNested(string $path): self
    {
        foreach ($this->resolveWildcardPaths($path) as $resolved) {
            $value = $this->getValueByPath($this->data, $resolved);

            if (!is_numeric($value)) {
                $this->errors[$resolved][] = 'Must be a number.';
            } else {
                $this->validated[$resolved] = (float) $value;
            }
        }

        return $this;
    }

    public function customNested(string $path, callable $callback): self
    {
        foreach ($this->resolveWildcardPaths($path) as $resolved) {
            $value = $this->getValueByPath($this->data, $resolved);

            if ($value !== null) {
                $result = $callback($value, $resolved, $this->data);

                if ($result !== true) {
                    $this->errors[$resolved][] = is_string($result)
                        ? $result
                        : 'Invalid value.';
                }
            }
        }

        return $this;
    }

    public function json(string $key): self
    {
        if (!isset($this->data[$key])) {
            $this->errors[$key][] = 'JSON value is required.';
            return $this;
        }

        $decoded = json_decode($this->data[$key], true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->errors[$key][] = 'Invalid JSON.';
            return $this;
        }

        if (!is_array($decoded)) {
            $this->errors[$key][] = 'JSON must decode to an object.';
            return $this;
        }

        $this->validated[$key] = $decoded;

        return $this;
    }

    public function jsonOnly(string $key, array $allowedKeys): self
    {
        if (!isset($this->validated[$key])) {
            return $this;
        }

        $extra = array_diff(array_keys($this->validated[$key]), $allowedKeys);

        if (!empty($extra)) {
            $this->errors[$key][] =
                'Unexpected keys: ' . implode(', ', $extra);
        }

        return $this;
    }

    public function jsonRequiredString(string $key, array $requiredKeys): self
    {
        if (!isset($this->validated[$key]) || !is_array($this->validated[$key])) {
            return $this;
        }

        foreach ($requiredKeys as $jsonKey) {
            if (
                !array_key_exists($jsonKey, $this->validated[$key]) ||
                !is_string($this->validated[$key][$jsonKey]) ||
                trim($this->validated[$key][$jsonKey]) === ''
            ) {
                $this->errors["{$key}.{$jsonKey}"][] =
                    "Must be a non-empty string.";
            }
        }

        return $this;
    }

    /* ===========================
     * Finalize
     * =========================== */

    public function validate(): void
    {
        if ($this->failed()) {
            throw new InvalidArgumentException(json_encode($this->errors, JSON_THROW_ON_ERROR));
        }
    }
}


/*
    $input = new InputValidator($_POST);

    REGULAR USAGE
    $input
        ->required('allPortion')->string('allPortion')->in('allPortion', ['100%', '50%', '25%'])
        ->bool('allChecked')
        ->array('staff')
        ->array('portion');

    $input->validate();

    $data = $input->all(); // sanitized & typed data only

    CUSTOM USAGE
    $input->custom('percent', function ($value) {
        if ($value % 5 !== 0) {
            return 'Percentage must be in multiples of 5.';
        }
    });

    NESTED USAGE
    $_POST = [
        'staff' => [
            ['id' => 12, 'percent' => 50],
            ['id' => 15, 'percent' => 120] // 👈 invalid
        ]
    ];

    $input
        ->requiredNested('staff.*.id')
        ->floatNested('staff.*.percent')
        ->customNested('staff.*.percent', function ($value) {
            if ($value <= 0 || $value > 100) {
                return 'Percentage must be between 1 and 100.';
            }
            return true;
        });

    $input->validate();

    RESULT
    [
        "staff.1.percent" => [
            "Percentage must be between 1 and 100."
        ]
    ]

    JSON USAGE
    $input = new InputValidator($_POST);

    $input
        ->required('id')
        ->json('id')
        ->jsonRequiredString('id', ['sub', 'type']);

    $input->validate();

    $data = $input->all();





*/