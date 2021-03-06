<?php

namespace TreeHouse\IoBundle\Scrape\Parser;

use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use TreeHouse\Feeder\Modifier\Data\Transformer\TransformerInterface;
use TreeHouse\Feeder\Modifier\Item\Filter\FilterInterface;
use TreeHouse\Feeder\Modifier\Item\ModifierInterface;
use TreeHouse\Feeder\Modifier\Item\Transformer\DataTransformer;
use TreeHouse\Feeder\Modifier\Item\Validator\ValidatorInterface;
use TreeHouse\IoBundle\Scrape\Parser\Type\ParserTypeInterface;

class ParserBuilder implements ParserBuilderInterface
{
    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @var ModifierInterface[]
     */
    protected $modifiers = [];

    /**
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(EventDispatcherInterface $eventDispatcher = null)
    {
        $this->eventDispatcher = $eventDispatcher ?: new EventDispatcher();
    }

    /**
     * @inheritdoc
     */
    public function getEventDispatcher()
    {
        return $this->eventDispatcher;
    }

    /**
     * @return ModifierInterface[]
     */
    public function getModifiers()
    {
        return $this->modifiers;
    }

    /**
     * @param ModifierInterface $modifier
     * @param int               $position Defaults to the next highest position
     * @param bool              $continue Will be determined based on modifier type
     *
     * @throws \InvalidArgumentException If there already is a modifier at the given position
     */
    public function addModifier(ModifierInterface $modifier, $position = null, $continue = null)
    {
        if (null === $position) {
            $position = sizeof($this->modifiers) ? (max(array_keys($this->modifiers)) + 1) : 0;
        }

        if (null === $continue) {
            $continue = (!$modifier instanceof FilterInterface) && (!$modifier instanceof ValidatorInterface);
        }

        if (array_key_exists($position, $this->modifiers)) {
            throw new \InvalidArgumentException(sprintf('There already is a modifier at position %d', $position));
        }

        $this->modifiers[$position] = [$modifier, $continue];
    }

    /**
     * Adds the given modifier between the start and end index, if there is a vacant position.
     *
     * @param ModifierInterface $modifier
     * @param int               $startIndex
     * @param int               $endIndex
     * @param bool              $continue
     *
     * @throws \OutOfBoundsException
     */
    public function addModifierBetween(ModifierInterface $modifier, $startIndex, $endIndex, $continue = null)
    {
        for ($position = $startIndex; $position <= $endIndex; ++$position) {
            if (!$this->hasModifierAt($position)) {
                $this->addModifier($modifier, $position, $continue);

                return;
            }
        }

        throw new \OutOfBoundsException(sprintf('No position left between %d and %d', $startIndex, $endIndex));
    }

    /**
     * Shortcut for adding a field-value transformer.
     *
     * @param TransformerInterface $transformer
     * @param string               $field
     * @param int                  $position
     * @param bool                 $continue
     */
    public function addTransformer(TransformerInterface $transformer, $field, $position = null, $continue = true)
    {
        $this->addModifier(new DataTransformer($transformer, $field), $position, $continue);
    }

    /**
     * Adds the given transformer between the start and end index, if there is a vacant position.
     *
     * @param TransformerInterface $transformer
     * @param string               $field
     * @param int                  $startIndex
     * @param int                  $endIndex
     * @param bool                 $continue
     *
     * @throws \OutOfBoundsException
     */
    public function addTransformerBetween(TransformerInterface $transformer, $field, $startIndex, $endIndex, $continue = null)
    {
        $this->addModifierBetween(new DataTransformer($transformer, $field), $startIndex, $endIndex, $continue);
    }

    /**
     * @param int $position
     *
     * @return bool
     */
    public function hasModifierAt($position)
    {
        return array_key_exists($position, $this->modifiers);
    }

    /**
     * Removes existing modifier.
     *
     * @param ModifierInterface $modifier
     */
    public function removeModifier(ModifierInterface $modifier)
    {
        foreach ($this->modifiers as $position => list($mod)) {
            if ($mod === $modifier) {
                unset($this->modifiers[$position]);
            }
        }
    }

    /**
     * Removes modifier at an existing position.
     *
     * @param int $position
     *
     * @throws \OutOfBoundsException If modifier does not exist
     */
    public function removeModifierAt($position)
    {
        if (!array_key_exists($position, $this->modifiers)) {
            throw new \OutOfBoundsException(sprintf('There is no modifier at position %d', $position));
        }

        unset($this->modifiers[$position]);
    }

    /**
     * @inheritdoc
     */
    public function build(ParserTypeInterface $type, array $options)
    {
        $resolver = $this->getOptionsResolver($type);

        $type->build($this, $resolver->resolve($options));

        $parser = new DefaultParser($this->eventDispatcher);
        foreach ($this->modifiers as $position => list($modifier, $continue)) {
            $parser->addModifier($modifier, $position, $continue);
        }

        return $parser;
    }

    /**
     * @param ParserTypeInterface $type
     *
     * @return OptionsResolver
     */
    protected function getOptionsResolver(ParserTypeInterface $type)
    {
        $resolver = new OptionsResolver();
        $type->setOptions($resolver);

        return $resolver;
    }
}
