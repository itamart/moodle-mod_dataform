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
 * @subpackage time
 * @copyright 2011 Itamar Tzadok
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once("$CFG->dirroot/mod/dataform/field/field_class.php");

class dataformfield_time extends dataformfield_base {

    public $type = 'time';
    
    public $date_only;
    public $masked;
    public $start_year;
    public $stop_year;
    public $display_format;

    public function __construct($df = 0, $field = 0) {       
        parent::__construct($df, $field);
        $this->date_only = $this->field->param1;
        $this->masked = $this->field->param5;
        $this->start_year = $this->field->param2;
        $this->stop_year = $this->field->param3;
        $this->display_format = $this->field->param4;
    }

    /**
     *
     */
    protected function content_names() {
        return array('', 'year', 'month', 'day', 'hour', 'minute', 'enabled');
    }
    
    /**
     *
     */
    protected function format_content($entry, array $values = null) { 
        $fieldid = $this->field->id;
        $oldcontents = array();
        $contents = array();
        // old contents
        if (isset($entry->{"c{$fieldid}_content"})) {
            $oldcontents[] = $entry->{"c{$fieldid}_content"};
        }

        // new contents
        $timestamp = null;
        if (!empty($values)) {
            if (count($values) === 1) {
                $values = reset($values);
            }
            
            if (!is_array($values)) {
                // assuming timestamp is passed (e.g. in import)
                $timestamp = $values;

            } else {
                // assuming any of year, month, day, hour, minute is passed
                $enabled = $year = $month = $day = $hour = $minute = 0;
                foreach ($values as $name => $val) {                
                    if (!empty($name)) {          // the time unit
                        ${$name} = $val;
                    }
                }
                if ($enabled) {
                    if ($year or $month or $day or $hour or $minute) {
                        $timestamp = make_timestamp($year, $month, $day, $hour, $minute, 0);
                    }
                }
            }
        }
        $contents[] = $timestamp;
        return array($contents, $oldcontents);        
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
        $namefrom = "df_{$this->field->id}_{$i}_from";
        $nameto = "df_{$this->field->id}_{$i}_to";
        $varcharcontent = $this->get_sql_compare_text();
        $params = array();
        
        if ($operator != 'BETWEEN') {
            if (!$operator) {
                $operator = '=';
            }
            $params[$namefrom] = $from; 
            return array(" $not $varcharcontent $operator :$namefrom ", $params, true);
        } else {
            $params[$namefrom] = $from;
            $params[$nameto] = $to;
            return array(" ($not $varcharcontent >= :$namefrom AND $varcharcontent < :$nameto) ", $params, true);
        }
    }

    /**
     * 
     */
    public function prepare_import_content(&$data, $importsettings, $csvrecord = null, $entryid = null) {
        // import only from csv
        if ($csvrecord) {
            $fieldid = $this->field->id;
            $fieldname = $this->name();
            $csvname = $importsettings[$fieldname]['name'];
            $timestr = !empty($csvrecord[$csvname]) ? $csvrecord[$csvname] : null;
            
            if ($timestr) {
                // It's a timestamp
                if (((string) (int) $timestr === $timestr) 
                        && ($timestr <= PHP_INT_MAX)
                        && ($timestr >= ~PHP_INT_MAX)) {

                    $data->{"field_{$fieldid}_{$entryid}"} = $timestr;
                    
                // It's a valid time string
                } else if ($timestr = strtotime($timestr)) {
                    $data->{"field_{$fieldid}_{$entryid}"} = $timestr;
                }
            }
        }
    
        return true;
    }

    /**
     * 
     */
    public function get_sql_compare_text($column = 'content') {
        global $DB;
        return $DB->sql_cast_char2int("c{$this->field->id}.$column", true);
    }

}

