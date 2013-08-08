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
 * @package view-editon
 * @copyright 2012 Itamar Tzadok
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(dirname(__FILE__). "/../view_entries_form.php");

/**
 *
 */
class dataformview_editon_entries_form extends dataformview_entries_form {

    function definition() {

        $view = $this->_customdata['view'];
        $update = $this->_customdata['update'];
        $mform =& $this->_form;

        $mform->addElement('hidden', 'update', $update);

        // entries
        //-------------------------------------------------------------------------------
        $view->definition_to_form($mform);

        // buttons
        //-------------------------------------------------------------------------------
        $this->add_action_buttons($view->show_cancel(), $view->get_submit_label());
    }
    
    /**
     *
     */
    protected function add_action_save_continue() {
        $view = $this->_customdata['view'];
        return $view->show_save_continue();
    }

    
}
