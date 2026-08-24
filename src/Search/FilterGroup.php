<?php

namespace BlueBillywig\Search;

/**
 * A set of filters that are OR-ed together.
 *
 * Groups themselves are AND-ed by {@see FilterSet}. That two-level structure is
 * the shape OVP6 produces and formatengine consumes: "any of these conditions,
 * and any of those".
 */
final class FilterGroup
{
    /**
     * @param list<Filter> $filters
     */
    public function __construct(public readonly array $filters = [])
    {
    }

    /**
     * Tolerant of junk: entries that are not arrays are skipped, because this is
     * an ingestion point for external data (a stored filterset, a request body),
     * and one malformed entry should not take the whole filterset down.
     *
     * @param array{filters?: mixed} $group
     */
    public static function fromArray(array $group): self
    {
        $rawFilters = $group['filters'] ?? [];
        if (!is_array($rawFilters)) {
            $rawFilters = [];
        }

        $filters = [];
        foreach ($rawFilters as $filter) {
            if (is_array($filter)) {
                $filters[] = Filter::fromArray($filter);
            }
        }

        return new self($filters);
    }
}
