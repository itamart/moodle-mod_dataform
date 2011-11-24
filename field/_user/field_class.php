<?php

/**
 * This file is part of the Dataform module for Moodle - http://moodle.org/.
 *
 * @package mod-dataform
 * @subpackage field-_user
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

class dataform_field__user extends dataform_field_base {

    public $type = '_user';

    /**
     *
     */
    public function get_sql_compare_text() {
        global $DB;
        // the sort sql here returns the field's sql name       
        return $DB->sql_compare_text($this->get_sort_sql());    
    }

    /**
     * 
     */
    public function get_sort_sql() {
        if ($this->field->internalname != 'picture') {
            if ($this->field->internalname == 'name') {
                $internalname = 'id';
            } else {
                $internalname = $this->field->internalname;
            }
            return 'u.'. $internalname;
        } else {
            return '';
        }
    }

    /**
     * returns an array of distinct content of the field
     */
    public function get_distinct_content($sortdir = 0) {
        global $CFG, $DB;
        
        $sortdir = $sortdir ? 'DESC' : 'ASC';
        $contentfull = $this->get_sort_sql();
        $sql = "SELECT DISTINCT $contentfull 
                         FROM {user} u 
                         WHERE $contentfull IS NOT NULL 
                         ORDER BY $contentfull $sortdir";

        $distinctvalues = array();
        if ($options = $DB->get_records_sql($sql)) {
            if ($this->field->internalname == 'name') {
                $internalname = 'id';
            } else {
                $internalname = $this->field->internalname;
            }
            foreach ($options as $data) {
                $value = $data->{$internalname};
                if ($value === '') {
                    continue;
                }
                $distinctvalues[] = $value;
            }
        }
        return $distinctvalues;
    }

    /**
     * 
     */
    public function update_content($entry, array $values = null) {
        return true;
    }
}
