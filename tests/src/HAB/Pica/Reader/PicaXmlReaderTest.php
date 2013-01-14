<?php

/**
 * Unit test for the PicaPlainReader class.
 *
 * This file is part of PicaReader.
 *
 * PicaReader is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * PicaReader is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with PicaReader.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @package   PicaReader
 * @author    David Maus <maus@hab.de>
 * @copyright Copyright (c) 2012, 2013 by Herzog August Bibliothek Wolfenbüttel
 * @license   http://www.gnu.org/licenses/gpl.html GNU General Public License v3
 */

namespace HAB\Pica\Reader;

use PHPUnit_FrameWork_TestCase;

class PicaXmlReaderTest extends PHPUnit_FrameWork_TestCase
{

    protected $_reader;

    public function setup ()
    {
        $this->_reader = new PicaXmlReader();
    }

    public function testRead ()
    {
        $this->_reader->open($this->getFixture('single_record'));
        $record = $this->_reader->read();
        $this->assertInstanceOf('\HAB\Pica\Record\TitleRecord', $record);
        $this->assertFalse($this->_reader->read());
        $this->assertEquals(377, count($record->getFields()));
        $this->assertEquals(21, count($record->getLocalRecords()));
        $this->assertEquals('024836885', $record->getPPN());
        $this->assertEquals(3, count($record->getLocalRecordByILN(31)->getCopyRecords()));
    }

    protected function getFixture ($fixture)
    {
        return file_get_contents(\PHPUNIT_FIXTURES . DIRECTORY_SEPARATOR . "{$fixture}.xml");
    }
}