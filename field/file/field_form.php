<?php

/**
 * This file is part of the Dataform module for Moodle - http://moodle.org/.
 *
 * @package mod-dataform
 * @subpackage field-file
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

require_once("$CFG->dirroot/mod/dataform/field/field_form.php");

class mod_dataform_field_file_form extends mod_dataform_field_form {

    /**
     *
     */
    function field_definition() {
        global $CFG;

        $df = $this->_customdata['df'];
        $mform =& $this->_form;

    //-------------------------------------------------------------------------------
        $mform->addElement('header', 'fieldattributeshdr', get_string('fieldattributes', 'dataform'));
        
        // max bytes
        $options = get_max_upload_sizes($CFG->maxbytes, $df->course->maxbytes);
        $mform->addElement('select', 'param1', get_string('filemaxsize', 'dataform'), $options);

        // max files
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

        // accetped types
        $options = array();
        $options['*'] = get_string('filetypeany', 'dataform');
        $options['image'] = get_string('filetypeimage', 'dataform');

        $mform->addElement('select', 'param3', get_string('filetypes', 'dataform'), $options);
        $mform->setDefault('param3', '*');

    }

}
