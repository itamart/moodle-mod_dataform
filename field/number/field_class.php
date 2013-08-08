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
 * @subpackage number
 * @copyright 2011 Itamar Tzadok
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once("$CFG->dirroot/mod/dataform/field/text/field_class.php");

class dataformfield_number extends dataformfield_text {
    public $type = 'number';

    /**
     * TODO This casting seems to omit decimals from the value stored in DB
     * ie it returns 11.95 as 12 
     */
    //protected function get_sql_compare_text($column = 'content') {
    //    global $DB;    
    //    return $DB->sql_cast_char2real("c{$this->field->id}.$column", true);
    //}

}

