<?php

/**
 * Reader for normalized Pica+ records.
 *
 * @see http://www.gbv.de/wikis/cls/PICA%2B#Normalisiertes_PICA.2B
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
 * @author    David Maus <maus@hab.de>
 * @copyright Copyright (c) 2013 by Herzog August Bibliothek Wolfenbüttel
 * @license   http://www.gnu.org/licenses/gpl.txt GNU General Public License v3
 */

namespace HAB\Pica\Reader;

use RuntimeException;
use InvalidArgumentException;

class PicaNormReader extends Reader
{
    /**
     * Separators.
     *
     * @var string
     */
    const RECORD_SEPARATOR = "\x1d";
    const FIELD_SEPARATOR  = "\x1e";
    const SUBFIELD_SEPARATOR = "\x1f";

    /**
     * Input stream.
     *
     * @var resource
     */
    private $stream;

    /**
     * Read-buffer.
     *
     * @var string
     */
    private $buffer;

    /**
     * Read-buffer size.
     *
     * @var integer
     */
    private $bufferSize;

    /**
     * Position in read-buffer.
     *
     * @var integer
     */
    private $bufferPosition;

    /**
     * Regular expression to split a field.
     *
     * @var string
     */
    private $fieldRegexp = "|^([012][0-9]{2}[A-Z@])(/([0-9]{2}))? \x1f(.+)$|uD";

    /**
     * Constructor.
     *
     * @return void
     */
    public function __construct ()
    {}

    /**
     * Open the reader with input stream.
     *
     * @throws InvalidArgumentException Invalid stream type
     * @throws InvalidArgumentException Argument neither string nor stream
     *
     * @param  resource|string $stream
     * @return void
     */
    public function open ($stream)
    {
        if (is_string($stream)) {
            $stream = fopen('data://text/plain;base64,' . base64_encode($stream), 'rb');
        }
        if (!is_resource($stream)) {
            throw new InvalidArgumentException(sprintf('Invalid type of argument: resource|string, %s', gettype($stream)));
        }
        $meta = stream_get_meta_data($stream);
        if ($meta['stream_type'] !== 'STDIO' && $meta['stream_type'] !== 'RFC2397') {
            throw new InvalidArgumentException(sprintf('Invalid stream type: STDIO|RFC297, %s', $meta['stream_type']));
        }
        $this->buffer         = null;
        $this->stream         = $stream;
        $this->bufferSize     = 0;
        $this->bufferPosition = 0;
        // Skip over preceeding whitespace
        while (!ctype_alnum($this->getc(true))) {
            $this->getc();
        }
    }

    /**
     * Close reader.
     *
     * @return void
     */
    public function close ()
    {
        if ($this->stream) {
            fclose($this->stream);
        }
    }

    /**
     * Return next record from input stream.
     *
     * @return array
     */
    protected function next ()
    {
        if ($this->feof()) {
            return false;
        }

        $record = array();
        while (!$this->feof() && $this->peek() !== self::RECORD_SEPARATOR) {
            $field = $this->field();
            if ($field) {
                $record['fields'] []= $field;
            }
        }
        if (!$this->feof()) {
            // Swallow record separator
            $this->getc();
        }
        return empty($record) ? false : $record;
    }

    ///

    /**
     * Return Pica+ field.
     *
     * @return array|null
     */
    private function field ()
    {
        if ($this->feof()) {
            return false;
        }

        $line  = '';
        while (!$this->feof() && $this->peek() !== self::FIELD_SEPARATOR) {
            $octet = $this->getc();
            if ($octet !== null) {
                $line .= $octet;
            }
        }
        if (!$this->feof()) {
            // Swallow field separator
            $this->getc();
        }

        $matches = array();
        if (!preg_match($this->fieldRegexp, $line, $matches)) {
            throw new RuntimeException(sprintf('Unexpected data in input stream: %s', $line));
        }
        $subfields = array_map(array($this, 'splitSubfield'), explode(self::SUBFIELD_SEPARATOR, $matches[4]));
        $field = array(
            'tag' => $matches[1],
            'occurrence' => $matches[3] ?: null,
            'subfields' => $subfields
        );
        return $field;
    }

    /**
     * Split subfields into array structures.
     *
     * @param  string $subfield
     * @return array
     */
    private function splitSubfield ($subfield)
    {
        return array('code' => $subfield[0], 'value' => substr($subfield, 1));
    }

    /**
     * Return next octet without moving pointer.
     *
     * @return string|null
     */
    private function peek ()
    {
        return $this->getc(true);
    }

    /**
     * Return next octet.
     *
     * If argument is true, the internal pointer is not moved after reading
     * the octet.
     *
     * @param  boolean $peek
     * @return string|null
     */
    private function getc ($peek = false)
    {
        if ($this->feof()) {
            return null;
        }
        if ($this->bufferPosition == $this->bufferSize) {
            $buffer = fread($this->stream, 4096);
            if ($buffer === false) {
                throw new RuntimeException('Error reading input stream');
            }
            if (strlen($buffer) === 0) {
                return null;
            }
            $this->bufferPosition = 0;
            $this->bufferSize = strlen($buffer);
            $this->buffer = $buffer;
        }
        $octet = $this->buffer[$this->bufferPosition];
        if (!$peek) {
            $this->bufferPosition++;
        }
        return $octet;
    }

    /**
     * Return true if input stream and read-buffer exhausted.
     *
     * @return boolean
     */
    private function feof ()
    {
        return (feof($this->stream) && ($this->bufferPosition == $this->bufferSize));
    }
}