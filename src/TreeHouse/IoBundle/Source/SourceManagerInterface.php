<?php

namespace TreeHouse\IoBundle\Source;

use TreeHouse\IoBundle\Entity\Feed;
use TreeHouse\IoBundle\Entity\Scraper;
use TreeHouse\IoBundle\Entity\SourceRepository;
use TreeHouse\IoBundle\Model\SourceInterface;

interface SourceManagerInterface
{
    /**
     * @return SourceRepository
     */
    public function getRepository();

    /**
     * Finds an existing source by id.
     *
     * @param int $sourceId
     *
     * @return SourceInterface
     */
    public function findById($sourceId);

    /**
     * Finds source object for a given feed and original id.
     *
     * @param Feed $feed
     * @param int  $originalId
     *
     * @return SourceInterface
     */
    public function findSourceByFeed(Feed $feed, $originalId);

    /**
     * Finds source object for a given scraper and original url.
     *
     * @param Scraper $scraper
     * @param string  $originalId
     *
     * @return SourceInterface
     */
    public function findSourceByScraper(Scraper $scraper, $originalId);

    /**
     * Finds source object for a given original id, optionally creates a new one if a source cannot be found.
     *
     * @param Feed   $feed
     * @param int    $originalId
     * @param string $originalUrl
     *
     * @return SourceInterface
     */
    public function findSourceByFeedOrCreate(Feed $feed, $originalId, $originalUrl = null);

    /**
     * Finds source object for a given original id, optionally creates a new one if a source cannot be found.
     *
     * @param Scraper $scraper
     * @param string  $originalId
     * @param string  $originalUrl
     *
     * @return SourceInterface
     */
    public function findSourceByScraperOrCreate(Scraper $scraper, $originalId, $originalUrl);

    /**
     * Persists a (new) source.
     *
     * @param SourceInterface $source
     */
    public function persist(SourceInterface $source);

    /**
     * Persists an existing source.
     *
     * @param SourceInterface $source
     */
    public function remove(SourceInterface $source);

    /**
     * Detaches a source, making all changes irrelevant.
     *
     * @param SourceInterface $source
     */
    public function detach(SourceInterface $source);

    /**
     * Flushes all outstanding changes in sources.
     *
     * @param SourceInterface $source
     */
    public function flush(SourceInterface $source = null);

    /**
     * Clears caches.
     */
    public function clear();
}
