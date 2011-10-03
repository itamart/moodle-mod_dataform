<?php

/**
 * This file is part of the Dataform module for Moodle - http://moodle.org/
 *
 * @package mod-dataform
 * @subpackage view-hierarchical
 * @author Itamar Tzadok
 * @copyright 2011 Moodle contributors
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * The Dataform has been developed as an enhanced counterpart
 * of Moodle's standard Database activity module. To the extent that the
 * Dataform code corresponds to the Database code (1.9.11+ (20110323)),
 * certain copyrights on certain files may obtain.
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
 
require_once("$CFG->dirroot/mod/dataform/view/block/view_form.php");

class mod_dataform_view_hierarchical_form extends mod_dataform_view_block_form {

    /**
     *
     */
    function view_definition_after_gps() {       

        $df = $this->_customdata['df'];
        $view = $this->_customdata['view'];
        $editoroptions = $view->editors();
        $fieldtags = $view->field_tags();

        $mform =& $this->_form;

    // entries layout
    //-------------------------------------------------------------------------------
        $mform->addElement('header', 'entrieslayouthdr', get_string('entrieslayout', 'dataformview_hierarchical'));

        // node field
        $fieldsmenu = array(0 => get_string('choosedots'));
        if ($nodefields = $df->get_fields_by_type('node', true)) {
            $fieldsmenu = $fieldsmenu + $nodefields;
        }
        $mform->addElement('select', 'param5', get_string('nodefield', 'dataformview_hierarchical'), $fieldsmenu);
 
        // layout
        $options = array(
            0 => get_string('choosedots'),
            dataform_view_hierarchical::LAYOUT_INDENTED => get_string('indented', 'dataformview_hierarchical'),
            dataform_view_hierarchical::LAYOUT_TREEVIEW => get_string('treeview', 'dataformview_hierarchical'),
        );                        
        $mform->addElement('select', 'param6', get_string('layout', 'dataformview_hierarchical'), $options);
        $mform->disabledIf('param6', 'param5', 'eq', 0);
        
        // repeated entry
        $mform->addElement('editor', 'eparam2_editor', get_string('viewlistbody', 'dataform'), null, $editoroptions['param2']);       
        $listbodyatags=array();
        $listbodyatags[] = &$mform->createElement('html', '<div class="fitemtitle"><label>'. get_string('viewavailabletags','dataform'). '</label></div>');
        $listbodyatags[] = &$mform->createElement('html', '<div class="felement fselect">'. html_writer::select($fieldtags, 'listbodytags', '', array('' => 'choosedots'), array('onchange' => 'insert_field_tags(this, \'eparam2_editor\');this.selectedIndex=0;')). '</div>');
        $mform->addGroup($listbodyatags, 'listbodyatags', null, array(' '), false);

    // toc settings
    //-------------------------------------------------------------------------------
        $mform->addElement('header', 'tocsettingshdr', get_string('tocsettings', 'dataformview_hierarchical'));

        // toc view
        $viewsmenu = array(0 => get_string('choosedots'));
        if ($tocviews = $df->get_views_by_type('hierarchical', true)) {
            unset($tocviews[$view->id()]);
            $viewsmenu = $viewsmenu + $tocviews;
        }
        $mform->addElement('select', 'param1', get_string('tocview', 'dataformview_hierarchical'), $viewsmenu);
 


/*
        // show toc
        $mform->addElement('selectyesno', 'tocshow', get_string('tocshow', 'dataformview_hierarchical'));
        $mform->setDefault('tocshow', 0);
        $mform->disabledIf('tocshow', 'param5', 'eq', 0);
                

        // auto update
        $mform->addElement('selectyesno', 'tocautoupdate', get_string('tocautoupdate', 'dataformview_hierarchical'));
        $mform->setDefault('tocautoupdate', 0);
        $mform->disabledIf('tocautoupdate', 'tocshow', 'eq', 0);
        $mform->disabledIf('tocautoupdate', 'param5', 'eq', 0);

        // toc label
        $mform->addElement('editor', 'eparam1_editor', '', null, $editoroptions['param1']);        
        $listbodyatags=array();
        $listbodyatags[] = &$mform->createElement('html', '<div class="fitemtitle"><label>'. get_string('viewavailabletags','dataform'). '</label></div>');
        $listbodyatags[] = &$mform->createElement('html', '<div class="felement fselect">'. html_writer::select($fieldtags, 'listbodytags', '', array('' => 'choosedots'), array('onchange' => 'insert_field_tags(this, \'eparam1_editor\');this.selectedIndex=0;')). '</div>');
        $mform->addGroup($listbodyatags, 'listbodyatags', '', array(' '), false);
*/        
    // other settings
    //-------------------------------------------------------------------------------
        $mform->addElement('header', 'othersettingshdr', get_string('other', 'dataform'));

        // auto numbering
        $mform->addElement('selectyesno', 'param7', get_string('autonumbering', 'dataformview_hierarchical'));

    }

    /**
     *
     *
    function data_preprocessing(&$default_values) {
        // toc options
        if (!empty($default_values->param3)) {
            list(
                $default_values->tocshow,
                $default_values->tocautoupdate) = explode(',', $default_values->param3);
        }
    }

    /**
     *
     *
    function set_data($default_values) {
        $this->data_preprocessing($default_values);
        parent::set_data($default_values);
    }

    /**
     *
     *
    function get_data($slashed = true) {
        if ($data = parent::get_data($slashed)) {
            // set toc options
            if ($data->tocshow) { 
                $data->param3 = "{$data->tocshow},{$data->tocautoupdate}";
            } else {
                $data->param3 = null;
            }
        }
        return $data;
    }
*/
}
