<?php

namespace BlueBillywig\Search;

/**
 * A filterset: groups of conditions, AND-ed together.
 *
 * The same structure OVP6 builds in its filter UI and formatengine compiles
 * server-side, so a filterset can be moved between the two unchanged. Build one
 * fluently:
 *
 *     $filterSet = FilterSet::create()
 *         ->where('status', FilterOperator::Is, 'published')
 *         ->where('title', FilterOperator::Contains, 'koert');
 *
 *     $sdk->mediaclip->search($filterSet);
 *
 * or hand back a filterset the OVP produced:
 *
 *     $filterSet = FilterSet::fromArray($json);
 */
final class FilterSet
{
    /**
     * @param list<FilterGroup> $groups
     */
    public function __construct(public readonly array $groups = [])
    {
    }

    public static function create(): self
    {
        return new self();
    }

    /**
     * Accepts either a bare filterset (a list of groups) or the
     * `{type: 'SearchRequest', filterSet: [...]}` envelope OVP6 sends.
     *
     * @param array<mixed> $filterSet
     */
    public static function fromArray(array $filterSet): self
    {
        if (($filterSet['type'] ?? null) === 'SearchRequest' && isset($filterSet['filterSet'])) {
            $filterSet = $filterSet['filterSet'];
        }

        return new self(array_values(array_map(
            static fn(array $group): FilterGroup => FilterGroup::fromArray($group),
            $filterSet
        )));
    }

    /**
     * Add a condition as its own group, so it is AND-ed with the rest.
     *
     * @param string|list<string> $value
     */
    public function where(string $field, FilterOperator $operator, string|array $value = '', ?string $type = null): self
    {
        return $this->andGroup(new Filter($field, $operator, $value, $type));
    }

    /**
     * Add several conditions as one group, so they are OR-ed with each other and
     * AND-ed with the other groups.
     */
    public function andGroup(Filter ...$filters): self
    {
        return new self([...$this->groups, new FilterGroup(array_values($filters))]);
    }

    /**
     * The wire format: the structure SAPI's `filterset` parameter expects.
     *
     * Deliberately NOT compiled to a Solr query here. SAPI compiles filtersets
     * itself — `/sapi/mediaclip?filterset={json}` — using the same
     * SearchRequestHelper that serves the OVP, so compiling client-side would be
     * a second implementation of semantics that already exist on the server, free
     * to drift from them. And a drifted filter does not fail loudly: SAPI answers
     * HTTP 200 with an empty envelope, which reads as "no results".
     *
     * Verified equivalent against a live publication: `filterset` and a
     * hand-compiled `fq` return identical counts for every operator tried.
     *
     * @return list<array{filters: list<array<string, mixed>>}>
     */
    public function toArray(): array
    {
        return array_values(array_map(
            static fn(FilterGroup $group): array => [
                'filters' => array_values(array_map(
                    static fn(Filter $filter): array => array_filter(
                        [
                            'field' => $filter->field,
                            'operator' => $filter->operator->value,
                            'value' => $filter->value,
                            'type' => $filter->type,
                        ],
                        static fn($value): bool => $value !== null
                    ),
                    array_values(array_filter(
                        $group->filters,
                        static fn(Filter $filter): bool => !$filter->isEmpty()
                    ))
                )),
            ],
            array_values(array_filter(
                $this->groups,
                static fn(FilterGroup $group): bool => self::groupHasFilters($group)
            ))
        ));
    }

    public function toJson(): string
    {
        return (string) json_encode($this->toArray());
    }

    public function isEmpty(): bool
    {
        return $this->toArray() === [];
    }

    private static function groupHasFilters(FilterGroup $group): bool
    {
        foreach ($group->filters as $filter) {
            if (!$filter->isEmpty()) {
                return true;
            }
        }

        return false;
    }
}
