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

require_once("$CFG->dirroot/mod/dataform/field/field_patterns.php");

/**
 *
 */
class mod_dataform_field_time_patterns extends mod_dataform_field_patterns {

    /**
     * 
     */
    public function get_replacements($tags = null, $entry = null, array $options = null) {
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
                    case 'date': $format = get_string('strftimedate'); break; 
                    case 'minute': $format = '%M'; break; 
                    case 'hour': $format = '%H'; break; 
                    case 'day': $format = '%a'; break; 
                    case 'week': $format = '%V'; break; 
                    case 'month': $format = '%b'; break; 
                    case 'year': $format = '%G'; break;
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
       
        if ($entryid > 0){
            $content = $entry->{"c{$fieldid}_content"};
        } else {
            $content = 0;
        }

        // If date only don't add time to selector
        $time = (empty($options['date']) ? 'time_' : '');      
         $options = array();
        // Optional
        $options['optional'] = (!empty($options['required']) ? false : true);
        // Start year
        if ($field->start_year) {
            $options['startyear'] = $field->start_year;
        }
        // End year
        if ($field->start_year) {
            $options['stopyear'] = $field->stop_year;
        }
        $mform->addElement("date_{$time}selector", $fieldname, null, $options);
        $mform->setDefault($fieldname, $content);
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
        $mform->addGroup($elements, "searchelements$i", null, '<br />', false);
        $mform->setDefault("f_{$i}_{$fieldid}_from", $from);
        $mform->setDefault("f_{$i}_{$fieldid}_to", $to);
        foreach (array('year','month','day','hour','minute') as $fieldidentifier) {
            $mform->disabledIf("f_{$i}_{$fieldid}_to[$fieldidentifier]", "searchoperator$i", 'neq', 'BETWEEN');
        }
        $mform->disabledIf("searchelements$i", "searchoperator$i", 'eq', 'IN');
        $mform->disabledIf("searchelements$i", "searchoperator$i", 'eq', 'LIKE');
    }
    
    /**
     * Array of patterns this field supports 
     */
    protected function patterns() {
        $fieldname = $this->_field->name();

        $patterns = array();
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
