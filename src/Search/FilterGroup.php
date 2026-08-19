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
     * @param array{filters?: list<array<string, mixed>>} $group
     */
    public static function fromArray(array $group): self
    {
        return new self(array_map(
            static fn(array $filter): Filter => Filter::fromArray($filter),
            $group['filters'] ?? []
        ));
    }
}
