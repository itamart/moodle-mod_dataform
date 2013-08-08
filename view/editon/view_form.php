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
 * @subpackage editon
 * @copyright 2012 Itamar Tzadok
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once("$CFG->dirroot/mod/dataform/view/view_form.php");

class dataformview_editon_form extends dataformview_base_form {

    /**
     *
     */
    function view_definition_after_gps() {
        parent::view_definition_after_gps();

        $view = $this->_view;
        $editoroptions = $view->editors();
        $editorattr = array('cols' => 40, 'rows' => 12);

        $mform = &$this->_form;

        // Disable unrequired parent fields
        //$mform->disabledIf('filter', 'filter', 'eq', 0);
        //$mform->disabledIf('groupby', 'groupby', 'eq', 0);
        //$mform->disabledIf('perpage', 'perpage', 'eq', 0);

        // View settings
        //-------------------------------------------------------------------------------
        $mform->addElement('header', 'settingshdr', get_string('settings'));

        // Submit button label (param4)
        $mform->addElement('text', 'param4', get_string('submitlabel', 'dataformview_editon'));

        // Show cancel button (param8)
        $mform->addElement('selectyesno', 'param8', get_string('showcancel', 'dataformview_editon'));

        // Show save and continue (param9)
        $mform->addElement('selectyesno', 'param9', get_string('showsavecontinue', 'dataformview_editon'));

        // Response for submission (param7)       
        $mform->addElement('editor', 'eparam7_editor', get_string('responsemessage', 'dataformview_editon'), $editorattr, $editoroptions['param7']);
        $mform->setDefault("eparam7_editor[format]", FORMAT_HTML);
        
        // Response timeout (param5)
        $options = range(0, 20);
        $options[0] = get_string('none');
        $mform->addElement('select', 'param5', get_string('responsetimeout', 'dataformview_editon'), $options);
        
        // Return after submission (param6)
        $options = array(
            $view::RETURN_SELF => get_string('returnself', 'dataformview_editon'),
            $view::RETURN_NEW => get_string('returnnew', 'dataformview_editon'),
            $view::RETURN_CALLER => get_string('returncaller', 'dataformview_editon')
        );
        $mform->addElement('select', 'param6', get_string('submitreturn', 'dataformview_editon'), $options);
        
    }
}
