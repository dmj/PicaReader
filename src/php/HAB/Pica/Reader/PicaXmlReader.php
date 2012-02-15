<?php

/**
 * The PicaXmlReader class file.
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
 * @copyright Copyright (c) 2012 by Herzog August Bibliothek Wolfenbüttel
 * @license   http://www.gnu.org/licenses/gpl.html GNU General Public License v3
 */

namespace HAB\Pica\Reader;

use \XMLReader;

/**
 * Reader for Pica+ records encoded in PicaXML.
 *
 * @package   PicaReader
 * @author    David Maus <maus@hab.de>
 * @copyright Copyright (c) 2012 by Herzog August Bibliothek Wolfenbüttel
 * @license   http://www.gnu.org/licenses/gpl.html GNU General Public License v3
 */
class PicaXmlReader extends Reader {

  /**
   * @var string XML namespace URI of PicaXML
   */
  const PICAXML_NAMESPACE_URI = 'info:srw/schema/5/picaXML-v1.0';

  /**
   * @var \XMLReader XML Reader instance
   */
  private $_xmlReader;

  /**
   * Constructor.
   *
   * @return void
   */
  public function __construct () {
    parent::__construct();
    $this->_xmlReader = new XMLReader();
  }

  /**
   * Prepare the reader for reading data.
   *
   * @param  string $input Data to read
   * @return void
   */
  public function open ($input) {
    $this->_xmlReader->XML($input);
    parent::open($input);
  }

  /**
   * Close current input data.
   *
   * @return void
   */
  public function close () {
    $this->_xmlReader->close();
    parent::close();
  }

  /**
   * Return the array representation of the next record in current input data.
   *
   * @see    ReaderAbstract::next()
   *
   * @return array|false Record or false if there are no more records
   */
  protected function next () {
    if ($this->forwardTo('record', self::PICAXML_NAMESPACE_URI)) {
      $record = array('fields' => array());
      while (!$this->atElementEnd('record', self::PICAXML_NAMESPACE_URI) && $this->_xmlReader->read()) {
        if ($this->atElement('datafield', self::PICAXML_NAMESPACE_URI)) {
          $record['fields'] []= $this->readField();
        }
      }
    } else {
      $record = false;
    }
    return $record;
  }

  /**
   * Return array representation of datafield at cursor.
   *
   * The cursor is expected to be positioned on the opening field element.
   *
   * @return array Field
   */
  protected function readField () {
    $field = array('tag' => $this->_xmlReader->getAttribute('tag'),
                   'occurrence' => $this->_xmlReader->getAttribute('occurrence'),
                   'subfields' => array());
    while (!$this->atElementEnd('datafield', self::PICAXML_NAMESPACE_URI) && $this->_xmlReader->read()) {
      if ($this->atElement('subfield', self::PICAXML_NAMESPACE_URI)) {
        $subfield = array('code' => $this->_xmlReader->getAttribute('code'), 'value' => '');
        while (!$this->atElementEnd('subfield', self::PICAXML_NAMESPACE_URI) && $this->_xmlReader->read()) {
          switch ($this->_xmlReader->nodeType) {
            case XMLReader::TEXT:
            case XMLReader::SIGNIFICANT_WHITESPACE:
            case XMLReader::CDATA:
              $subfield['value'] .= $this->_xmlReader->value;
              break;
          }
        }
        $field['subfields'] []= $subfield;
      }
    }
    return $field;
  }

  /**
   * Move cursor forward to named element.
   *
   * The cursor is not moved if it is already positioned at the named element.
   *
   * @param  string $name Element local name
   * @param  string $uri Namespace URI
   * @return boolean TRUE if cursor is at specified element or FALSE if cursor
   *         reached the end of the document
   */
  protected function forwardTo ($name, $uri) {
    while (!$this->atElement($name, $uri) && $this->_xmlReader->read()) { }
    return ($this->_xmlReader->nodeType === XMLReader::ELEMENT);
  }

  /**
   * Return TRUE if the cursor is positioned at the named element.
   *
   * @param  string $name Element local name
   * @param  string $uri Namespace URI
   * @return boolean TRUE if the cursor is positioned at the element
   */
  protected function atElement ($name, $uri) {
    return ($this->_xmlReader->nodeType === XMLReader::ELEMENT &&
            $this->_xmlReader->localName === $name &&
            $this->_xmlReader->namespaceURI === $uri);
  }

  /**
   * Return TRUE if the cursor is positioned at the end of the named element.
   *
   * @param  string $name Element local name
   * @param  string $uri Namespace URI
   * @return boolean TRUE if the cursor is positioned at the end of the named
   *         element
   */
  protected function atElementEnd ($name, $uri) {
    return ($this->_xmlReader->nodeType === XMLReader::END_ELEMENT &&
            $this->_xmlReader->localName === $name &&
            $this->_xmlReader->namespaceURI === $uri);
  }
}
