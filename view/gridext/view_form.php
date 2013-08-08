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
 * @subpackage gridext
 * @copyright 2011 Itamar Tzadok
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once("$CFG->dirroot/mod/dataform/view/grid/view_form.php");

class dataformview_gridext_form extends dataformview_grid_form {

    /**
     *
     */
    function view_definition_after_gps() {

        $view = $this->_view;
        $editoroptions = $view->editors();
        $editorattr = array('cols' => 40, 'rows' => 5);

        $mform =& $this->_form;

        // list header
        //-------------------------------------------------------------------------------
        $mform->addElement('header', 'listheaderhdr', get_string('viewlistheader', 'dataform'));

        $mform->addElement('editor', 'eparam4_editor', '', $editorattr, $editoroptions['param4']);
        $this->add_tags_selector('eparam4_editor', 'general');
        $this->add_tags_selector('eparam4_editor', 'character');        

        // repeated entry
        //-------------------------------------------------------------------------------
        parent::view_definition_after_gps();

        // list footer
        //-------------------------------------------------------------------------------
        $mform->addElement('header', 'listfooterhdr', get_string('viewlistfooter', 'dataform'));

        $mform->addElement('editor', 'eparam5_editor', '', $editorattr, $editoroptions['param5']);
        $this->add_tags_selector('eparam5_editor', 'general');
        $this->add_tags_selector('eparam5_editor', 'character');        
    }
    
}
