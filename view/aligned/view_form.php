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
 * @subpackage aligned
 * @copyright 2012 Itamar Tzadok 
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once("$CFG->dirroot/mod/dataform/view/view_form.php");

class dataformview_aligned_form extends dataformview_base_form {

    /**
     *
     */
    function view_definition_after_gps() {

        $view = $this->_view;
        $mform = &$this->_form;

        // repeated entry (param2)
        //-------------------------------------------------------------------------------
        $mform->addElement('header', 'entrytemplatehdr', get_string('entrytemplate', 'dataform'));

        $mform->addElement('textarea', 'param2', '', array('cols' => 40, 'rows' => 12));
        $this->add_tags_selector('param2', 'view');
        $this->add_tags_selector('param2', 'field');
        $this->add_tags_selector('param2', 'character');        
    }


}
