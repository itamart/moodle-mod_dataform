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
 * @package dataformfield
 * @subpackage radiobutton
 * @copyright 2011 Itamar Tzadok
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') or die();

require_once("$CFG->dirroot/mod/dataform/field/select/field_patterns.php");

/**
 * 
 */
class mod_dataform_field_radiobutton_patterns extends mod_dataform_field_select_patterns {

    /**
     * 
     */
    protected function render(&$mform, $fieldname, $options, $selected, $required = false) {
        $field = $this->_field;

        $elemgrp = array();
        foreach ($options as $key => $option) {
            $elemgrp[] = &$mform->createElement('radio', $fieldname, null, $option, $key);
        }
        $mform->addGroup($elemgrp, "{$fieldname}_grp", null, $field->separators[(int) $field->get('param3')]['chr'], false);
        if (!empty($selected)) {
            $mform->setDefault($fieldname, (int) $selected);
        }
        if ($required) {
            $mform->addRule("{$fieldname}_grp", null, 'required', null, 'client');
        }
        
    }

}
