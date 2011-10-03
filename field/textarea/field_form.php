<?php

/**
 * This file is part of the Dataform module for Moodle - http://moodle.org/.
 *
 * @package mod-dataform
 * @subpackage field-textarea
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

class mod_dataform_field_textarea_form extends mod_dataform_field_form {

    /**
     *
     */
    function field_definition() {
        global $CFG;

        $df = $this->_customdata['df'];
        $mform =& $this->_form;

    // editor settings
    //-------------------------------------------------------------------------------
        $mform->addElement('header', 'fieldattributeshdr', get_string('fieldattributes', 'dataform'));

        // editor enabled
        $mform->addElement('selectyesno', 'param1', get_string('editorenable', 'dataform'));
        $mform->setDefault('param1', 1);

        // field width (cols)
        $mform->addElement('text', 'param2', get_string('cols', 'dataformfield_textarea'), array('size'=>'8'));
        $mform->setType('param2', PARAM_INT);
        $mform->addRule('param2', null, 'numeric', null, 'client');
        $mform->setDefault('param2', 40);

        // field height (rows)
        $mform->addElement('text', 'param3', get_string('rows', 'dataformfield_textarea'), array('size'=>'8'));
        $mform->setType('param3', PARAM_INT);
        $mform->addRule('param3', null, 'numeric', null, 'client');
        $mform->setDefault('param3', 20);

        // trust text
        $mform->addElement('selectyesno', 'param4', get_string('trusttext', 'dataform'));
        $mform->setDefault('param4', 0);

        // word count
        $mform->addElement('text', 'param7', get_string('wordcountmin', 'dataformfield_textarea'), array('size'=>'4'));
        $mform->addElement('text', 'param8', get_string('wordcountmax', 'dataformfield_textarea'), array('size'=>'4'));
        $mform->addElement('selectyesno', 'param9', get_string('wordcountshow', 'dataformfield_textarea'));

    // editor files settings
    //-------------------------------------------------------------------------------
        $mform->addElement('header', 'fieldattributeshdr', get_string('fieldattributes', 'dataform'));

        // max bytes
        $options = get_max_upload_sizes($CFG->maxbytes, $df->course->maxbytes);
        $mform->addElement('select', 'param5', get_string('filemaxsize', 'dataform'), $options);

        // max files
        $range = range(1, 100);
        $options = array(-1 => get_string('unlimited')) + array_combine($range, $range);
        $mform->addElement('select', 'param6', get_string('filesmax', 'dataform'), $options);
        $mform->setDefault('param2', -1);

    }
}
