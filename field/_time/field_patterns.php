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

require_once("$CFG->dirroot/mod/dataform/field/field_patterns.php");

/**
 *
 */
class mod_dataform_field__time_patterns extends mod_dataform_field_patterns {

    /**
     * 
     */
    public function get_replacements($tags = null, $entry = null, $edit = false, $editable = false) {
        $field = $this->_field;
        $fieldname = $field->get('internalname');

        // no edit mode
        $replacements = array();

        foreach ($tags as $tag) {
            // display nothing on new entries 
            if ($entry->id < 0) {
                $replacements[$tag] = '';

            } else {
                switch ($tag) {            
                    case "##{$fieldname}:hour##": $format = '%H'; break; 
                    case "##{$fieldname}:day##": $format = '%a'; break; 
                    case "##{$fieldname}:week##": $format = '%V'; break; 
                    case "##{$fieldname}:month##": $format = '%b'; break; 
                    case "##{$fieldname}:year##": $format = '%G'; break;
                    default: $format = null;
                }
                $replacements[$tag] = array('html', userdate($entry->{$fieldname}, $format));
            }
        }    

        return $replacements;
    }

    /**
     * 
     */
    public function display_search($mform, $i = 0, $value = '') {
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
        $fieldname = $this->_field->get('internalname');
        $cat = get_string('entryinfo', 'dataform');

        $patterns = array();
        $patterns["##$fieldname##"] = array(true, $cat);
        $patterns["##$fieldname:hour##"] = array(false, $cat);
        $patterns["##$fieldname:day##"] = array(false, $cat);
        $patterns["##$fieldname:week##"] = array(false, $cat);
        $patterns["##$fieldname:month##"] = array(false, $cat);
        $patterns["##$fieldname:year##"] = array(false, $cat);

        return $patterns; 
    }
}
