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
 * @package dataformfield
 * @subpackage entryactions
 * @copyright 2013 Itamar Tzadok
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class dataformfield_entryactions_form extends mod_dataform\pluginbase\dataformfieldform {

    /**
     *
     */
    protected function field_definition() {

        $mform =& $this->_form;

        foreach (array('edit', 'more') as $action) {
            $this->field_definition_action($action);
        }
    }

    /**
     *
     */
    protected function field_definition_action($action) {
        global $CFG;

        $field = $this->_field;
        $mform = &$this->_form;

        // Header.
        $mform->addElement('header', '', get_string($action, 'dataformfield_entryactions'));

        // Target view (param1).
        $viewman = mod_dataform_view_manager::instance($field->dataid);
        $options = array('' => get_string('default'));
        if ($viewsmenu = $viewman->views_menu) {
            $options = $options + $viewsmenu;
        }
        $mform->addElement('select', "targetview_$action", get_string('targetview', 'dataformfield_entryactions'), $options);
        $mform->addHelpButton("targetview_$action", 'targetview', 'dataformfield_entryactions');

        // Additional action params.
        $mform->addElement('text', "actionparams_$action", get_string('actionparams', 'dataformfield_entryactions'));
        $mform->setType("actionparams_$action", PARAM_TEXT);
        $mform->addHelpButton("actionparams_$action", 'targetview', 'dataformfield_entryactions');

        // Theme icon.
        $mform->addElement('text', "themeicon_$action", get_string('themeicon', 'dataformfield_entryactions'));
        $mform->setType("themeicon_$action", PARAM_TEXT);
        $mform->addHelpButton("themeicon_$action", 'targetview', 'dataformfield_entryactions');

        /*
        // Custom icon.
        $options = array('subdirs' => 0, 'maxbytes' => $COURSE->maxbytes, 'maxfiles' => 1, 'accepted_types' => array('image'));
        $draftitemid = file_get_submitted_draft_itemid('customicon');
        file_prepare_draft_area($draftitemid, $this->context->id, 'mod_dataform', "field_{$action}icon", 0, $options);
        $mform->addElement('filemanager', 'customicon', get_string('activityicon', 'dataform'), null, $options);
        $mform->setDefault('customicon', $draftitemid);

        // Entry condition (filter).
        */
    }

}
