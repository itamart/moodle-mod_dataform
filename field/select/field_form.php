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
 * @subpackage select
 * @copyright 2011 Itamar Tzadok
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once("$CFG->dirroot/mod/dataform/field/field_form.php");

class dataformfield_select_form extends dataformfield_form {

    /**
     *
     */
    function field_definition() {

        $mform =& $this->_form;

    //-------------------------------------------------------------------------------
        $mform->addElement('header', 'fieldattributeshdr', get_string('fieldattributes', 'dataform'));
        
        // options
        $mform->addElement('textarea', 'param1', get_string('fieldoptions', 'dataform'), 'wrap="virtual" rows="5" cols="30"');

        // default value
        $mform->addElement('text', 'param2', get_string('default'));
        $mform->setType('param2', PARAM_TEXT);

        // reserve param3 for options separator (e.g. radiobutton, image button)

        // allow add option
        $mform->addElement('selectyesno', 'param4', get_string('allowaddoption', 'dataform'));
        
    }
}
