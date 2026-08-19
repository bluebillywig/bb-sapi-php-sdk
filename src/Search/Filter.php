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
     * @param string                $field    OVP field name; translated by {@see FieldMap}.
     * @param FilterOperator        $operator
     * @param string|list<string>   $value    A single value, or several for the *AnyOf/*AllOf operators.
     * @param string|null           $type     Entity type to constrain to: mediaclip, project, search.
     */
    public function __construct(
        public readonly string $field,
        public readonly FilterOperator $operator,
        public readonly string|array $value = '',
        public readonly ?string $type = null,
    ) {
    }

    /**
     * Build from the array shape OVP6 serialises into a filterset.
     *
     * @param array{field: string, operator: string, value?: string|list<string>, type?: string|null} $filter
     */
    public static function fromArray(array $filter): self
    {
        return new self(
            (string) $filter['field'],
            FilterOperator::from((string) $filter['operator']),
            $filter['value'] ?? '',
            isset($filter['type']) && $filter['type'] !== '' ? (string) $filter['type'] : null,
        );
    }

    /**
     * @return list<string>
     */
    public function values(): array
    {
        return array_values(array_map('strval', is_array($this->value) ? $this->value : [$this->value]));
    }

    /**
     * A filter with no value is not a filter — it is skipped, exactly as
     * formatengine skips on `empty($searchData['value'])`. The presence
     * operators are the exception: they are meaningful without one.
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
}
