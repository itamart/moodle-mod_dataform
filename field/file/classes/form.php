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
 * @subpackage file
 * @copyright 2011 Itamar Tzadok
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class dataformfield_file_form extends mod_dataform\pluginbase\dataformfieldform {

    /**
     *
     */
    public function field_definition() {
        // Appearance
        $this->definition_appearance();

        // File settings
        $this->definition_file_settings();
    }

    /**
     *
     */
    protected function definition_appearance() {
        $field = &$this->_field;
        $mform = &$this->_form;

        // Appearance
        $mform->addElement('header', 'appearancehdr', get_string('appearance'));
        $mform->setExpanded('appearancehdr');

        // Files separator (param4)
        $options = array(
            '' => get_string('none'),
            '<br />' => get_string('newline', 'dataformfield_file')
        );
        $mform->addElement('select', 'param4', get_string('filesseparator', 'dataformfield_file'), $options);
        $mform->addHelpButton('param4', 'filesseparator', 'dataformfield_file');
    }

    /**
     *
     */
    protected function definition_file_settings() {
        global $CFG;

        $mform =& $this->_form;

        // File settings
        // -------------------------------------------------------------------------------
        $mform->addElement('header', 'filesettingshdr', get_string('filesettings', 'dataform'));

        // max bytes (param1)
        $options = get_max_upload_sizes($CFG->maxbytes, $this->_field->df->course->maxbytes);
        $mform->addElement('select', 'param1', get_string('filemaxsize', 'dataform'), $options);

        // max files (param2)
        $range = range(1, 100);
        $options = array_combine($range, $range);
        $options[-1] = get_string('unlimited');
        $mform->addElement('select', 'param2', get_string('filesmax', 'dataform'), $options);
        $mform->setDefault('param2', -1);

        // accetped types
        $this->definition_filetypes();

    }

    /**
     *
     */
    protected function definition_filetypes() {

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
