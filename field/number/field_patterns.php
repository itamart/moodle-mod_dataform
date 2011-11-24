<?php

/**
 * This file is part of the Dataform module for Moodle - http://moodle.org/.
 *
 * @package mod-dataform
 * @package field-number
 * @copyright 2011 Itamar Tzadok
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * The Dataform has been developed as an enhanced counterpart
 * of Moodle's Database activity module (1.9.11+ (20110323)).
 * To the extent that Dataform code corresponds to Database code,
 * certain copyrights on the Database module may obtain.
 *
 * Moodle is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Moodle is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Moodle. If not, see <http://www.gnu.org/licenses/>.
 */

defined('MOODLE_INTERNAL') or die();

require_once("$CFG->dirroot/mod/dataform/field/field_patterns.php");

/**
 *
 */
class mod_dataform_field_number_patterns extends mod_dataform_field_text_patterns {

    /**
     *
     */
    public function display_edit(&$mform, $entry) {
        parent::display_edit($mform, $entry);
        
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
            $number = $entry->{"c{$fieldid}_content"};
/*
            $decimals = trim($this->field->param1);
            // only apply number formatting if param1 contains an integer number >= 0:
            if (preg_match("/^\d+$/", $decimals)) {
                $decimals = $decimals * 1;
                // removes leading zeros (eg. '007' -> '7'; '00' -> '0')
                $str = format_float($number, $decimals, true);
                // For debugging only:
#                $str .= " ($decimals)";
            } else {
                $str = $number;
            }
*/
            $str = $number;
        } else {
            $str = '';
        }
        
        return $str;
    }
}
