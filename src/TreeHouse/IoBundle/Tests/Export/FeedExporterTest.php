<?php

namespace TreeHouse\IoBundle\Tests\Export;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use TreeHouse\IoBundle\Export\FeedExporter;
use TreeHouse\IoBundle\Export\FeedType\FeedTypeInterface;
use TreeHouse\IoBundle\Export\FeedWriter;

class FeedExporterTest extends \PHPUnit_Framework_TestCase
{
    protected $tmpDir;

    public function setUp()
    {
        $this->tmpDir = sys_get_temp_dir().'/exporter';

        $filesystem = new Filesystem();
        $filesystem->mkdir($this->tmpDir);
    }

    public function tearDown()
    {
        if (is_dir($this->tmpDir)) {
            $fs = new Filesystem();
            $fs->remove($this->tmpDir);
        }
    }

    public function testRegisterType()
    {
        $exporter = $this->getExporter();

        $type = $this->getMockBuilder(FeedTypeInterface::class)->getMockForAbstractClass();
        $type->expects($this->any())
            ->method('getName')
            ->willReturn('some_type');
        $type->expects($this->any())
            ->method('supports')
            ->willReturn(true);

        $exporter->registerType($type);

        $this->assertTrue($exporter->hasType('some_type'));
        $this->assertEquals([$type], $exporter->getTypes());
        $this->assertEquals($type, $exporter->getType('some_type'));
    }

    public function testCacheItemWritesAFileToDisk()
    {
        $writer = $this->getMockBuilder(FeedWriter::class)
            ->disableOriginalConstructor()->getMock();
        $writer->expects($this->any())
            ->method('renderEntity')
            ->willReturn('<someNode>some entity data</someNode>');

        $entity = $this->getMockBuilder('stdClass')->setMethods(['getId'])->getMock();
        $entity->expects($this->any())
            ->method('getId')
            ->willReturn(234);

        $exporter = $this->getExporter(null, null, $writer);

        $type = $this->getMockBuilder(FeedTypeInterface::class)->getMockForAbstractClass();
        $type->expects($this->any())
            ->method('getName')
            ->willReturn('some_type');
        $type->expects($this->any())
            ->method('supports')
            ->willReturn(true);

        $exporter->registerType($type);

        $finder = new Finder();
        $this->assertEquals(0, $finder->files()->in($this->tmpDir)->count());

        $exporter->cacheItem($entity);

        $finder = new Finder();
        $this->assertEquals(1, $finder->files()->in($this->tmpDir)->count());
    }

    /**
     * @param null|string     $cacheDir
     * @param null            $exportDir
     * @param null|FeedWriter $writer
     *
     * @return FeedExporter
     */
    protected function getExporter($cacheDir = null, $exportDir = null, $writer = null)
    {
        $writer = $writer ?:
            $this->getMockBuilder(FeedWriter::class)
                ->disableOriginalConstructor()->getMock();

        $exporter = new FeedExporter($cacheDir ?: $this->tmpDir, $exportDir ?: $this->tmpDir, $writer, new Filesystem());

        return $exporter;
    }
}
