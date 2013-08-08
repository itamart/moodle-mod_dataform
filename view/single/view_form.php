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
 * @subpackage single
 * @copyright 2013 Itamar Tzadok
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once("$CFG->dirroot/mod/dataform/view/view_form.php");

class dataformview_single_form extends dataformview_base_form {

    /**
     *
     */
    function view_definition_after_gps() {
        parent::view_definition_after_gps();

        $view = $this->_view;
        $editoroptions = $view->editors();

        $mform = &$this->_form;
        
        // Remove unnecessary view settings
        $mform->removeElement('filter');
        $mform->removeElement('groupby');
        $mform->removeElement('perpage');

        // repeated entry (param2)
        //-------------------------------------------------------------------------------
        $mform->addElement('header', 'entrytemplatehdr', get_string('entrytemplate', 'dataform'));
        $mform->addHelpButton('entrytemplatehdr', 'entrytemplate', 'dataform');

        $mform->addElement('editor', 'eparam2_editor', '', null, $editoroptions['param2']);
        $mform->setDefault("eparam2_editor[format]", FORMAT_PLAIN);
        $this->add_tags_selector('eparam2_editor', 'field');
        $this->add_tags_selector('eparam2_editor', 'character'); 
    }
}
