<?php

/**
 * Unit test for the PicaNormReader class.
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

class PicaNormReaderTest extends PHPUnit_FrameWork_TestCase
{
    public function testReadStringData ()
    {
        $data   = "003@ \x1f0test\x1e002@ \x1f0Aau";
        $reader = new PicaNormReader();
        $reader->open($data);
        $record = $reader->read();
        $this->assertInstanceOf('HAB\Pica\Record\TitleRecord', $record);
        $reader->close();
    }
}
