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
 * @subpackage select
 * @copyright 2011 Itamar Tzadok
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class dataformfield_select_form extends mod_dataform\pluginbase\dataformfieldform {

    /**
     *
     */
    public function field_definition() {
        $mform =& $this->_form;

        // Options.
        $attrs = 'wrap="virtual" rows="5" cols="30"';
        $mform->addElement('textarea', 'param1', get_string('options', 'dataformfield_select'), $attrs);

        // Reserve param3 for options separator (e.g. radiobutton, image button).

        // Allow add option.
        $mform->addElement('selectyesno', 'param4', get_string('allowaddoption', 'dataformfield_select'));

    }
    /**
     *
     */
    public function definition_default_content() {
        $mform = &$this->_form;
        $field = &$this->_field;

        $defaultcontent = $field->default_content;

        // Content elements.
        $mform->addElement('text', 'contentdefault_selected', get_string('optionsdefault', 'dataformfield_select'));
        $mform->setType('contentdefault_selected', PARAM_TEXT);
        $mform->disabledIf('contentdefault_selected', 'param1', 'eq', '');
        if (!empty($defaultcontent['selected'])) {
            $mform->setDefault('contentdefault_selected', $defaultcontent['selected']);
        }
    }

    /**
     * A hook method for validating field default content. The method modifies an argument array
     * of errors that is then returned in the validation method.
     *
     * @param array The form data
     * @param array The list of errors
     * @return void
     */
    protected function validation_default_content(array $data, array &$errors) {
        if (!empty($data['contentdefault_selected'])) {
            $defaultselected = trim($data['contentdefault_selected']);
            // Get the options.
            if (!empty($data['param1'])) {
                $options = array_map('trim', explode("\n", $data['param1']));
            } else {
                $options = null;
            }

            // The default must be a valid option.
            if (!$options or !in_array($defaultselected, $options)) {
                $errors['contentdefault_selected'] = get_string('invaliddefaultvalue', 'dataformfield_select');
            }
        }
    }
}
