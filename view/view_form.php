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
 * @copyright 2011 Itamar Tzadok
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') or die;

require_once("$CFG->libdir/formslib.php");

/**
 *
 */
class dataformview_base_form extends moodleform {
    protected $_view = null;
    protected $_df = null;

    public function __construct($view, $action = null, $customdata = null, $method = 'post', $target = '', $attributes = null, $editable = true) {
        $this->_view = $view;
        $this->_df = $view->get_df();
        
        parent::__construct($action, $customdata, $method, $target, $attributes, $editable);       
    }
    
    /**
     *
     */
    function definition() {
        $view = $this->_view;
        $df = $this->_df;
        $editoroptions = $view->editors();
        $mform = &$this->_form;

        // buttons
        //-------------------------------------------------------------------------------
        $this->add_action_buttons();

        // general
        //-------------------------------------------------------------------------------
        $mform->addElement('header', 'general', get_string('viewgeneral', 'dataform'));
        $mform->addHelpButton('general', 'viewgeneral', 'dataform');

        // name and description
        $mform->addElement('text', 'name', get_string('name'));
        $mform->addRule('name', null, 'required', null, 'client');

        $mform->addElement('text', 'description', get_string('description'), array('size'=>'64'));
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
            $mform->setType('description', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEAN);
            $mform->setType('description', PARAM_CLEAN);
        }

         // visibility
        $visibilityoptions = array(0=>'disabled',1=>'enabled',2=>'visible');
        $mform->addElement('select', 'visible', get_string('viewvisibility', 'dataform'), $visibilityoptions);
        $mform->setDefault('visible', 2);

        // filter
        if (!$filtersmenu = $df->get_filter_manager()->get_filters(null, true)) {
            $filtersmenu = array(0 => get_string('filtersnonedefined', 'dataform'));
        } else {
           $filtersmenu = array(0 => get_string('choose')) + $filtersmenu;
        }
        $mform->addElement('select', 'filter', get_string('viewfilter', 'dataform'), $filtersmenu);
        $mform->setDefault('filter', 0);

        // group by
        if (!$fieldsmenu = $view->get_df()->get_fields(array('entry'), true)) {
            $fieldsmenu = array('' => get_string('fieldsnonedefined', 'dataform'));
        } else {
           $fieldsmenu = array('' => get_string('choose')) + $fieldsmenu;
        }        
        $mform->addElement('select', 'groupby', get_string('viewgroupby', 'dataform'), $fieldsmenu);

        // entries per page
        $perpageoptions = array(0=>get_string('choose'),1=>1,2=>2,3=>3,4=>4,5=>5,6=>6,7=>7,8=>8,9=>9,10=>10,15=>15,
                            20=>20,30=>30,40=>40,50=>50,100=>100,200=>200,300=>300,400=>400,500=>500,1000=>1000);
        $mform->addElement('select', 'perpage', get_string('viewperpage', 'dataform'), $perpageoptions);
        $mform->setDefault('perpage', 10);
                            

        // view specific definition
        //-------------------------------------------------------------------------------
        $this->view_definition_before_gps();

        // View template
        //-------------------------------------------------------------------------------
        $mform->addElement('header', 'viewtemplatehdr', get_string('viewtemplate', 'dataform'));
        $mform->addHelpButton('viewtemplatehdr', 'viewtemplate', 'dataform');

        // section position
        //$sectionposoptions = array(0 => 'top', 1 => 'left', 2 => 'right', 3 => 'bottom');
        //$mform->addElement('select', 'sectionpos', get_string('viewsectionpos', 'dataform'), $sectionposoptions);
        //$mform->setDefault('sectionpos', 0);
        
        // section
        $mform->addElement('editor', 'esection_editor', '', null, $editoroptions['section']);
        $this->add_tags_selector('esection_editor', 'general');

        // view specific definition
        //-------------------------------------------------------------------------------
        $this->view_definition_after_gps();

        // buttons
        //-------------------------------------------------------------------------------
        $this->add_action_buttons();
    }

    /**
     *
     */
    function view_definition_before_gps() {
    }

    /**
     *
     */
    function view_definition_after_gps() {
    }

    /**
     *
     */
    function add_action_buttons($cancel = true, $submit = null){
        $mform = &$this->_form;

        $buttonarray=array();
        // save and display
        $buttonarray[] = &$mform->createElement('submit', 'submitbutton', get_string('savechanges'));
        // save and continue
        $buttonarray[] = &$mform->createElement('submit', 'submitreturnbutton', get_string('savecontinue', 'dataform'));
        // reset to default
        $buttonarray[] = &$mform->createElement('submit', 'resetdefaultbutton', get_string('viewresettodefault', 'dataform'));
        $mform->registerNoSubmitButton('resetdefaultbutton');
        // switch editor
        // cancel
        $buttonarray[] = &$mform->createElement('cancel');
        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
        $mform->closeHeaderBefore('buttonar');
    }

    /**
     *
     */
    function add_tags_selector($editorname, $tagstype){
        $view = $this->_view;
        $mform = &$this->_form;
        switch ($tagstype) {
            case 'general':
                $tags = $view->patterns()->get_menu();
                $label = get_string('viewgeneraltags','dataform');
                break;
                
            case 'field':
                $tags = $view->field_tags();
                $label = get_string('viewfieldtags','dataform');
                break;
                
            case 'character':
                $tags = $view->character_tags();
                $label = get_string('viewcharactertags','dataform');
                break;
                
            default:
                $tags = null;
        }
                
        if (!empty($tags)) {
            $grp = array();
            $grp[] = &$mform->createElement('html', html_writer::start_tag('div', array('class' => 'fitem')));
            $grp[] = &$mform->createElement('html', '<div class="fitemtitle"><label>'. $label. '</label></div>');
            $grp[] = &$mform->createElement('html', '<div class="felement fselect">'. html_writer::select($tags, "{$editorname}{$tagstype}tags", '', array('' => 'choosedots'), array('onchange' => "insert_field_tags(this, '{$editorname}');this.selectedIndex=0;")). '</div>');
            $grp[] = &$mform->createElement('html', html_writer::end_tag('div'));
            $mform->addGroup($grp, "{$editorname}{$tagstype}tagsgrp", '', array(' '), false);
        }
    }
    
    /**
     *
     */
    function data_preprocessing(&$data){
    }

    /**
     *
     */
    function validation($data, $files) {
        $errors = parent::validation($data, $files);

        $view = $this->_view;
        $df = $this->_df;
        $errors= array();
        
        if ($df->name_exists('views', $data['name'], $view->id())) {
            $errors['name'] = get_string('invalidname','dataform', get_string('view', 'dataform'));
        }

        return $errors;
    }

}
