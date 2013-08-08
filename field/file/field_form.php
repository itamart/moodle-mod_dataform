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
 * @subpackage file
 * @copyright 2011 Itamar Tzadok
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once("$CFG->dirroot/mod/dataform/field/field_form.php");

class dataformfield_file_form extends dataformfield_form {

    /**
     *
     */
    function field_definition() {
        global $CFG;

        $mform =& $this->_form;

    //-------------------------------------------------------------------------------
        $mform->addElement('header', 'fieldattributeshdr', get_string('fieldattributes', 'dataform'));
        
        // max bytes (param1)
        $options = get_max_upload_sizes($CFG->maxbytes, $this->_df->course->maxbytes);
        $mform->addElement('select', 'param1', get_string('filemaxsize', 'dataform'), $options);

        // max files (param2)
        $range = range(1, 100);
        $options = array_combine($range, $range);
        $options[-1] = get_string('unlimited');
        $mform->addElement('select', 'param2', get_string('filesmax', 'dataform'), $options);
        $mform->setDefault('param2', -1);

        // accetped types
        $this->filetypes_definition();

    }

    /**
     *
     */
    function filetypes_definition() {

        $mform =& $this->_form;

        // accetped types (param3)
        $options = array();
        $options['*'] = get_string('filetypeany', 'dataform');
        $options['*.jpg,*.gif,*.png'] = get_string('filetypeimage', 'dataform');
        $options['*.html'] = get_string('filetypehtml', 'dataform');

        $mform->addElement('select', 'param3', get_string('filetypes', 'dataform'), $options);
        $mform->setDefault('param3', '*');

    }

}
