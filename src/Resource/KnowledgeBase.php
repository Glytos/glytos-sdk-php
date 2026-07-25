<?php

declare(strict_types=1);

namespace Glytos\Resource;

/**
 * Knowledge base: documents and hybrid retrieval your agents draw on.
 */
final class KnowledgeBase extends AbstractResource
{
    /**
     * List your knowledge-base documents.
     *
     * @return array<mixed>
     */
    public function listDocuments(): array
    {
        return (array) $this->client->request('GET', '/knowledge-base/documents');
    }

    /**
     * Create a document. The text is chunked and embedded for retrieval.
     *
     * @return array<mixed>
     */
    public function createDocument(
        string $name,
        string $content,
        ?int $chunkSize = null,
        ?int $chunkOverlap = null,
    ): array {
        $body = ['name' => $name, 'content' => $content];
        if ($chunkSize !== null) {
            $body['chunk_size'] = $chunkSize;
        }
        if ($chunkOverlap !== null) {
            $body['chunk_overlap'] = $chunkOverlap;
        }

        return (array) $this->client->request('POST', '/knowledge-base/documents', $body);
    }

    /**
     * Hybrid search (vector + full-text) over your documents.
     *
     * @param list<int>|null $documentIds Restrict the search to these document ids.
     *
     * @return array<mixed>
     */
    public function search(
        string $query,
        ?int $topK = null,
        ?array $documentIds = null,
        ?float $minScore = null,
    ): array {
        $body = ['query' => $query];
        if ($topK !== null) {
            $body['top_k'] = $topK;
        }
        if ($documentIds !== null) {
            $body['document_ids'] = $documentIds;
        }
        if ($minScore !== null) {
            $body['min_score'] = $minScore;
        }

        return (array) $this->client->request('POST', '/knowledge-base/search', $body);
    }
}
