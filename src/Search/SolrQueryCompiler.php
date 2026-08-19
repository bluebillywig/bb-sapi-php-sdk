<?php

namespace BlueBillywig\Search;

/**
 * Compiles a filterset into the Solr query string SAPI expects.
 *
 * A faithful port of `SearchRequestHelper` in formatengine, which is the
 * authority for these semantics. OVP6 builds filtersets, formatengine compiles
 * them; every other client has been re-deriving this by hand, which is how
 * integrations end up with searches that quietly return nothing.
 *
 * Structure: filters inside a group are OR-ed, groups are AND-ed.
 *
 *     (a OR b) AND (c)
 *
 * The subtleties that are easy to get wrong, and the reason this belongs in the
 * SDK rather than in each integration:
 *
 *  - Field SUFFIX decides semantics. `_txtmulti`/`_strmulti` fields test
 *    cardinality (`[1 TO *]`), `_bool` fields need the negated form for false,
 *    and everything else is a term match.
 *  - `contains` on a single-valued field is an exact match OR a substring match
 *    with escaped spaces. Quoting alone finds nothing unless the caller typed
 *    the whole value — searching "Koert" would never find "Koert Live".
 *  - `fulltext` carries no field name at all.
 *  - Range operators take an allowlisted value, so a caller-supplied string
 *    cannot break out of `[X TO Y]`.
 */
final class SolrQueryCompiler
{
    /**
     * Entity type filter values, keyed by the `type` a filter may carry.
     *
     * @var array<string, string|list<string>>
     */
    private const TYPE_FILTERS = [
        'mediaclip' => 'MediaClip',
        'project' => 'Project',
        'search' => ['Project', 'MediaClip', 'MediaClipList'],
    ];

    /**
     * Compile a whole filterset.
     *
     * @param FilterSet $filterSet
     * @return string The query, or '' when nothing is filtered.
     */
    public static function compile(FilterSet $filterSet): string
    {
        $groupQueries = [];

        foreach ($filterSet->groups as $group) {
            $statements = [];

            foreach ($group->filters as $filter) {
                if ($filter->isEmpty()) {
                    continue;
                }

                $field = FieldMap::resolve($filter->field);
                $values = $filter->values();

                // A boolean field asked for BOTH true and false excludes nothing,
                // so emitting a clause would only slow the query down.
                if (str_contains($field, '_boolean') && count($values) > 1) {
                    continue;
                }

                $typeFilter = $filter->type !== null ? (self::TYPE_FILTERS[$filter->type] ?? $filter->type) : null;

                $parts = [];
                foreach ($values as $value) {
                    $parts[] = self::statement($field, $filter->operator, $value, $typeFilter);
                }

                $statements[] = implode($filter->operator->joinsWithAnd() ? ' AND ' : ' OR ', $parts);
            }

            if ($statements === []) {
                continue;
            }

            $groupQueries[] = count($statements) > 1
                ? '(' . implode(') OR (', $statements) . ')'
                : $statements[0];
        }

        if ($groupQueries === []) {
            return '';
        }

        return '(' . implode(') AND (', $groupQueries) . ')';
    }

    /**
     * Build one Solr statement.
     *
     * @param string|list<string>|null $typeFilter
     */
    public static function statement(
        string $field,
        FilterOperator $operator,
        string $value,
        string|array|null $typeFilter = null
    ): string {
        $value = self::sanitize($value);

        $statement = match ($operator) {
            FilterOperator::Is,
            FilterOperator::IsAnyOf,
            FilterOperator::ContainsAllOf => self::equality($field, $value),

            FilterOperator::IsNot,
            FilterOperator::IsNotAnyOf => '( *:* -' . $field . ':"' . $value . '")',

            FilterOperator::IsEmpty => '( *:* -' . $field . ':*)',
            FilterOperator::IsNotEmpty => '( ' . $field . ':["" TO *])',

            FilterOperator::Contains => $field . ':"' . $value . '" OR '
                . $field . ':*' . self::escapeSpaces($value) . '*',
            FilterOperator::ContainsAnyOf => $field . ':"' . $value . '"',

            FilterOperator::DoesNotContain,
            FilterOperator::DoesNotContainAnyOf => self::negation($field, $value),

            FilterOperator::IsBefore,
            FilterOperator::IsSmallerThan => $field . ':[ * TO ' . self::escapeRange($value) . ']',

            FilterOperator::IsAfter,
            FilterOperator::IsGreaterThan => $field . ':[' . self::escapeRange($value) . ' TO *]',

            FilterOperator::IsInTheLast => $field . ':[NOW-' . self::escapeRange($value) . ' TO NOW]',
            FilterOperator::IsNotInTheLast => '(*:* -' . $field . ':[NOW-' . self::escapeRange($value) . ' TO NOW])',
        };

        // Fulltext is the default search field, so it carries no field name.
        if ($field === 'fulltext') {
            $statement = str_replace('fulltext:', '', $statement);
        }

        // Interactivity is presence of a timeline, not a stored flag.
        if ($field === '{!key=hasInteractivity}timeline_multistring') {
            if ($value === 'true') {
                $statement = '(timeline_multistring:[* TO *])';
            } elseif ($value === 'false') {
                $statement = '(*:* AND -timeline_multistring:[* TO *])';
            }
        }

        if ($typeFilter !== null && $typeFilter !== '' && $typeFilter !== []) {
            $statement = is_array($typeFilter)
                ? '((typeSort:' . implode(' OR typeSort:', $typeFilter) . ') AND (' . $statement . '))'
                : '(typeSort:' . $typeFilter . ' AND (' . $statement . '))';
        }

        return $statement;
    }

    /**
     * `is` behaves differently per field kind.
     */
    private static function equality(string $field, string $value): string
    {
        // Multi-valued fields store a count; "does it have any" is a range test.
        if (str_contains($field, '_txtmulti') || str_contains($field, '_strmulti')) {
            return ($value === 'false' ? '-' : '') . $field . ':[1 TO *]';
        }

        // Solr has no "false" term for a boolean; absence of true is the test.
        if (str_contains($field, '_bool')) {
            return $value === 'true' ? $field . ':true' : '*:* AND -' . $field . ':true';
        }

        // A value that already carries range syntax is passed through unquoted.
        return str_contains($value, ']') ? $field . ':' . $value : $field . ':"' . $value . '"';
    }

    private static function negation(string $field, string $value): string
    {
        $isMulti = str_contains($field, '_txtmulti') || str_contains($field, '_strmulti');

        return $isMulti
            ? '( *:* -' . $field . ':"' . $value . '")'
            : '( *:* -' . $field . ':*' . self::escapeSpaces($value) . '*)';
    }

    /**
     * `+` is a Solr operator and a stray `"` would close the term early.
     */
    private static function sanitize(string $value): string
    {
        return str_replace(['+', '"'], [' ', ''], $value);
    }

    /**
     * A wildcard term cannot be quoted — quoting turns it back into a phrase and
     * the wildcards stop meaning anything — so spaces are escaped instead.
     */
    private static function escapeSpaces(string $value): string
    {
        return str_replace(' ', '\\ ', $value);
    }

    /**
     * Range values are allowlisted rather than escaped.
     *
     * Legitimate inputs here are ISO dates, numbers and NOW-relative expressions
     * (`2026-05-21T15:30:00+01:00`, `1.5e10`, `NOW-7DAY`, `7DAYS`). Anything
     * outside that vocabulary is dropped, so no caller-supplied value can close
     * the `[X TO Y]` bracket and continue the query.
     */
    private static function escapeRange(string $value): string
    {
        return (string) preg_replace('/[^A-Za-z0-9._:+\-\/]/', '', $value);
    }
}
