<?php

/**
 * The Reader class file.
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

/**
 * Abstract base class of Pica+ readers.
 *
 * @package   PicaReader
 * @author    David Maus <maus@hab.de>
 * @copyright Copyright (c) 2012 by Herzog August Bibliothek Wolfenbüttel
 * @license   http://www.gnu.org/licenses/gpl.html GNU General Public License v3
 */
abstract class Reader {

  /**
   * TRUE if the reader was opened with input data.
   *
   * @var boolean
   */
  protected $_isOpen = false;

  /**
   * Filter function or NULL if none set.
   *
   * @see Reader::setFilter()
   *
   * @var callback|null
   */
  protected $_filter = null;

  /**
   * Constructor.
   *
   * @return void
   */
  public function __construct () {
  }

  /**
   * Open the reader with input data.
   *
   * @param  string $data Input data
   * @return void
   */
  public function open ($data) {
    if ($this->isOpen()) {
      $this->close();
    }
    $this->_isOpen = true;
  }

  /**
   * Return next record in input data or FALSE if no more records.
   *
   * This function uses the \HAB\Pica\Record\Record::factory() method to create a record and
   * applies a possible filter function to the input data.
   *
   * @see \HAB\Pica\Reader\Reader::setFilter()
   * @see \HAB\Pica\Record\Record::factory()
   *
   * @throws \RuntimeException Error creating a record instance via factory function
   * @return \HAB\Pica\Record\Record|FALSE Next record in input data
   */
  public function read () {
    $record = $this->next();
    if (is_array($record)) {
      $record = $this->applyFilter($record);
      if (is_array($record)) {
        try {
          return \HAB\Pica\Record\Record::factory($record);
        } catch (\Exception $e) {
          throw new \RuntimeException("Error creating record instance in Record::factory()", -1, $e);
        }
      }
    }
    return false;
  }

  /**
   * Read the next record in input data.
   *
   * @return array|FALSE Array representation of the record or FALSE if no more records
   */
  abstract protected function next ();

  /**
   * Return filtered record.
   *
   * Applies the filter function to the array representation of a record.
   *
   * @param  array $record Array representation of record
   * @return array|false Filtered record or FALSE to skip the record
   */
  protected function applyFilter (array $record) {
    $filter = $this->getFilter();
    if ($filter) {
      return call_user_func($filter, $record);
    } else {
      return $record;
    }
  }

  /**
   * Set a filter function.
   *
   * A filter function is every valid callback function that takes the array
   * representation of a record as only argument and returns a possibly
   * modifed array or FALSE to skip the record.
   *
   * @param  callback $filter Filter function
   * @return array|FALSE Filtered record or FALSE if the record should be skipped
   */
  public function setFilter ($filter) {
    $this->_filter = $filter;
  }

  /**
   * Return current filter function.
   *
   * @return callback|NULL Current filter function or NULL if not set
   */
  public function getFilter () {
    return $this->_filter;
  }

  /**
   * Unset the filter filter function.
   *
   * @return void
   */
  public function unsetFilter () {
    $this->_filter = null;
  }

  /**
   * Close the reader.
   *
   * @return void
   */
  public function close () {
    $this->_isOpen = false;
  }

  /**
   * Return TRUE if the reader is open.
   *
   * @return boolean TRUE if the reader is open
   */
  public function isOpen () {
    return $this->_isOpen;
  }
}