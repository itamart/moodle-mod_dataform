<?php

/**
 * This file is part of the Dataform module for Moodle - http://moodle.org/.
 *
 * @package mod-dataform
 * @subpackage view-blockext
 * @copyright 2011 Itamar Tzadok
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * The Dataform has been developed as an enhanced counterpart
 * of Moodle's Database activity module (1.9.11+ (20110323)).
 * To the extent that Dataform code corresponds to Database code,
 * certain copyrights on the Database module may obtain.
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

class mod_dataform_view_blockext_form extends mod_dataform_view_block_form {

    /**
     *
     */
    function view_definition_after_gps() {

        $view = $this->_customdata['view'];
        $editoroptions = $view->editors();

        $mform =& $this->_form;

        // list header
        //-------------------------------------------------------------------------------
        $mform->addElement('header', 'listheaderhdr', get_string('viewlistheader', 'dataform'));

        $mform->addElement('editor', 'eparam1_editor', '', array('rows' => 5), $editoroptions['param1']);
        $this->add_tags_selector('eparam1_editor', 'general');
        $this->add_tags_selector('eparam1_editor', 'character');        

        // repeated entry
        //-------------------------------------------------------------------------------
        parent::view_definition_after_gps();

        // list footer
        //-------------------------------------------------------------------------------
        $mform->addElement('header', 'listfooterhdr', get_string('viewlistfooter', 'dataform'));

        $mform->addElement('editor', 'eparam3_editor', '', array('rows' => 5), $editoroptions['param3']);
        $this->add_tags_selector('eparam3_editor', 'general');
        $this->add_tags_selector('eparam3_editor', 'character');        
    }
    
}
