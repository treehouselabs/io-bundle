<?php

namespace TreeHouse\IoBundle\Scrape;

use TreeHouse\IoBundle\Entity\Scraper as ScraperEntity;
use TreeHouse\IoBundle\Event\SourceEvent;
use TreeHouse\IoBundle\Model\SourceInterface;
use TreeHouse\IoBundle\Scrape\Crawler\RateLimit\EnablingRateLimitInterface;
use TreeHouse\IoBundle\Scrape\Exception\NotFoundException;
use TreeHouse\IoBundle\Source\SourceManagerInterface;

class SourceRevisitor
{
    /**
     * @var SourceManagerInterface
     */
    protected $sourceManager;

    /**
     * @var ScraperFactory
     */
    protected $factory;

    /**
     * Array of cached scrapers.
     *
     * @var Scraper[]
     */
    protected $scrapers = [];

    /**
     * @param SourceManagerInterface $sourceManager
     * @param ScraperFactory         $factory
     */
    public function __construct(SourceManagerInterface $sourceManager, ScraperFactory $factory)
    {
        $this->sourceManager = $sourceManager;
        $this->factory = $factory;
    }

    /**
     * Revisits a source. This basically means doing a scrape operation on the
     * source origin, only this time the source will be removed if the original
     * url was not found.
     *
     * @param SourceInterface $source       The source to revisit.
     * @param bool            $async        If true, makes the scrape action asynchronous.
     *                                      The revisit action will happen right away, but any
     *                                      consecutive scrape actions will be queued. Use this
     *                                      when calling the revisit action from an asynchronous
     *                                      context.
     * @param bool            $disableLimit Whether to disable the rate limit when revisiting.
     */
    public function revisit(SourceInterface $source, $async = false, $disableLimit = false)
    {
        if (!$source->getOriginalUrl()) {
            throw new \InvalidArgumentException('Source does not contain an original url');
        }

        // check if source is still fresh
        if ($this->isFresh($source)) {
            return;
        }

        $scraper = $this->createScraper($source->getScraper(), $disableLimit);
        $scraper->setAsync($async);

        try {
            $scraper->scrape($source->getScraper(), $source->getOriginalUrl(), false);
        } catch (NotFoundException $e) {
            $this->removeSource($source);
        }
    }

    /**
     * Does a non-blocking revisit operation. Depending on the implementation,
     * this will mean adding a revisit job to some sort of queueing system.
     *
     * @param SourceInterface $source
     * @param \DateTime       $date
     */
    public function revisitAfter(SourceInterface $source, \DateTime $date)
    {
        $scraper = $this->createScraper($source->getScraper());
        $scraper->getEventDispatcher()->dispatch(
            ScraperEvents::SCRAPE_REVISIT_SOURCE,
            new SourceEvent($source),
            $date
        );
    }

    /**
     * @param SourceInterface $source
     */
    protected function removeSource(SourceInterface $source)
    {
        $this->sourceManager->remove($source);
        $this->sourceManager->flush($source);
    }

    /**
     * @param ScraperEntity $scraperEntity
     * @param bool          $disableLimit
     *
     * @return ScraperInterface
     */
    protected function createScraper(ScraperEntity $scraperEntity, $disableLimit = false)
    {
        if (!array_key_exists($scraperEntity->getId(), $this->scrapers)) {
            $scraper = $this->factory->createScraper($scraperEntity);

            if ($disableLimit) {
                $limit = $scraper->getCrawler()->getRateLimit();
                if ($limit instanceof EnablingRateLimitInterface) {
                    $limit->disable();
                }
            }

            $this->scrapers[$scraperEntity->getId()] = $scraper;
        }

        return $this->scrapers[$scraperEntity->getId()];
    }

    /**
     * Checks whether the given source is fresh, meaning it doesn't need revisiting right now.
     *
     * @param SourceInterface $source
     *
     * @return bool
     */
    protected function isFresh(SourceInterface $source)
    {
        $lastVisitDate = $source->getDatetimeLastVisited();

        // no previous visit date, consider it stale
        if (null === $lastVisitDate) {
            return false;
        }

        $revisitDate = clone $lastVisitDate;
        $revisitDate->modify(sprintf('+%d hours', $source->getScraper()->getRevisitFrequency()));

        return $revisitDate > new \DateTime();
    }
}
