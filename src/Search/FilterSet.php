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
     * Compile to the Solr query SAPI expects. Empty when nothing is filtered.
     */
    public function toSolrQuery(): string
    {
        return SolrQueryCompiler::compile($this);
    }

    public function isEmpty(): bool
    {
        return $this->toSolrQuery() === '';
    }
}
