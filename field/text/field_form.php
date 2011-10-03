<?php

/**
 * This file is part of the Dataform module for Moodle - http://moodle.org/.
 *
 * @package mod-dataform
 * @subpackage field-text
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

require_once("$CFG->dirroot/mod/dataform/field/field_form.php");

class mod_dataform_field_text_form extends mod_dataform_field_form {

    /**
     *
     */
    function field_definition() {

        $mform =& $this->_form;

    //-------------------------------------------------------------------------------
        $mform->addElement('header', 'fieldattributeshdr', get_string('fieldattributes', 'dataform'));
        
        // auto link
        $mform->addElement('checkbox', 'param1', get_string('fieldallowautolink', 'dataform'));

        // field width
        $fieldwidthgrp=array();
        $fieldwidthgrp[] = &$mform->createElement('text', 'param2', null, array('size'=>'8'));
        $fieldwidthgrp[] = &$mform->createElement('select', 'param3', null, array('px' => 'px', 'em' => 'em', '%' => '%'));
        $mform->addGroup($fieldwidthgrp, 'fieldwidthgrp', get_string('fieldwidth', 'dataform'), array(' '), false);
        $mform->setType('param2', PARAM_INT);
        //$mform->addRule('param2', null, 'numeric', null, 'client');
        $mform->addGroupRule('fieldwidthgrp', array('param2' => array(array(null, 'numeric', null, 'client'))));        
        $mform->disabledIf('param3', 'param2', 'eq', '');
        ////$mform->addHelpButton('fieldwidthgrp', array("fieldwidth", get_string('fieldwidth', 'dataform'), 'dataform'));
        $mform->setDefault('param2', '');
        $mform->setDefault('param3', 'px');
    }

}
