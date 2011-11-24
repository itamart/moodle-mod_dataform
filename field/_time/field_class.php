<?php

/**
 * This file is part of the Dataform module for Moodle - http://moodle.org/.
 *
 * @package mod-dataform
 * @subpackage field-_time
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

require_once("$CFG->dirroot/mod/dataform/field/field_class.php");

class dataform_field__time extends dataform_field_base {

    public $type = '_time';

    /**
     * 
     */
    public function get_internalname() {
        return $this->field->internalname;
    }

    /**
     * 
     */
    public function update_content($entry, array $values = null) {
        return true;
    }

    /**
     * 
     */
    public function parse_search($formdata, $i) {
        $time = array();

        if (!empty($formdata->{'f_'. $i. '_'. $this->field->id. '_from'})) {
            $time[0] = $formdata->{'f_'. $i. '_'. $this->field->id. '_from'};
        }
            
        if (!empty($formdata->{'f_'. $i. '_'. $this->field->id. '_to'})) {
            $time[1] = $formdata->{'f_'. $i. '_'. $this->field->id. '_to'};
        }

        if (!empty($time)) {
            return $time;   
        } else {
            return false;
        }
    }

    /**
     * 
     */
    public function get_search_sql($search) {
        list($not, $operator, $value) = $search;

        if (is_array($value)){
            $from = $value[0];
            $to = $value[1];
        } else {
            $from = 0;
            $to = 0;
        }
        
        static $i=0;
        $i++;
        $namefrom = "df__time_$i_from";
        $nameto = "df__time_$i_to";
        $varcharcontent = $this->get_sql_compare_text();
        $params = array();
        
        if ($operator != 'BETWEEN') {
            if (!$operator or $operator == 'LIKE') {
                $operator = '=';
            }
            $params[$namefrom] = from;
            return array(" $not $varcharcontent $operator :$namefrom ", $params);
        } else {
            $params[$namefrom] = from;
            $params[$nameto] = to;
            return array(" ($not $varcharcontent >= :$namefrom AND $varcharcontent <= :$nameto) ", $params);
        }
    }

    /**
     *
     */
    public function get_sql_compare_text() {
        global $DB;
        
        return $DB->sql_compare_text("e.{$this->field->internalname}");    
    }

    /**
     * 
     */
    public function get_sort_sql() {
        return 'e.'. $this->field->internalname;
    }

    /**
     * returns an array of distinct content of the field
     */
    public function get_distinct_content($sortdir = 0) {
        global $DB;
        
        $sortdir = $sortdir ? 'DESC' : 'ASC';
        $contentfull = $this->get_sort_sql();
        
        $sql = "SELECT DISTINCT $contentfull
                    FROM {dataform_entries} e
                    WHERE $contentfull IS NOT NULL'.
                    ORDER BY $contentfull $sortdir";

        $distinctvalues = array();
        if ($options = $DB->get_records_sql($sql)) {
            foreach ($options as $data) {
                $value = $data->{$this->field->internalname};
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
    public function format_search_value($searchparams) {
        list($not, $operator, $value) = $searchparams;
        if (is_array($value)){
            $from = userdate($value[0]);
            $to = userdate($value[1]);
        } else {
            $from = userdate(time());
            $to = userdate(time());
        }
        if ($operator != 'BETWEEN') {
            return $not. ' '. $operator. ' '. $from;
        } else {
            return $not. ' '. $operator. ' '. $from. ' and '. $to;
        }
    }  

}
