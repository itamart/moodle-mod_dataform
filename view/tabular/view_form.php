<?php

/**
 * This file is part of the Dataform module for Moodle - http://moodle.org/. 
 *
 * @package mod-dataform
 * @subpackage view-tabular
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

require_once("$CFG->dirroot/mod/dataform/view/view_form.php");

class mod_dataform_view_tabular_form extends mod_dataform_view_base_form {

    /**
     *
     */
    function view_definition_after_gps() {

        $view = $this->_customdata['view'];
        $editoroptions = $view->editors();

        $mform =& $this->_form;

        // content
        //-------------------------------------------------------------------------------
        $mform->addElement('header', 'entriessectionhdr', get_string('viewlistbody', 'dataform'));

        $mform->addElement('selectyesno', 'param1', get_string('headerrow', 'dataformview_tabular'));
        $mform->setDefault('param1', 1);
        
        $mform->addElement('editor', 'eparam2_editor', get_string('table', 'dataformview_tabular'), null, $editoroptions['param2']);
        $mform->setType('eparam2_editor', PARAM_RAW);
        $this->add_tags_selector('eparam2_editor', 'general');
        $this->add_tags_selector('eparam2_editor', 'field');        

    }

}
