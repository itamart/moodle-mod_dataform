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
 * @package dataformfield_grading
 * @copyright 2014 Itamar Tzadok
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class dataformfield_grading_form extends \mod_dataform\pluginbase\dataformfieldform {

    /**
     *
     */
    protected function field_definition() {

        $mform =& $this->_form;

        // On events.
        $options = array(
            'entry_created' => get_string('event_entry_created', 'dataform'),
            'entry_updated' => get_string('event_entry_updated', 'dataform'),
            'entry_deleted' => get_string('event_entry_deleted', 'dataform'),
        );
        $select = &$mform->addElement('select', 'config[events]', get_string('events', 'dataformfield_grading'), $options);
        $select->setMultiple(true);
        $mform->addHelpButton('config[events]', 'events', 'dataformfield_grading');

        // Multiple updates.
        $mform->addElement('checkbox', 'config[multiupdate]', get_string('multiupdate', 'dataformfield_grading'));
        $mform->addHelpButton('config[multiupdate]', 'multiupdate', 'dataformfield_grading');
    }

    /**
     * The field default content fieldset. Contains a header and calls the hook methods
     * {@link dataformfieldform::definition_default_settings()} and
     * {@link dataformfieldform::definition_default_content()}.
     *
     * @return void
     */
    protected function definition_defaults() {
    }

    /**
     *
     */
    public function data_preprocessing(&$data) {
        parent::data_preprocessing($data);

        $field = $this->_field;
        $data->config = $field->config;
    }

    /**
     *
     */
    public function set_data($data) {
        $this->data_preprocessing($data);
        parent::set_data($data);
    }

    /**
     *
     */
    public function get_data() {
        $field = $this->_field;

        if ($data = parent::get_data()) {
            $config = null;
            if (!empty($data->config['events']) or !empty($data->config['multiupdate'])) {
                $config = json_encode($data->config);
            }
            $data->param1 = $config;
        }
        return $data;
    }

}
