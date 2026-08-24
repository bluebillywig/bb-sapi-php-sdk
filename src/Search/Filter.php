<?php

namespace BlueBillywig\Search;

/**
 * One condition inside a {@see FilterGroup}.
 *
 * Mirrors the `Filter` interface OVP6 uses (app/services/filter-set.types.ts),
 * so a filterset can be moved between the UI, the API and any SDK unchanged.
 */
final class Filter
{
    /**
     * Always a string or a list of strings after normalisation — see the
     * constructor for why numbers and booleans do not survive as-is.
     *
     * @var string|list<string>
     */
    public readonly string|array $value;

    /**
     * @param string $field OVP field name.
     * @param FilterOperator $operator
     * @param string|int|float|bool|array<string|int|float|bool> $value A single
     *        value, or several for the *AnyOf/*AllOf operators. Numbers and
     *        booleans are normalised to strings here because the backend's
     *        compiler cannot take them raw: a JSON `true` gets mangled into "1"
     *        (which matches nothing, silently), and `false` is dropped outright
     *        by its empty-value guard. 'true'/'false'/decimal strings are what
     *        the OVP sends and what the compiler understands.
     * @param string|null $type Entity type to constrain to: mediaclip, project, search.
     */
    public function __construct(
        public readonly string $field,
        public readonly FilterOperator $operator,
        string|int|float|bool|array $value = '',
        public readonly ?string $type = null,
    ) {
        $this->value = self::normalizeValue($value);
    }

    /**
     * Build from the array shape OVP6 serialises into a filterset.
     *
     * @param array{field?: string, operator?: string, value?: mixed, type?: string|null} $filter
     * @throws \InvalidArgumentException When the operator is missing or not one
     *         {@see FilterOperator} knows. The enum mirrors the operators
     *         formatengine understands; if the OVP has grown a new one, add it
     *         to the enum rather than working around this.
     */
    public static function fromArray(array $filter): self
    {
        $rawOperator = (string) ($filter['operator'] ?? '');
        $operator = FilterOperator::tryFrom($rawOperator);
        if ($operator === null) {
            $message = 'Unknown filter operator "%s". FilterOperator mirrors the operators';
            $message .= ' the OVP/formatengine understand; extend the enum if a new one has been added.';
            throw new \InvalidArgumentException(sprintf($message, $rawOperator));
        }

        $value = $filter['value'] ?? '';
        if (!is_string($value) && !is_array($value) && !is_int($value) && !is_float($value) && !is_bool($value)) {
            // Junk (an object, null) is treated as "no value", so the filter is
            // dropped by isEmpty() instead of stringifying into garbage.
            $value = '';
        }

        return new self(
            (string) ($filter['field'] ?? ''),
            $operator,
            $value,
            isset($filter['type']) && $filter['type'] !== '' ? (string) $filter['type'] : null,
        );
    }

    /**
     * @return list<string>
     */
    public function values(): array
    {
        return is_array($this->value) ? array_values($this->value) : [$this->value];
    }

    /**
     * A filter with no value is not a filter — it is skipped, exactly as
     * formatengine skips on `empty($searchData['value'])`. The presence
     * operators are the exception: they are meaningful without one (and are
     * sent with the '*' placeholder the backend requires — see FilterSet).
     */
    public function isEmpty(): bool
    {
        if ($this->operator->ignoresValue()) {
            return false;
        }

        foreach ($this->values() as $value) {
            if ($value !== '') {
                return false;
            }
        }

        return true;
    }

    /**
     * @return string|list<string>
     */
    private static function normalizeValue(string|int|float|bool|array $value): string|array
    {
        if (is_array($value)) {
            $normalized = [];
            foreach ($value as $item) {
                if (is_bool($item)) {
                    $normalized[] = $item ? 'true' : 'false';
                } elseif (is_string($item) || is_int($item) || is_float($item)) {
                    $normalized[] = (string) $item;
                }
                // Non-scalar members are junk; dropping them beats sending "Array".
            }

            return $normalized;
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        return (string) $value;
    }
}
