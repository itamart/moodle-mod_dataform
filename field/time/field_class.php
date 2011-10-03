<?php

/**
 * This file is part of the Dataform module for Moodle - http://moodle.org/.
 *
 * @package mod-dataform
 * @subpackage field-time
 * @copyright 2011 Itamar Tzadok
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * The Dataform has been developed as an enhanced counterpart
 * of Moodle's Database activity module (1.9.11+ (20110323)).
 * To the extent that Dataform code corresponds to Database code,
 * certain copyrights on Database module may obtain, including:
 * @copyright 1999 Moodle Pty Ltd http://moodle.com
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

class dataform_field_time extends dataform_field_base {

    public $type = 'time';

    /**
     * 
     */
    public function patterns($tags = null, $entry = null, $edit = false, $editable = false) {
        $patterns = parent::patterns($tags, $entry, $edit, $editable);
        
        $fieldname = $this->field->name;
        $extrapatterns = array('%H' => "[[{$fieldname}:hour]]",
                                '%a' => "[[{$fieldname}:day]]",
                                '%V' => "[[{$fieldname}:week]]",
                                '%b' => "[[{$fieldname}:month]]",
                                '%G' => "[[{$fieldname}:year]]");
        
        // if no tags requested, return select menu
        if (is_null($tags)) {
            foreach ($extrapatterns as $pattern) {
                $patterns['fields']['fields'][$pattern] = $pattern;
            }

        } else {
            foreach ($tags as $tag) {
                if ($edit) {
                    if (in_array($tag, $extrapatterns)) {
                        $patterns[$tag] = array('', array(array($this,'display_edit'), array($entry)));
                    }
                } else {
                    if ($format = array_search($tag, $extrapatterns)) {
                        $patterns[$tag] = array('html', $this->display_browse($entry, array('format' => $format)));
                    }
                }
            }
        }

        return $patterns;
    }

    /**
     *
     */
    public function format_content(array $values = null) {
        if (!empty($values)) {
            $timestamp = 0;
            if (count($values) === 1) {
                // assuming timestamp is passed (e.g. in import)
                $timestamp = reset($values);
            } else {
                // assuming any of year, month, day, hour, minute is passed
                $year = $month = $day = $hour = $minute = 0;
                foreach ($values as $name => $value) {
                    $names = explode('_', $name);
                    if (!empty($names[3])) {          // the time unit
                        ${$names[3]} = $value;
                    }
                }
                $timestamp = make_timestamp($year, $month, $day, $hour, $minute, 0, 0, false);
            }
            return $timestamp;
        } else {
            return null;
        }
    }

    /**
     * 
     */
    public function display_search($mform, $i = 0, $value = '') {
        if (is_array($value)){
            $from = $value[0];
            $to = $value[1];
        } else {
            $from = 0;
            $to = 0;
        }
    
        $elements = array();
        $elements[] = &$mform->createElement('date_time_selector', 'f_'. $i. '_'. $this->field->id. '_from', get_string('from'));
        $elements[] = &$mform->createElement('date_time_selector', 'f_'. $i. '_'. $this->field->id. '_to', get_string('to'));
        $mform->addGroup($elements, "searchelements$i", null, '<br />', false);
        $mform->setDefault('f_'. $i. '_'. $this->field->id. '_from', $from);
        $mform->setDefault('f_'. $i. '_'. $this->field->id. '_to', $to);
        foreach (array('year','month','day','hour','minute') as $fieldidentifier) {
            $mform->disabledIf('f_'. $i. '_'. $this->field->id. '_to['. $fieldidentifier. ']', "searchoperator$i", 'neq', 'BETWEEN');
        }
        $mform->disabledIf("searchelements$i", "searchoperator$i", 'eq', 'IN');
        $mform->disabledIf("searchelements$i", "searchoperator$i", 'eq', 'LIKE');
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
        $namefrom = "df_{$this->field->id}_$i_from";
        $nameto = "df_{$this->field->id}_$i_to";
        $varcharcontent = $this->get_sql_compare_text();
        $params = array();
        
        if ($operator != 'BETWEEN') {
            if (!$operator) {
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
    public function get_sort_sql($fieldname) {
        global $DB;
        return $DB->sql_cast_char2int($fieldname, true);
    }

    /**
     * 
     */
    public function display_edit(&$mform, $entry = null) {
        $entryid = $entry->id;
        $fieldid = $this->field->id;
        
        if ($entryid > 0){
            $content = $entry->{"c{$fieldid}_content"};
        } else {
            $content = 0;
        }

        $fieldname = "field_{$fieldid}_{$entryid}";
        $mform->addElement('date_time_selector', $fieldname, null, array('optional' => true));
        $mform->setDefault($fieldname, $content);
    }
    
    /**
     * 
     */
    public function display_browse($entry, $params = null) {

        $fieldid = $this->field->id;

        if (isset($entry->{"c$fieldid". '_content'})) {
            $content = $entry->{"c$fieldid". '_content'};

            if (!empty($params['format'])) {
                $str = userdate($content, $params['format']);
            } else {
                $str = userdate($content);
            }
        } else {
            $str = '';
        }
        return $str;
    }    
}

