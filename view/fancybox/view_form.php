<?php

/**
 * This file is part of the Dataform module for Moodle - http://moodle.org/
 *
 * @package mod-dataform
 * @subpackage view-fancybox
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

class mod_dataform_view_fancybox_form extends mod_dataform_view_block_form {

    /**
     *
     */
    function view_definition_before_gps() {

        $mform =& $this->_form;

        // Slides paging settings
        //-------------------------------------------------------------------------------
        $mform->addElement('header', 'viewhdr', get_string('settings', 'dataform'));
        
        // display the page items 
        $options = array(
            dataform_view_fancybox::SHOW_ALL => get_string('all'),
            dataform_view_fancybox::SHOW_FIRST => get_string('first', 'dataform'),
            dataform_view_fancybox::SHOW_RANDOM => get_string('randomone', 'dataform'),
            dataform_view_fancybox::SHOW_NONE => get_string('none'),
        );
        $mform->addElement('select', 'param3', get_string('showitemsonpage', 'dataformview_fancybox'), $options);
        $mform->setDefault('param3', dataform_view_fancybox::SHOW_ALL);

        $range = range(1, 50);
        $options = array_combine($range, $range);
        $mform->addElement('select', 'param1', get_string('columnsperpage', 'dataformview_fancybox'), $options);
        $mform->setDefault('param1', 3);

        // repeated entry
        //-------------------------------------------------------------------------------
        parent::view_definition_before_gps();
    }

}
