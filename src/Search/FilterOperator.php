<?php

namespace BlueBillywig\Search;

/**
 * The filter operators the OVP search understands.
 *
 * The values match the strings OVP6 puts in a filterset and formatengine's
 * `SearchRequestHelper::constructSolrSearchParameter()` switches on, so a
 * filterset built here is byte-compatible with one built in the OVP UI.
 */
enum FilterOperator: string
{
    case Is = 'is';
    case IsAnyOf = 'isAnyOf';
    case IsNot = 'isNot';
    case IsNotAnyOf = 'isNotAnyOf';
    case IsEmpty = 'isEmpty';
    case IsNotEmpty = 'isNotEmpty';
    case Contains = 'contains';
    case ContainsAnyOf = 'containsAnyOf';
    case ContainsAllOf = 'containsAllOf';
    case DoesNotContain = 'doesNotContain';
    case DoesNotContainAnyOf = 'doesNotContainAnyOf';
    case IsBefore = 'isBefore';
    case IsAfter = 'isAfter';
    case IsSmallerThan = 'isSmallerThan';
    case IsGreaterThan = 'isGreaterThan';
    case IsInTheLast = 'isInTheLast';
    case IsNotInTheLast = 'isNotInTheLast';

    /**
     * Operators that ignore any supplied value — they test presence only, so an
     * empty value must not cause the filter to be skipped.
     */
    public function ignoresValue(): bool
    {
        return $this === self::IsEmpty || $this === self::IsNotEmpty;
    }
}
