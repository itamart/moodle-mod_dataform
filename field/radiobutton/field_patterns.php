<?php

/**
 * This file is part of the Dataform module for Moodle - http://moodle.org/.
 *
 * @package mod-dataform
 * @package field-radiobutton
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

require_once("$CFG->dirroot/mod/dataform/field/select/field_patterns.php");

/**
 * 
 */
class mod_dataform_field_radiobutton_patterns extends mod_dataform_field_select_patterns {

    /**
     * 
     */
    protected function render(&$mform, $fieldname, $options, $selected) {
        $field = $this->_field;

        $elemgrp = array();
        foreach ($options as $key => $option) {
            $elemgrp[] = &$mform->createElement('radio', $fieldname, null, $option, $key);
        }
        $mform->addGroup($elemgrp, "{$fieldname}_grp", null, $field->separators[(int) $field->get('param3')]['chr'], false);
        if (!empty($selected)) {
            $mform->setDefault($fieldname, (int) $selected);
        }
    }

}
