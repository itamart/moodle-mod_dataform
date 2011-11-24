<?php

/**
 * This file is part of the Dataform module for Moodle - http://moodle.org/.
 *
 * @package mod-dataform
 * @subpackage field-multiselect
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

class mod_dataform_field_multiselect_form extends mod_dataform_field_form {

    /**
     *
     */
    function field_definition() {

        $field = $this->_customdata['field'];
        $mform =& $this->_form;

    //-------------------------------------------------------------------------------
        $mform->addElement('header', 'fieldattributeshdr', get_string('fieldattributes', 'dataform'));
        
        // options
        $mform->addElement('textarea', 'param1', get_string('fieldoptions', 'dataform'), 'wrap="virtual" rows="10" cols="50"');

        // default options
        $mform->addElement('textarea', 'param2', get_string('fieldoptionsdefault', 'dataform'), 'wrap="virtual" rows="5" cols="50"');

        // options separator
        $mform->addElement('select', 'param3', get_string('fieldoptionsseparator', 'dataform'), array_map('current', $field->separators));

        // allow add option
        $mform->addElement('selectyesno', 'param4', get_string('allowaddoption', 'dataform'));

    }
}
