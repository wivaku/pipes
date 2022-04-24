<?php

declare(strict_types=1);

namespace Jwhulette\Pipes\Tests\Unit\Extractors;

use Jwhulette\Pipes\Extractors\FixedWithExtractor;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use Tests\database\factories\DataFileFactory;
use Tests\TestCase;

class FixedWithExtractorTest extends TestCase
{
    protected string $extract;

    protected string $extractNoHeader;

    protected vfsStreamDirectory $vfs;

    public function setUp(): void
    {
        parent::setUp();

        $directory = [
            'csv_extractor.csv',
            'csv_no_header_extractor.csv',
        ];

        $this->vfs = vfsStream::setup(sys_get_temp_dir(), null, $directory);

        $header = [
            'FIRSTNAME',
            'LASTNAME',
            'DOB',
            'AMOUNT',
        ];

        $this->extract = $this->vfs->url().'/fixed_width_extractor.txt';

        $this->extractNoHeader = $this->vfs->url().'/fixed_width_no_header_extractor.txt';

        (new DataFileFactory($this->extract))
            ->asFixedWidth(10)
            ->setHeader($header)
            ->create();

        (new DataFileFactory($this->extractNoHeader))
            ->asFixedWidth(10)
            ->create();
    }

    /** @test */
    public function it_has_header()
    {
        $fixedWidth = new FixedWithExtractor($this->extract);

        $frameData = $fixedWidth->setAllColumns(10)->extract();

        $frame = $frameData->current();

        $expected = [
            'FIRSTNAME',
            'LASTNAME',
            'DOB',
            'AMOUNT',
        ];

        $this->assertEquals($expected, $frame->getData()->keys()->toArray());
    }

    /** @test */
    public function it_has_no_header_all_columns_same_width()
    {
        $fixedWidth = new FixedWithExtractor($this->extractNoHeader);

        $frameData = $fixedWidth->setNoHeader()->setAllColumns(10)->extract();

        $frame = $frameData->current();

        $expected = [
            'BOB',
            'SMITH',
            '02/11/1969',
            '$22.00',
        ];

        $this->assertEquals($expected, $frame->getData()->toArray());
    }

    /** @test */
    public function it_has_no_header_columns_have_different_widths()
    {
        $widths = [1 => 10, 2 => 10, 3 => 10, 4 => 10];

        $fixedWidth = new FixedWithExtractor($this->extractNoHeader);

        $frameData = $fixedWidth->setColumnsWidth($widths)->setNoHeader()->extract();

        $frame = $frameData->current();

        $expected = [
            'BOB',
            'SMITH',
            '02/11/1969',
            '$22.00',
        ];

        $this->assertEquals($expected, $frame->getData()->toArray());
    }

    /** @test */
    public function it_can_skip_lines()
    {
        $fixedWidth = new FixedWithExtractor($this->extract);

        $fixedWidth->setSkipLines(3);

        $frameData = $fixedWidth->setAllColumns(10)->extract();

        $frame = $frameData->current();

        $expected = [
            'FIRSTNAME' => 'LISA',
            'LASTNAME'  => 'SMITH',
            'DOB'       => '02/11/2001',
            'AMOUNT'    => '$50.00',
        ];

        $this->assertEquals($expected, $frame->getData()->toArray());
    }
}
