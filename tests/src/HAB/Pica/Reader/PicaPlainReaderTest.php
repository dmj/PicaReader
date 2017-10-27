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

use PHPUnit_FrameWork_TestCase ;

class PicaPlainReaderTest extends PHPUnit_FrameWork_TestCase
{

    protected $_reader;

    public function setup ()
    {
        $this->_reader = new PicaPlainReader();
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

    public function testReadDoubleEncodedDollarSign ()
    {
        $this->_reader->open('002@/00 $0T$adouble$$dollar');
        $record = $this->_reader->read();
        $field = $record->getFirstMatchingField('002@/00');
        $subfield = $field->getNthSubfield('a', 0);
        $this->assertEquals('double$dollar', $subfield->getValue());
    }

    public function testReadDoubleEncodedDoubleDollarSign2x ()
    {
        $this->_reader->open('002@/00 $0T$adouble$$$$dollar');
        $record = $this->_reader->read();
        $field = $record->getFirstMatchingField('002@/00');
        $subfield = $field->getNthSubfield('a', 0);
        $this->assertEquals('double$$dollar', $subfield->getValue());
    }

    public function testReadDoubleEncodedDoubleDollarSignAtEnd ()
    {
        $this->_reader->open('002@/00 $0T$adoubledollar$$');
        $record = $this->_reader->read();
        $field = $record->getFirstMatchingField('002@/00');
        $subfield = $field->getNthSubfield('a', 0);
        $this->assertEquals('doubledollar$', $subfield->getValue());
    }

    public function testReadDoubleEncodedDoubleDollarSignOnly ()
    {
        $this->_reader->open('002@/00 $0T$a$$');
        $record = $this->_reader->read();
        $field = $record->getFirstMatchingField('002@/00');
        $subfield = $field->getNthSubfield('a', 0);
        $this->assertEquals('$', $subfield->getValue());
    }

    public function testReadFilterInvalidSubfieldCode ()
    {
        $filter = function (array $record) {
            return array('fields' => array_map(function (array $field) {
                return array('tag' => $field['tag'],
                             'occurrence' => $field['occurrence'],
                             'subfields' => array_filter($field['subfields'],
                                                         function (array $subfield) {
                                                         return \HAB\Pica\Record\Subfield::isValidSubfieldCode($subfield['code']);
                                                     }));
            }, $record['fields']));
        };
        $this->_reader->open("002@/00 \$0T\n000A/00 \$@FOOBAR");
        $this->_reader->setFilter($filter);
        $this->assertSame($filter, $this->_reader->getFilter());
        $this->_reader->read();
        $this->_reader->unsetFilter();
    }

    public function testReadIgnoreLines ()
    {
        $this->_reader->open("002@/00 \$0T\nfoo: foobar");
        $this->_reader->ignoreLineRegexp = '/^[a-z]+:/';
        $this->_reader->read();
    }

    ///

    /**
     * @expectedException RuntimeException
     */
    public function testReadMalformedSingleDollarAtEnd ()
    {
        $this->_reader->open('002@/00 $0T$aFOOBAR$');
        $record = $this->_reader->read();
    }
    /**
     * @expectedException RuntimeException
     */
    public function testReadMalformedLine ()
    {
        $this->_reader->open('');
        $this->_reader->read();
    }

    ///

    protected function getFixture ($fixture)
    {
        return file_get_contents(\PHPUNIT_FIXTURES . DIRECTORY_SEPARATOR . "{$fixture}.pp");
    }
}
