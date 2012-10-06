<?php
// This file is part of Moodle - http://moodle.org/.
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <http://www.gnu.org/licenses/>.
 
/**
 * @package dataformfield
 * @subpackage number
 * @copyright 2011 Itamar Tzadok
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') or die();

require_once("$CFG->dirroot/mod/dataform/field/text/field_patterns.php");

/**
 *
 */
class mod_dataform_field_number_patterns extends mod_dataform_field_text_patterns {

    /**
     *
     */
    public function display_edit(&$mform, $entry, array $options = null) {
        parent::display_edit($mform, $entry, $options);
        
        $fieldid = $this->_field->id();
        $entryid = $entry->id;
        $fieldname = "field_{$fieldid}_$entryid";
        $mform->addRule($fieldname, null, 'numeric', null, 'client');
    }

    /**
     *
     */
    protected function display_browse($entry, $params = null) {
        $field = $this->_field;
        $fieldid = $field->id();
        if (isset($entry->{"c{$fieldid}_content"})) {
            $number = (float) $entry->{"c{$fieldid}_content"};
            $decimals = (int) trim($field->get('param1'));
            // only apply number formatting if param1 contains an integer number >= 0:
            if ($decimals) {
                // removes leading zeros (eg. '007' -> '7'; '00' -> '0')
                $str = sprintf("%4.{$decimals}f", $number);
                //$str = round($number, $decimals);
            } else {
                $str = (int) $number;
                //$str = round($number);
            }
        } else {
            $str = '';
        }
        
        return $str;
    }
}
