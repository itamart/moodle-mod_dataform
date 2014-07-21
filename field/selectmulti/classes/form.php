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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * @package dataformfield
 * @subpackage selectmulti
 * @copyright 2011 Itamar Tzadok
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class dataformfield_selectmulti_form extends mod_dataform\pluginbase\dataformfieldform {

    /**
     *
     */
    public function field_definition() {

        $mform = &$this->_form;
        $field = $this->_field;

        // -------------------------------------------------------------------------------
        $mform->addElement('header', 'fieldattributeshdr', get_string('fieldattributes', 'dataform'));

        // options
        $mform->addElement('textarea', 'param1', get_string('options', 'dataformfield_selectmulti'), 'wrap="virtual" rows="10" cols="50"');

        // default options
        $mform->addElement('textarea', 'param2', get_string('optionsdefault', 'dataformfield_selectmulti'), 'wrap="virtual" rows="5" cols="50"');

        // options separator
        $mform->addElement('select', 'param3', get_string('optionsseparator', 'dataformfield_selectmulti'), array_map('current', $field->separator_types));

        // allow add option
        $mform->addElement('selectyesno', 'param4', get_string('allowaddoption', 'dataformfield_selectmulti'));

    }
}
