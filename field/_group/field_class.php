<?php

/**
 * This file is part of the Dataform module for Moodle - http://moodle.org/.
 *
 * @package mod-dataform
 * @subpackage field-_group
 * @copyright 2011 Itamar Tzadok
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * The Dataform has been developed as an enhanced counterpart
 * of Moodle's Database activity module (1.9.11+ (20110323)).
 * To the extent that Dataform code corresponds to Database code,
 * certain copyrights on Database module may obtain.
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

require_once($CFG->dirroot.'/mod/dataform/field/field_class.php');

class dataform_field__group extends dataform_field_base {

    public $type = '_group';

    /**
     * 
     */
    public function update_content($entry, array $values = null) {
        return true;
    }

    /**
     * 
     */
    public function get_search_sql($search) {
        return array(" ", array());
    }

    /**
     * 
     */
    public function parse_search($formdata, $i) {
        return '';
    }

    /**
     * 
     */
    public function get_sort_sql() {
        return '';
    }

    /**
     * returns an array of distinct content of the field
     */
    public function get_distinct_content($sortdir = 0) {
        return false;
    }

    /**
     * 
     */
    public function get_internalname() {
        return $this->field->internalname;
    }


}
