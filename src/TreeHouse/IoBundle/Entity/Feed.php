<?php

namespace TreeHouse\IoBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use TreeHouse\IoBundle\Model\OriginInterface;
use TreeHouse\IoBundle\Model\SourceInterface;

/**
 * @ORM\Entity(repositoryClass="FeedRepository")
 * @ORM\Table
 */
class Feed
{
    /**
     * Items from this feed come directly from the source.
     */
    const SYNDICATION_DIRECT = 1;

    /**
     * Feed contains items from multiple sources, in an aggregated way.
     */
    const SYNDICATION_AGGREGATE = 2;

    /**
     * @var int
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * The frequency to import the feed with, in hours.
     *
     * @var int
     *
     * @ORM\Column(type="integer")
     */
    protected $frequency;

    /**
     * Whether the feed contains all items or only updates.
     *
     * @var bool
     *
     * @ORM\Column(type="boolean")
     */
    protected $partial;

    /**
     * Specifies where the feed's items come from.
     * Default options are 'direct' and 'aggregate', but these can be extended.
     *
     * @var int
     *
     * @ORM\Column(type="smallint")
     */
    protected $syndication = self::SYNDICATION_DIRECT;

    /**
     * One of the configured feed types.
     *
     * @var string
     *
     * @ORM\Column(type="string")
     */
    protected $type;

    /**
     * One of the configured reader types.
     *
     * @var string
     *
     * @ORM\Column(type="string")
     */
    protected $readerType;

    /**
     * One of the configured importer types.
     *
     * @var string
     *
     * @ORM\Column(type="string")
     */
    protected $importerType;

    /**
     * Options to be passed to the feed type.
     *
     * @var array
     *
     * @ORM\Column(type="json_array", nullable=true)
     */
    protected $options;

    /**
     * Options to be passed to the feed reader.
     *
     * @var array
     *
     * @ORM\Column(type="json_array", nullable=true)
     */
    protected $readerOptions;

    /**
     * Options to be passed to the feed importer.
     *
     * @var array
     *
     * @ORM\Column(type="json_array", nullable=true)
     */
    protected $importerOptions;

    /**
     * Configuration to use for downloading the feed.
     *
     * @var array
     *
     * @ORM\Column(type="json_array")
     */
    protected $transportConfig;

    /**
     * Can contain key/value pairs to be used as defaults if the feed doesn't supply them.
     *
     * @var array
     *
     * @ORM\Column(type="json_array", nullable=true)
     */
    protected $defaultValues;

    /**
     * The feed's origin.
     *
     * @var OriginInterface
     *
     * @ORM\ManyToOne(targetEntity="TreeHouse\IoBundle\Model\OriginInterface", inversedBy="feeds", cascade={"persist"})
     */
    protected $origin;

    /**
     * Sources that were imported using this feed.
     *
     * @var SourceInterface
     *
     * @ORM\OneToMany(targetEntity="TreeHouse\IoBundle\Model\SourceInterface", mappedBy="feed", cascade={"persist", "remove"})
     */
    protected $sources;

    /**
     * A specific supplier (a company, software package, etc.) that's providing this feed.
     *
     * @var FeedSupplier
     *
     * @ORM\ManyToOne(targetEntity="FeedSupplier", inversedBy="feeds", cascade={"persist"})
     */
    protected $supplier;

    /**
     * Imports for this feed.
     *
     * @var ArrayCollection|Import[]
     *
     * @ORM\OneToMany(targetEntity="Import", mappedBy="feed", cascade={"persist","remove"})
     */
    protected $imports;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->imports = new ArrayCollection();
        $this->sources = new ArrayCollection();
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return sprintf('%s feed %d', $this->getOrigin()->getName(), $this->getId());
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $frequency
     *
     * @return $this
     */
    public function setFrequency($frequency)
    {
        $this->frequency = $frequency;

        return $this;
    }

    /**
     * @return int
     */
    public function getFrequency()
    {
        return $this->frequency;
    }

    /**
     * @param bool $partial
     *
     * @return $this
     */
    public function setPartial($partial)
    {
        $this->partial = $partial;

        return $this;
    }

    /**
     * @return bool
     */
    public function isPartial()
    {
        return $this->partial;
    }

    /**
     * @param int $syndication
     *
     * @return $this
     */
    public function setSyndication($syndication)
    {
        $this->syndication = $syndication;

        return $this;
    }

    /**
     * @return int
     */
    public function getSyndication()
    {
        return $this->syndication;
    }

    /**
     * @param string $type
     *
     * @return $this
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $readerType
     *
     * @return $this
     */
    public function setReaderType($readerType)
    {
        $this->readerType = $readerType;

        return $this;
    }

    /**
     * @return string
     */
    public function getReaderType()
    {
        return $this->readerType;
    }

    /**
     * @param string $importerType
     *
     * @return $this
     */
    public function setImporterType($importerType)
    {
        $this->importerType = $importerType;

        return $this;
    }

    /**
     * @return string
     */
    public function getImporterType()
    {
        return $this->importerType;
    }

    /**
     * @param array $options
     *
     * @return $this
     */
    public function setOptions(array $options = null)
    {
        $this->options = $options;

        return $this;
    }

    /**
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @param array $readerOptions
     *
     * @return $this
     */
    public function setReaderOptions(array $readerOptions = null)
    {
        $this->readerOptions = $readerOptions;

        return $this;
    }

    /**
     * @return array
     */
    public function getReaderOptions()
    {
        return $this->readerOptions;
    }

    /**
     * @param array $importerOptions
     *
     * @return $this
     */
    public function setImporterOptions(array $importerOptions = null)
    {
        $this->importerOptions = $importerOptions;

        return $this;
    }

    /**
     * @return array
     */
    public function getImporterOptions()
    {
        return $this->importerOptions;
    }

    /**
     * @param array $transportConfig
     *
     * @return $this
     */
    public function setTransportConfig(array $transportConfig)
    {
        $this->transportConfig = $transportConfig;

        return $this;
    }

    /**
     * @return array
     */
    public function getTransportConfig()
    {
        return $this->transportConfig;
    }

    /**
     * @param array $defaultValues
     *
     * @return $this
     */
    public function setDefaultValues(array $defaultValues = null)
    {
        $this->defaultValues = $defaultValues;

        return $this;
    }

    /**
     * @return array
     */
    public function getDefaultValues()
    {
        return $this->defaultValues;
    }

    /**
     * @param OriginInterface $origin
     *
     * @return $this
     */
    public function setOrigin(OriginInterface $origin = null)
    {
        $this->origin = $origin;

        return $this;
    }

    /**
     * @return OriginInterface
     */
    public function getOrigin()
    {
        return $this->origin;
    }

    /**
     * @param FeedSupplier $supplier
     *
     * @return $this
     */
    public function setSupplier(FeedSupplier $supplier = null)
    {
        $this->supplier = $supplier;

        return $this;
    }

    /**
     * @return FeedSupplier
     */
    public function getSupplier()
    {
        return $this->supplier;
    }

    /**
     * @param Import $imports
     *
     * @return $this
     */
    public function addImport(Import $imports)
    {
        $this->imports[] = $imports;

        return $this;
    }

    /**
     * @param Import $import
     */
    public function removeImport(Import $import)
    {
        $this->imports->removeElement($import);
    }

    /**
     * @return ArrayCollection|Import[]
     */
    public function getImports()
    {
        return $this->imports;
    }

    /**
     * @param SourceInterface $source
     *
     * @return $this
     */
    public function addSource(SourceInterface $source)
    {
        $this->sources[] = $source;

        return $this;
    }

    /**
     * @param SourceInterface $source
     */
    public function removeSource(SourceInterface $source)
    {
        $this->sources->removeElement($source);
    }

    /**
     * @return SourceInterface[]|ArrayCollection
     */
    public function getSources()
    {
        return $this->sources;
    }
}
