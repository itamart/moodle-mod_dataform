<?php
// This file is part of Moodle - http://moodle.org/
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
 * @package dataformfield_entry
 * @copyright 2015 Itamar Tzadok
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class dataformfield_entry_form extends mod_dataform\pluginbase\dataformfieldform {

    /**
     * The field general fieldset. Not required for internal fields.
     *
     * @return void
     */
    protected function definition_general() {
        global $CFG;

        $mform =& $this->_form;
        $paramtext = !empty($CFG->formatstringstriptags) ? PARAM_TEXT : PARAM_CLEAN;
        $mform->addElement('hidden', 'name', '');
        $mform->setType('name', $paramtext);
    }

    /**
     *
     */
    protected function field_definition() {
        $mform =& $this->_form;

        // Entry types.
        $mform->addElement('text', 'entrytypes', get_string('entrytypes', 'dataform'), array('style' => 'width: 90%'));
        $mform->setType('entrytypes', PARAM_TEXT);
        $mform->addHelpButton('entrytypes', 'entrytypes', 'dataform');

    }

    /**
     * The field default content fieldset. No defaults here.
     *
     * @return void
     */
    protected function definition_defaults() {
    }

    /**
     *
     */
    public function data_preprocessing(&$data) {
        $field = $this->_field;

        if ($entrytypes = $field->df->entrytypes) {
            $data->entrytypes = $entrytypes;
        }
    }
}
