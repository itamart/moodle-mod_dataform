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
defined('MOODLE_INTERNAL') or die();

require_once("$CFG->dirroot/mod/dataform/field/renderer.php");

/**
 *
 */
class dataformfield_time_renderer extends dataformfield_renderer {

    /**
     * 
     */
    protected function replacements(array $tags = null, $entry = null, array $options = null) {
        $field = $this->_field;
        $fieldname = $field->name();
        $edit = !empty($options['edit']) ? $options['edit'] : false;

        // rules support
        $tags = $this->add_clean_pattern_keys($tags);        

        $replacements = array_fill_keys($tags, '');
        
        foreach ($tags as $tag => $cleantag) {
            if ($edit) {
                $required = $this->is_required($tag);
                // Determine whether date only selector
                $date = (($cleantag == "[[$fieldname:date]]") or $field->date_only);
                $options = array('required' => $required, 'date' => $date);
                $replacements[$tag] = array('', array(array($this,'display_edit'), array($entry, $options)));
                break;
            } else {
                // Determine display format
                $format = (strpos($tag, "$fieldname:") !== false ? str_replace("$fieldname:", '', trim($tag, '[]')) : $field->display_format);
                // For specialized tags convert format to the userdate format string
                switch ($format) {            
                    case 'date': $format = get_string('strftimedate', 'langconfig'); break; 
                    case 'minute': $format = '%M'; break; 
                    case 'hour': $format = '%H'; break; 
                    case 'day': $format = '%a'; break; 
                    case 'week': $format = '%V'; break; 
                    case 'month': $format = '%b'; break; 
                    case 'm': $format = '%m'; break; 
                    case 'year':
                    case 'Y': $format = '%Y'; break;
                    default:
                        if (!$format and $field->date_only) {
                            $format = get_string('strftimedate', 'langconfig');
                        }
                }
                $replacements[$tag] = array('html', $this->display_browse($entry, array('format' => $format)));
            }
        }    

        return $replacements;
    }

    /**
     * 
     */
    public function display_edit(&$mform, $entry, array $options = null) {
        $field = $this->_field;
        $fieldid = $field->id();
        $entryid = $entry->id;
        $fieldname = "field_{$fieldid}_{$entryid}";
       
        $content = 0;
        if ($entryid > 0 and !empty($entry->{"c{$fieldid}_content"})){
            $content = $entry->{"c{$fieldid}_content"};
        }

        $includetime = empty($options['date']) ? true : false;

        if ($field->masked) {
            $this->render_masked_selector($mform, $entry, $content, $includetime);
        } else {
            $this->render_standard_selector($mform, $entry, $content, $includetime);
        }
    }
    
    /**
     *
     */
    public function display_browse($entry, $params = null) {
        $field = $this->_field;
        $fieldid = $field->id();

        $strtime = '';
        if (isset($entry->{"c{$fieldid}_content"})) {
            if ($content = $entry->{"c{$fieldid}_content"}) {
                $format = !empty($params['format']) ? $params['format'] : '';
                $strtime = userdate($content, $format);
            }
        }
        
        return $strtime;
    }

    /**
     * 
     */
    public function display_search(&$mform, $i = 0, $value = '') {
        $fieldid = $this->_field->id();

        if (is_array($value)){
            $from = $value[0];
            $to = $value[1];
        } else {
            $from = 0;
            $to = 0;
        }
    
        $elements = array();
        $elements[] = &$mform->createElement('date_time_selector', "f_{$i}_{$fieldid}_from", get_string('from'));
        $elements[] = &$mform->createElement('date_time_selector', "f_{$i}_{$fieldid}_to", get_string('to'));
        $mform->setDefault("f_{$i}_{$fieldid}_from", $from);
        $mform->setDefault("f_{$i}_{$fieldid}_to", $to);
        foreach (array('year','month','day','hour','minute') as $fieldidentifier) {
            $mform->disabledIf("f_{$i}_{$fieldid}_to[$fieldidentifier]", "searchoperator$i", 'neq', 'BETWEEN');
        }
        $mform->disabledIf("f_{$i}_{$fieldid}_from", "searchoperator$i", 'eq', '');
        $mform->disabledIf("f_{$i}_{$fieldid}_from", "searchoperator$i", 'eq', 'IN');
        $mform->disabledIf("f_{$i}_{$fieldid}_from", "searchoperator$i", 'eq', 'LIKE');
        $mform->disabledIf("f_{$i}_{$fieldid}_to", "searchoperator$i", 'eq', '');
        $mform->disabledIf("f_{$i}_{$fieldid}_to", "searchoperator$i", 'eq', 'IN');
        $mform->disabledIf("f_{$i}_{$fieldid}_to", "searchoperator$i", 'eq', 'LIKE');
        
        $separators = array('<br />'. get_string('from'), '<br />'. get_string('to'));
        return array($elements, $separators);
    }
    
    /**
     * 
     */
    protected function render_standard_selector(&$mform, $entry, $content, $includetime = true) {
        $field = $this->_field;
        $fieldid = $field->id();
        $entryid = $entry->id;
        $fieldname = "field_{$fieldid}_{$entryid}";
       
        // If date only don't add time to selector
        $time = $includetime ? 'time_' : '';      
        $options = array();
        // Optional
        $options['optional'] = (!empty($options['required']) ? false : true);
        // Start year
        if ($field->start_year) {
            $options['startyear'] = $field->start_year;
        }
        // End year
        if ($field->stop_year) {
            $options['stopyear'] = $field->stop_year;
        }
        $mform->addElement("date_{$time}selector", $fieldname, null, $options);
        $mform->setDefault($fieldname, $content);      
    }
    
    /**
     * 
     */
    protected function render_masked_selector(&$mform, $entry, $content, $includetime = true) {
        $field = $this->_field;
        $entryid = $entry->id;
        $fieldid = $field->id();
        $fieldname = "field_{$fieldid}_{$entryid}";
        
        // TODO some defaults that need to be set in the field settings
        $step = 5;
        $startyear = $field->start_year ? $field->start_year : 1970;
        $stopyear = $field->stop_year ? $field->stop_year : 2020;
        $maskday = get_string('day', 'dataformfield_time');
        $maskmonth = get_string('month', 'dataformfield_time');
        $maskyear = get_string('year', 'dataformfield_time');
       
        $days = array();
        for ($i=1; $i<=31; $i++) {
            $days[$i] = $i;
        }
        $months = array();
        for ($i=1; $i<=12; $i++) {
            $months[$i] = userdate(mktime(0, 0, 0, $i, 10), "%B");
        }
        $years = array();
        for ($i = $startyear; $i <= $stopyear; $i++) {
            $years[$i] = $i;
        }

        $grp = array();
        $grp[] = &$mform->createElement('select', "{$fieldname}[day]", null, array(0 => $maskday) + $days);                   
        $grp[] = &$mform->createElement('select', "{$fieldname}[month]", null, array(0 => $maskmonth) + $months);                   
        $grp[] = &$mform->createElement('select', "{$fieldname}[year]", null, array(0 => $maskyear) + $years);                   

        // If time add hours and minutes
        if ($includetime) {
            $maskhour = get_string('hour', 'dataformfield_time');
            $maskminute = get_string('minute', 'dataformfield_time');

            $hours = array();
            for ($i=0; $i<=23; $i++) {
                $hours[$i] = sprintf("%02d",$i);
            }
            $minutes = array();
            for ($i=0; $i<60; $i+=$step) {
                $minutes[$i] = sprintf("%02d",$i);
            }
            
            $grp[] = &$mform->createElement('select', "{$fieldname}[hour]", null, array(0 => $maskhour) + $hours);                   
            $grp[] = &$mform->createElement('select', "{$fieldname}[minute]", null, array(0 => $maskminute) + $minutes);                   
        }

        $mform->addGroup($grp, "grp$fieldname", null, '', false);
        // Set field values
        if ($content) {
            list($day, $month, $year, $hour, $minute) = explode(':', date('d:n:Y:G:i', $content));
            $mform->setDefault("{$fieldname}[day]", (int) $day);
            $mform->setDefault("{$fieldname}[month]", (int) $month);
            $mform->setDefault("{$fieldname}[year]", (int) $year);
            // Defaults for time
            if ($includetime) {
                $mform->setDefault("{$fieldname}[hour]", (int) $hour);
                $mform->setDefault("{$fieldname}[minute]", (int) $minute);
            }
        }
        // Add enabled fake field
        $mform->addElement('hidden', "{$fieldname}[enabled]", 1);
        $mform->setType("{$fieldname}[enabled]", PARAM_INT);
    }
    
    /**
     * Array of patterns this field supports 
     */
    protected function patterns() {
        $fieldname = $this->_field->name();

        $patterns = parent::patterns();
        $patterns["[[$fieldname]]"] = array(true);
        $patterns["[[$fieldname:date]]"] = array(true);
        // Minute (M)
        $patterns["[[$fieldname:minute]]"] = array(false);
        // Hour (H)
        $patterns["[[$fieldname:hour]]"] = array(false);
        // Day (a)
        $patterns["[[$fieldname:day]]"] = array(false);
        $patterns["[[$fieldname:d]]"] = array(false);
        // Week (V)
        $patterns["[[$fieldname:week]]"] = array(false);
        // Month (b)
        $patterns["[[$fieldname:month]]"] = array(false);
        $patterns["[[$fieldname:m]]"] = array(false);
        // Year (G)
        $patterns["[[$fieldname:year]]"] = array(false);
        $patterns["[[$fieldname:Y]]"] = array(false);

        return $patterns; 
    }
    
    /**
     * Array of patterns this field supports 
     */
    protected function supports_rules() {
        return array(
            self::RULE_REQUIRED
        );
    }
}
