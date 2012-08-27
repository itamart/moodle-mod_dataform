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
 * @package mod-dataform
 * @copyright 2011 Itamar Tzadok
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') or die;

require_once("$CFG->libdir/formslib.php");

/**
 *
 */
class mod_dataform_view_base_form extends moodleform {

    /**
     *
     */
    function definition() {
        $view = $this->_customdata['view'];
        $df = $this->_customdata['df'];
        $editoroptions = $view->editors();
        $mform =& $this->_form;

        // hidden optional params
        //-------------------------------------------------------------------------------
        $mform->addElement('hidden', 'type', $view->type());
        $mform->setType('type', PARAM_ALPHA);

        $streditinga = $view->id() ? get_string('viewedit', 'dataform', $view->name()) : get_string('viewnew', 'dataform', $view->typename());
        $mform->addElement('html', '<h2 class="mdl-align">'.format_string($streditinga).'</h2>');

        // buttons
        //-------------------------------------------------------------------------------
        $this->add_action_buttons();

        // general
        //-------------------------------------------------------------------------------
        $mform->addElement('header', 'general', get_string('general', 'form'));

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
        if (!$filtersmenu = $view->get_filters(null, true)) {
            $filtersmenu = array(0 => get_string('filtersnonedefined', 'dataform'));
        } else {
           $filtersmenu = array(0 => get_string('choose')) + $filtersmenu;
        }
        $mform->addElement('select', 'filter', get_string('viewfilter', 'dataform'), $filtersmenu);
        $mform->setDefault('filter', 0);

        // group by
        if (!$fieldsmenu = $view->get_df()->get_fields(array(-1), true)) {
            $fieldsmenu = array(0 => get_string('fieldsnonedefined', 'dataform'));
        } else {
           $fieldsmenu = array(0 => get_string('choose')) + $fieldsmenu;
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

        // general purpose section
        //-------------------------------------------------------------------------------
        $mform->addElement('header', 'sectionhdr', get_string('viewsection', 'dataform'));

        // section position
        $sectionposoptions = array(0 => 'top', 1 => 'left', 2 => 'right', 3 => 'bottom');
        $mform->addElement('select', 'sectionpos', get_string('viewsectionpos', 'dataform'), $sectionposoptions);
        $mform->setDefault('sectionpos', 0);
        
        // section
        $mform->addElement('editor', 'esection_editor', '', array('cols' => 40, 'rows' => 12), $editoroptions['section']);
        $this->add_tags_selector('esection_editor', 'general');

        // view specific definition
        //-------------------------------------------------------------------------------
        $this->view_definition_after_gps();

        // activity grading (param1)
        //-------------------------------------------------------------------------------
        $this->activity_grading_settings();        

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
    function activity_grading_settings() {
        $view = $this->_customdata['view'];
        $df = $this->_customdata['df'];
        
        if (!$view->supports_activity_grading() or !$df->data->grade) {
            return;
        }

        $mform =& $this->_form;

        // grading settings
        //-------------------------------------------------------------------------------
        $mform->addElement('header', 'gradingsettinshdr', get_string('gradingsettings', 'dataform'));
        
        // grade input (none, text, scale menu)
        $options = array(
            0 => get_string('choosedots'),
            1 => get_string('textbox', 'dataform'),
            2 => get_string('dropdown', 'grades')
        );
        $mform->addElement('select', 'gradeinputtype', get_string('gradeinputtype', 'dataform'), $options);

        // Comment input (none, simple text, rich text)
        $options = array(
            0 => get_string('choosedots'),
            1 => get_string('textbox', 'dataform'),
            2 => get_string('htmleditor')
        );
        $mform->addElement('select', 'commentinputtype', get_string('commentinputtype', 'dataform'), $options);

        // Participant info display (picture, name, idnumber)
        $userinfogrp=array();
        $userinfogrp[] = &$mform->createElement('checkbox', 'userpicture', null, get_string('userpicture', 'dataform'));
        $userinfogrp[] = &$mform->createElement('checkbox', 'username', null, get_string('username', 'dataform'));
        $userinfogrp[] = &$mform->createElement('checkbox', 'useridnumber', null, get_string('useridnumber', 'dataform'));
        $userinfogrp[] = &$mform->createElement('checkbox', 'submissionsinpopup', null, get_string('submissionsinpopup', 'dataform'));
        $mform->addGroup($userinfogrp, 'userinfo', get_string('userinfo', 'dataform'), '<br />', false);
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
        $view = $this->_customdata['view'];
        $mform = &$this->_form;

        switch ($tagstype) {
            case 'general':
                $tags = $view->general_tags();
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
        $view = $this->_customdata['view'];
        $df = $this->_customdata['df'];
        
        // activity grading
        if ($view->supports_activity_grading() and $df->data->grade) {
            if (!empty($data->param1)){
                if ($activitygrading = explode(',', $data->param1) and count($activitygrading) == 6) {
                    list(
                        $data->gradeinputtype,
                        $data->commentinputtype,
                        $data->userpicture,
                        $data->username,
                        $data->useridnumber,
                        $data->submissionsinpopup
                    ) = $activitygrading;
                }
            }
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
            $data = $this->get_activity_grading_data($data);
        }
        return $data;
    }
            
    /**
     *
     */
    protected function get_activity_grading_data($data) {
        $view = $this->_customdata['view'];
        $df = $this->_customdata['df'];
        
        // activity grading
        if ($view->supports_activity_grading() and $df->data->grade) {
            // activity grading
            if (!empty($data->gradeinputtype) or !empty($data->commentinputtype)) {
                $arr = array(
                    'gradeinputtype',
                    'commentinputtype',
                    'userpicture',
                    'username',
                    'useridnumber',
                    'submissionsinpopup'
                );

                foreach ($arr as $key => $var) {
                    if (isset($data->$var)) {
                        $arr[$key] = $data->$var;
                        unset($data->$var);
                    } else {
                        $arr[$key] = 0;
                    }                        
                }
                $data->param1 = implode(',', $arr);
            } else {
                $data->param1 = null;
            }
        }
        return $data;
    }

    /**
     *
     */
    function validation($data, $files) {
        $errors = parent::validation($data, $files);

        $df = $this->_customdata['df'];
        $view = $this->_customdata['view'];
        $errors= array();
        
        if ($df->name_exists('views', $data['name'], $view->id())) {
            $errors['name'] = get_string('invalidname','dataform', get_string('view', 'dataform'));
        }

        return $errors;
    }

}
