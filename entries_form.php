<?php

/**
 * This file is part of the Dataform module for Moodle - http://moodle.org/.
 *
 * @package mod-dataform
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

require_once $CFG->libdir.'/formslib.php';

/**
 *
 */
class mod_dataform_entries_form extends moodleform {

    function definition() {

        $df = $this->_customdata['df'];
        $entries = $this->_customdata['entries'];
        $view = $this->_customdata['view'];
        $filter = $this->_customdata['filter'];
        $update = $this->_customdata['update'];

        $mform =& $this->_form;

        // hidden optional params
        //-------------------------------------------------------------------------------
        $mform->addElement('hidden', 'd', $df->id());
        $mform->setType('d', PARAM_INT);

        $mform->addElement('hidden', 'view', $view->id());
        $mform->setType('view', PARAM_INT);

        $mform->addElement('hidden', 'filter', $filter->id);
        $mform->setType('filter', PARAM_INT);

        $mform->addElement('hidden', 'page', $filter->page);
        $mform->setType('page', PARAM_ALPHAEXT);

        $mform->addElement('hidden', 'eid', $filter->eid);
        $mform->setType('eid', PARAM_INT);

        $mform->addElement('hidden', 'update', $update);
        $mform->setType('update', PARAM_SEQUENCE);

        // buttons
        //-------------------------------------------------------------------------------
        $this->add_action_buttons(true);

        // entries
        //-------------------------------------------------------------------------------
        $entries->definition_to_form($mform);

        // buttons again
        //-------------------------------------------------------------------------------
        $this->add_action_buttons(true);
    }

    /**
     *
     */
    function data_preprocessing(&$default_values){
        $view = $this->_customdata['view'];
        $df = $this->_customdata['df'];
        //$editors = $this->_customdata['editors'];

        //foreach ($editors as $editorname => $options) {
        //     $default_values = file_prepare_standard_editor($default_values, "e$editorname", $options, $df->context, 'mod_dataform', 'view-'. $view->filearea($default_values->name));
        //}
    }

    /**
     *
     */
    function set_data($default_values) {
        $this->data_preprocessing($default_values);
        parent::set_data($default_values);
    }

    /**
     *
     */
    function get_data($slashed = true) {
        if ($data = parent::get_data($slashed)) {
            $view = $this->_customdata['view'];
            $df = $this->_customdata['df'];
            //$editors = $this->_customdata['editors'];

            //foreach ($editors as $editorname => $options) {
            //    $data = file_postupdate_standard_editor($data, "e$editorname", $options, $df->context, 'mod_dataform', $view->filearea($data->name));
            //}
        }
        return $data;
    }

    /**
     *
     */
    function add_action_buttons($cancel = true, $submitlabel = null) {
        $mform = &$this->_form;

        $mform->addElement('html', '<div class="mdl-align">');
        parent::add_action_buttons($cancel, $submitlabel);
        $mform->addElement('html', '</div>');
    }
    
    /**
     *
     */
    public function html() {
        return $this->_form->toHtml();
    }    
}
