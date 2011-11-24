<?php

/**
 * This file is part of the Dataform module for Moodle - http://moodle.org/.
 *
 * @package mod-dataform
 * @package field-checkbox
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

defined('MOODLE_INTERNAL') or die();

require_once("$CFG->dirroot/mod/dataform/field/multiselect/field_patterns.php");

/**
 * 
 */
class mod_dataform_field_checkbox_patterns extends mod_dataform_field_multiselect_patterns {

    /**
     *
     */
    protected function render(&$mform, $fieldname, $options, $selected) {
        $field = $this->_field;
        
        $elemgrp = array();
        foreach ($options as $i => $option) {
            $cb = &$mform->createElement('advcheckbox', $fieldname. '_'. $i, null, $option, array('group' => 1), array(0, $i));
            $elemgrp[] = $cb;
            if (in_array($i, $selected)) {
                $cb->setChecked(true);
            }
        }
        $mform->addGroup($elemgrp, "{$fieldname}_grp",null, $field->separators[(int) $field->get('param2')]['chr'], false);
        // add checkbox controller
    }



}
