<?php

/**
 * The PicaPlainParser class file.
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

namespace HAB\Pica\Parser;

use RuntimeException;

class PicaPlainParser implements PicaPlainParserInterface
{

    /**
     * {@inheritDoc}
     */
    public function parseField ($line) 
    {
        $field = array('subfields' => array());
        $match = array();
        if (preg_match('#^([012][0-9]{2}[A-Z@])(/([0-9]{2}))?\s+(\$.*)$#Du', $line, $match)) {
            $field = array('tag' => $match[1],
                           'occurrence' => $match[3] ?: null,
                           'subfields' => $this->parseSubfields($match[4]));;
        } else {
            throw new RuntimeException("Invalid characters in PicaPlain record at line: {$line}");
        }
        return $field;
    }

    public function parseSubfields ($str) 
    {
        $subfields = array();
        $subfield = null;
        $pos = 0;
        $max = strlen($str);
        $state = '$';
        do {
            switch ($state) {
                case '$':
                    if (is_array($subfield)) {
                        $subfields []= $subfield;
                        $subfield = array();
                    }
                    $pos += 1;
                    $state = 'code';
                    break;
                case 'code':
                    $subfield['code'] = $str[$pos];
                    $subfield['value'] = '';
                    $pos += 1;
                    $state = 'value';
                    break;
                case 'value':
                    $next = strpos($str, '$', $pos);
                    if ($next === false) {
                        $subfield['value'] .= substr($str, $pos);
                        $pos = $max;
                    } else {
                        $subfield['value'] .= substr($str, $pos, ($next - $pos));
                        $pos = $next;
                        if (isset($str[$pos + 1]) && $str[$pos + 1] === '$') {
                            $subfield['value'] .= '$';
                            $pos += 2;
                        } else {
                            $state = '$';
                        }
                    }
                    break;
            }
        } while ($pos < $max);
        $subfields []= $subfield;
        return $subfields;
    }
}