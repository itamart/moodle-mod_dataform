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
 * @package dataformfield
 * @subpackage dataformview
 * @copyright 2013 Itamar Tzadok
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once("$CFG->dirroot/mod/dataform/field/field_form.php");

class dataformfield_dataformview_form extends dataformfield_form {

    /**
     *
     */
    function field_definition() {
        global $CFG, $PAGE, $DB;

        $mform =& $this->_form;

    //-------------------------------------------------------------------------------
        $mform->addElement('header', 'fieldattributeshdr', get_string('fieldattributes', 'dataform'));
        
        // Get all Dataforms where user has managetemplate capability
        // TODO there may be too many
        if ($dataforms = $DB->get_records('dataform')) {
            foreach ($dataforms as $dfid => $dataform) {
                $df = new dataform($dataform);
                // Remove if user cannot manage
                if (!has_capability('mod/dataform:managetemplates', $df->context)) {
                    unset($dataforms[$dfid]);
                    continue;
                }
                $dataforms[$dfid] = $df;
            }
        }
            
        // Dataforms menu
        if ($dataforms) {        
            $dfmenu = array('' => array(0 => get_string('choosedots')));
            foreach($dataforms as $dfid => $df) {
                if (!isset($dfmenu[$df->course->shortname])) {
                    $dfmenu[$df->course->shortname] = array();
                }
                $dfmenu[$df->course->shortname][$dfid] = strip_tags(format_string($df->name(), true));
            }
        } else {
            $dfmenu = array('' => array(0 => get_string('nodataforms', 'dataformfield_dataformview')));
        }    
        $mform->addElement('selectgroups', 'param1', get_string('dataform', 'dataformfield_dataformview'), $dfmenu);
        $mform->addHelpButton('param1', 'dataform', 'dataformfield_dataformview');

        // Views menu
        $options = array(0 => get_string('choosedots'));
        $mform->addElement('select', 'param2', get_string('view', 'dataformfield_dataformview'), $options);
        $mform->disabledIf('param2', 'param1', 'eq', 0);
        $mform->addHelpButton('param2', 'view', 'dataformfield_dataformview');
        
        // Filters menu
        $options = array(0 => get_string('choosedots'));
        $mform->addElement('select', 'param3', get_string('filter', 'dataformfield_dataformview'), $options);
        $mform->disabledIf('param3', 'param1', 'eq', 0);
        $mform->disabledIf('param3', 'param2', 'eq', 0);
        $mform->addHelpButton('param3', 'filter', 'dataformfield_dataformview');

        // Filter by entry attributes (param6)
        $grp = array();
        $grp[] = &$mform->createElement('advcheckbox', 'entryauthor', null, get_string('entryauthor', 'dataformfield_dataformview'), null, array(0,1));
        $grp[] = &$mform->createElement('advcheckbox', 'entrygroup', null, get_string('entrygroup', 'dataformfield_dataformview'), null, array(0,1));
        $mform->addGroup($grp, 'filterbyarr', get_string('filterby', 'dataformfield_dataformview'), '<br />', false);
        $mform->addHelpButton('filterbyarr', 'filterby', 'dataformfield_dataformview');

        // Custom sort options
        $mform->addElement('textarea', 'param4', get_string('customsort', 'dataformfield_dataformview'), array('rows'=>'5', 'cols'=>'60'));
        $mform->setType('param4', PARAM_NOTAGS);
        $mform->disabledIf('param4', 'param1', 'eq', 0);
        $mform->disabledIf('param4', 'param2', 'eq', 0);
        $mform->addHelpButton('param4', 'customsort', 'dataformfield_dataformview');

        // Custom search options
        $mform->addElement('textarea', 'param5', get_string('customsearch', 'dataformfield_dataformview'), array('rows'=>'5', 'cols'=>'60'));
        $mform->setType('param5', PARAM_NOTAGS);
        $mform->disabledIf('param5', 'param1', 'eq', 0);
        $mform->disabledIf('param5', 'param2', 'eq', 0);
        $mform->addHelpButton('param5', 'customsearch', 'dataformfield_dataformview');

        // ajax view loading
        $options = array(
            'dffield' => 'param1',
            'viewfield' => 'param2',
            'filterfield' => 'param3',
            'acturl' => "$CFG->wwwroot/mod/dataform/loaddfviews.php"
        );

        $module = array(
            'name' => 'M.mod_dataform_load_views',
            'fullpath' => '/mod/dataform/dataformloadviews.js',
            'requires' => array('base','io','node')
        );

        $PAGE->requires->js_init_call('M.mod_dataform_load_views.init', array($options), false, $module);
    }

        /**
     *
     */
    function definition_after_data() {
        global $DB;

        if ($selectedarr = $this->_form->getElement('param1')->getSelected()) {
            $dataformid = reset($selectedarr);
        } else {
            $dataformid = 0;
        }
        
        if ($selectedarr = $this->_form->getElement('param2')->getSelected()) {
            $viewid = reset($selectedarr);
        } else {
            $viewid = 0;
        }
        
        if ($dataformid) {           
            if ($views = $DB->get_records_menu('dataform_views', array('dataid' => $dataformid), 'name', 'id,name')) {
                $configview = &$this->_form->getElement('param2');
                foreach($views as $key => $value) {
                    $configview->addOption(strip_tags(format_string($value, true)), $key);
                }
            }
        
            if ($viewid) {           
                if ($filters = $DB->get_records_menu('dataform_filters', array('dataid' => $dataformid), 'name', 'id,name')) {
                    $configfilter = &$this->_form->getElement('param3');
                    foreach($filters as $key => $value) {
                        $configfilter->addOption(strip_tags(format_string($value, true)), $key);
                    }
                }
            }
        }
    }
    
    /**
     *
     */
    function data_preprocessing(&$data){
        if (!empty($data->param6)) {
            list($data->entryauthor, $data->entrygroup) = explode(',', $data->param6);
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
            // set filter by (param6)
            if ($data->entryauthor or $data->entrygroup) {
                $data->param6 = "$data->entryauthor,$data->entrygroup";
            } else {
                $data->param6 = '';
            }
        }
        return $data;
    }   

    /**
     *
     */
    function validation($data, $files) {
        $errors = parent::validation($data, $files);

        $errors= array();
        
        if (!empty($data['param1']) and empty($data['param2'])) {
            $errors['param2'] = get_string('missingview','block_dataform_view');
        }

        return $errors;
    }
}
