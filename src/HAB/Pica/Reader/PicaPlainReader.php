<?php

/**
 * The PicaPlainReader class file.
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
 * @copyright Copyright (c) 2012 - 2017 by Herzog August Bibliothek Wolfenbüttel
 * @license   http://www.gnu.org/licenses/gpl.html GNU General Public License v3
 */

namespace HAB\Pica\Reader;

use HAB\Pica\Parser\PicaPlainParserInterface;
use HAB\Pica\Parser\PicaPlainParser;

class PicaPlainReader extends Reader
{

    /**
     * Regular expression matching lines that should be ignore.
     *
     * @var string
     */
    public $ignoreLineRegexp = '/^$/';

    /**
     * Current input data.
     *
     * @var string
     */
    protected $_data;

    /**
     * Parser instance.
     *
     * @var PicaPlainParser
     */
    private $_parser;

    /**
     * Constructor.
     *
     * @param  PicaPlainParserInterface $parser Optional parser instance
     * @return void
     */
    public function __construct (PicaPlainParserInterface $parser = null)
    {
        $this->_parser = $parser ?: new PicaPlainParser();
    }

    /**
     * Open the reader with input stream.
     *
     * @param  resource|string $stream
     * @return void
     */
    public function open ($data)
    {
        $this->_data = preg_split("/(?:\n\r|[\n\r])/", $data);
    }

    /**
     * {@inheritDoc}
     */
    protected function next ()
    {
        $record = false;
        if (current($this->_data) !== false) {
            $record = array('fields' => array());
            do {
                $line = current($this->_data);
                if (!preg_match($this->ignoreLineRegexp, $line)) {
                    $record['fields'] []= $this->_parser->parseField($line);
                }
            } while (next($this->_data));
            next($this->_data);
        }
        return $record;
    }

    /**
     * Close reader.
     *
     * @return void
     */
    public function close ()
    {
        $this->_data = null;
    }
}
