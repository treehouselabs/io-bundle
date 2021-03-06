<?php

namespace TreeHouse\IoBundle\Tests\Bridge\WorkerBundle\Executor;

use Psr\Log\LogLevel;
use Psr\Log\NullLogger;
use Symfony\Component\OptionsResolver\OptionsResolver;
use TreeHouse\IoBundle\Bridge\WorkerBundle\Executor\SourceProcessExecutor;
use TreeHouse\IoBundle\Exception\SourceLinkException;
use TreeHouse\IoBundle\Exception\SourceProcessException;
use TreeHouse\IoBundle\Model\SourceInterface;
use TreeHouse\IoBundle\Source\Processor\DelegatingSourceProcessor;
use TreeHouse\IoBundle\Source\SourceManagerInterface;
use TreeHouse\IoBundle\Test\Mock\SourceMock;

class SourceProcessExecutorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var SourceManagerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $manager;

    /**
     * @var DelegatingSourceProcessor|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $processor;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->manager = $this
            ->getMockBuilder(SourceManagerInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['findById'])
            ->getMockForAbstractClass()
        ;

        $this->processor = $this
            ->getMockBuilder(DelegatingSourceProcessor::class)
            ->disableOriginalConstructor()
            ->setMethods(['link', 'unlink', 'isLinked', 'process'])
            ->getMock()
        ;
    }

    public function testLinkFirst()
    {
        $executor = new SourceProcessExecutor($this->manager, $this->processor, new NullLogger());

        $source = new SourceMock(12345);

        $this->manager->expects($this->once())->method('findById')->will($this->returnValue($source));
        $this->processor->expects($this->once())->method('isLinked')->will($this->returnValue(false));
        $this->processor->expects($this->once())->method('link')->will($this->returnValue(true));
        $this->manager->expects($this->once())->method('flush')->with($source);

        $executor->execute($this->getPayload($executor, $source));
    }

    public function testLinkException()
    {
        $executor = new SourceProcessExecutor($this->manager, $this->processor, new NullLogger());

        $source = new SourceMock(12345);

        $this->manager->expects($this->once())->method('findById')->will($this->returnValue($source));
        $this->processor->expects($this->once())->method('isLinked')->will($this->returnValue(false));

        $this->processor
            ->expects($this->once())
            ->method('link')
            ->will($this->throwException(new SourceLinkException('Foobar')))
        ;

        $this->assertFalse($executor->execute($this->getPayload($executor, $source)));

        $messages = $source->getMessages();
        $this->assertInternalType('array', $messages);
        $this->assertArrayHasKey('link', $messages);
        $this->assertArrayHasKey(LogLevel::ERROR, $messages['link']);
        $this->assertContains('Foobar', $messages['link'][LogLevel::ERROR]);
    }

    public function testExecute()
    {
        $executor = new SourceProcessExecutor($this->manager, $this->processor, new NullLogger());

        $source = new SourceMock(12345);

        $this->manager->expects($this->once())->method('findById')->will($this->returnValue($source));
        $this->processor->expects($this->once())->method('isLinked')->will($this->returnValue(true));
        $this->processor->expects($this->once())->method('process')->will($this->returnValue(true));

        $this->assertTrue($executor->execute($this->getPayload($executor, $source)));
    }

    /**
     * @expectedException \Symfony\Component\OptionsResolver\Exception\InvalidArgumentException
     */
    public function testCannotFindSource()
    {
        $executor = new SourceProcessExecutor($this->manager, $this->processor, new NullLogger());

        $source = new SourceMock(12345);

        $this->manager->expects($this->once())->method('findById')->will($this->returnValue(null));
        $this->processor->expects($this->never())->method('link')->will($this->returnValue(true));

        $this->assertFalse($executor->execute($this->getPayload($executor, $source)));
    }

    public function testBlockedSource()
    {
        $executor = new SourceProcessExecutor($this->manager, $this->processor, new NullLogger());

        $source = new SourceMock(12345);
        $source->setBlocked(true);

        $this->manager->expects($this->once())->method('findById')->will($this->returnValue($source));
        $this->processor->expects($this->never())->method('link')->will($this->returnValue(true));
        $this->processor->expects($this->once())->method('unlink')->will($this->returnValue(true));

        $this->assertFalse($executor->execute($this->getPayload($executor, $source)));
    }

    public function testProcessException()
    {
        $executor = new SourceProcessExecutor($this->manager, $this->processor, new NullLogger());

        $source = new SourceMock(12345);

        $this->manager->expects($this->once())->method('findById')->will($this->returnValue($source));
        $this->processor->expects($this->once())->method('isLinked')->will($this->returnValue(false));

        $this->processor
            ->expects($this->once())
            ->method('process')
            ->will($this->throwException(new SourceProcessException('Foobar')))
        ;

        $this->assertFalse($executor->execute($this->getPayload($executor, $source)));

        $messages = $source->getMessages();
        $this->assertInternalType('array', $messages);
        $this->assertArrayHasKey('process', $messages);
        $this->assertArrayHasKey(LogLevel::ERROR, $messages['process']);
        $this->assertContains('Foobar', $messages['process'][LogLevel::ERROR]);
    }

    /**
     * @param SourceProcessExecutor $executor
     * @param SourceInterface       $source
     *
     * @return array
     */
    private function getPayload(SourceProcessExecutor $executor, SourceInterface $source)
    {
        $payload = $executor->getObjectPayload($source);

        $resolver = new OptionsResolver();
        $executor->configurePayload($resolver);

        return $resolver->resolve($payload);
    }
}
