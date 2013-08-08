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
 * @package dataformview
 * @subpackage grid
 * @copyright 2012 Itamar Tzadok 
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once("$CFG->dirroot/mod/dataform/view/view_form.php");

class dataformview_grid_form extends dataformview_base_form {

    /**
     *
     */
    function view_definition_after_gps() {

        $view = $this->_view;
        $editoroptions = $view->editors();
        $editorattr = array('cols' => 40, 'rows' => 12);

        $mform = &$this->_form;

        // Grid layout (param3)
        //-------------------------------------------------------------------------------
        $mform->addElement('header', 'gridsettingshdr', get_string('gridsettings', 'dataformview_grid'));

        // cols
        $range = range(2, 50);
        $options = array('' => get_string('choosedots')) + array_combine($range, $range);
        $mform->addElement('select', 'cols', get_string('cols', 'dataformview_grid'), $options);
        
        // rows
        $mform->addElement('selectyesno', 'rows', get_string('rows', 'dataformview_grid'));
        $mform->disabledIf('rows', 'cols', 'eq', '');

        // repeated entry (param2)
        //-------------------------------------------------------------------------------
        $mform->addElement('header', 'entrytemplatehdr', get_string('entrytemplate', 'dataform'));

        $mform->addElement('editor', 'eparam2_editor', '', $editorattr, $editoroptions['param2']);
        $mform->setDefault("eparam2_editor[format]", FORMAT_PLAIN);
        $this->add_tags_selector('eparam2_editor', 'field');
        $this->add_tags_selector('eparam2_editor', 'character');        
    }


    /**
     *
     */
    function data_preprocessing(&$data){
        parent::data_preprocessing($data);
        // grid layout
        if (!empty($data->param3)){
            list(
                $data->cols,
                $data->rows,
            ) = explode(' ', $data->param3);
        }
    }

    /**
     *
     */
    function set_data($data) {
        $this->data_preprocessing($data);
        parent::set_data($data);
    }

    /**
     *
     */
    function get_data($slashed = true) {
        if ($data = parent::get_data($slashed)) {
            // grid layout
            if (!empty($data->cols)) {
                $data->param3 = $data->cols. ' '. (int) !empty($data->rows);
            } else {
                $data->param3 = '';
            }
        }
        return $data;
    }

    
}
