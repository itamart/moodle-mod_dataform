<?php

/**
 * This file is part of the Dataform module for Moodle - http://moodle.org/.
 *
 * @package mod-dataform
 * @subpackage field-number
 * @copyright 2011 Itamar Tzadok
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * The Dataform has been developed as an enhanced counterpart
 * of Moodle's Database activity module (1.9.11+ (20110323)).
 * To the extent that Dataform code corresponds to Database code,
 * certain copyrights on the Database module may obtain, including:
 * @copyright 1999 Moodle Pty Ltd http://moodle.com
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

require_once("$CFG->dirroot/mod/dataform/field/text/field_class.php");

class dataform_field_number extends dataform_field_text {
    public $type = 'number';

    /**
     *
     */
    public function get_sql_compare_text() {
        global $DB;

        return $DB->sql_cast_char2real("c{$this->field->id}.content", true);
    }

    /**
     *
     */
    public function display_edit(&$mform, $entry) {
        parent::display_edit($mform, $entry);
        
        $entryid = $entry->id;
        $fieldname = 'field_'. $this->field->id. '_'. $entryid;
        $mform->addRule($fieldname, null, 'numeric', null, 'client');
    }

}

