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
 * @subpackage _time
 * @copyright 2011 Itamar Tzadok
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') or die;

require_once("$CFG->dirroot/mod/dataform/field/renderer.php");

/**
 *
 */
class dataformfield__time_renderer extends dataformfield_renderer {

    /**
     * 
     */
    protected function replacements(array $tags = null, $entry = null, array $options = null) {
        $field = $this->_field;
        $fieldname = $field->get('internalname');

        // no edit mode
        $replacements = array();

        foreach ($tags as $tag) {
            // display nothing on new entries 
            if ($entry->id < 0) {
                $replacements[$tag] = '';

            } else {
                $format = (strpos($tag, "{$fieldname}:") !== false ? str_replace("{$fieldname}:", '', trim($tag, '#')) : '');
                switch ($format) {            
                    case 'date': $format = get_string('strftimedate'); break; 
                    case 'minute': $format = '%M'; break; 
                    case 'hour': $format = '%H'; break; 
                    case 'day': $format = '%a'; break; 
                    case 'week': $format = '%V'; break; 
                    case 'month': $format = '%b'; break; 
                    case 'm': $format = '%m'; break; 
                    case 'year':
                    case 'Y': $format = '%Y'; break;
                }
                $replacements[$tag] = array('html', userdate($entry->{$fieldname}, $format));
            }
        }    

        return $replacements;
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
     * Array of patterns this field supports 
     */
    protected function patterns() {
        $fieldname = $this->_field->get('internalname');
        $cat = get_string('entryinfo', 'dataform');

        $patterns = array();
        $patterns["##$fieldname##"] = array(true);
        $patterns["##$fieldname:date##"] = array(true);
        // Minute (M)
        $patterns["##$fieldname:minute##"] = array(false);
        // Hour (H)
        $patterns["##$fieldname:hour##"] = array(false);
        // Day (a)
        $patterns["##$fieldname:day##"] = array(false);
        $patterns["##$fieldname:d##"] = array(false);
        // Week (V)
        $patterns["##$fieldname:week##"] = array(false);
        // Month (b)
        $patterns["##$fieldname:month##"] = array(false);
        $patterns["##$fieldname:m##"] = array(false);
        // Year (G)
        $patterns["##$fieldname:year##"] = array(false);
        $patterns["##$fieldname:Y##"] = array(false);

        return $patterns; 
    }
}
