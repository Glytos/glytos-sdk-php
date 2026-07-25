<?php

declare(strict_types=1);

namespace Glytos\Resource;

/**
 * Vector stores: named groupings of knowledge-base documents.
 */
final class VectorStores extends AbstractResource
{
    /**
     * List your vector stores.
     *
     * @return array<mixed>
     */
    public function list(): array
    {
        return (array) $this->client->request('GET', '/vector-stores');
    }

    /**
     * Create a vector store.
     *
     * @return array<mixed>
     */
    public function create(string $name): array
    {
        return (array) $this->client->request('POST', '/vector-stores', ['name' => $name]);
    }

    /**
     * Retrieve a vector store by uuid.
     *
     * @return array<mixed>
     */
    public function retrieve(string $vectorStoreUuid): array
    {
        return (array) $this->client->request(
            'GET',
            '/vector-stores/' . rawurlencode($vectorStoreUuid),
        );
    }

    /**
     * Delete a vector store.
     */
    public function delete(string $vectorStoreUuid): mixed
    {
        return $this->client->request('DELETE', '/vector-stores/' . rawurlencode($vectorStoreUuid));
    }
}
