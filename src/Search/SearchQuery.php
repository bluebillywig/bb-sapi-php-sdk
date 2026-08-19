<?php

namespace BlueBillywig\Search;

/**
 * Builds the query string for a filtered SAPI search.
 *
 * This exists because the encoding is not obvious and getting it wrong fails
 * SILENTLY. SAPI accepts filter queries as INDEXED parameters:
 *
 *     fq[0]=…&fq[1]=…      accepted
 *     fq[][0]=…            ignored — the shape `http_build_query(['fq[]' => [...]])`
 *                          produces, which is the natural-looking thing to write
 *     fq=…                 ignored
 *
 * An ignored filter is not an error: SAPI answers HTTP 200 with a body that has
 * no `numfound` and no `items`, which a caller cannot tell apart from a genuinely
 * empty result. So a filtered search returns "nothing found" and nothing
 * anywhere reports a problem.
 *
 * Passing `'fq' => [...]` to http_build_query() produces `fq[0]=`, which is why
 * that is done here once rather than at every call site.
 */
final class SearchQuery
{
    public function __construct(
        private readonly string $entityType = 'mediaclip',
        private readonly ?FilterSet $filterSet = null,
        private readonly int $limit = 15,
        private readonly int $offset = 0,
        private readonly ?string $sort = null,
        private readonly string $query = '*',
    ) {
    }

    /**
     * The full path + query string, ready for a Request.
     */
    public function toPath(): string
    {
        $params = [
            'q' => $this->query,
            'limit' => $this->limit,
            'offset' => $this->offset,
        ];

        if ($this->sort !== null && $this->sort !== '') {
            $params['sort'] = $this->sort;
        }

        $solr = $this->filterSet?->toSolrQuery() ?? '';
        if ($solr !== '') {
            // 'fq', never 'fq[]' — see the class docblock.
            $params['fq'] = [$solr];
        }

        return '/sapi/' . $this->entityType . '?' . http_build_query($params);
    }

    public function __toString(): string
    {
        return $this->toPath();
    }
}
