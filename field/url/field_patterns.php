<?php

/**
 * This file is part of the Dataform module for Moodle - http://moodle.org/.
 *
 * @package mod-dataform
 * @package field-text
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
class mod_dataform_field_text_patterns extends mod_dataform_field_patterns {

    /**
     * 
     */
    public function get_replacements($tags = null, $entry = null, $edit = false, $editable = false) {
        $field = $this->_field;
        $fieldname = $field->name();

        // there is only one possible tag here so no check
        $replacements = array();

        if ($edit) {
            $replacements["[[$fieldname]]"] = array('', array(array($this,'display_edit'), array($entry)));
        } else {
            $replacements["[[$fieldname]]"] = array('html', $this->display_browse($entry));
        }

        return $replacements;
    }

    /**
     *
     */
    public function display_edit(&$mform, $entry) {
        $field = $this->_field;
        $fieldid = $field->id();
        $entryid = $entry->id;
        
        if ($entryid > 0){
            $content = $entry->{"c{$fieldid}_content"};
        } else {
            $content = '';
        }

        $fieldattr = array();

        if (!empty($field->get('param2'))) {
            $fieldattr['style'] = 'width:'. s($field->get('param2')). s($field->get('param3')). ';';
        }

        if (!empty($field->get('param4'))) {
            $fieldattr['class'] = s($field->get('param4'));
        }

        $fieldname = "field_{$fieldid}_{$entryid}";
        $mform->addElement('text', $fieldname, null, $fieldattr);
        $mform->setDefault($fieldname, s($content));
        //$mform->addRule($fieldname, null, 'required', null, 'client');
    }

    /**
     *
     */
    protected function display_browse($entry, $params = null) {
        $field = $this->_field;
        $fieldid = $field->id();

        if (isset($entry->{"c{$fieldid}_content"})) {
            $content = $entry->{"c{$fieldid}_content"};

            $options = new object();
            $options->para = false;

            $format = FORMAT_PLAIN;
            if ($field->get('param1') == '1') {  // We are autolinking this field, so disable linking within us
                $content = '<span class="nolink">'. $content .'</span>';
                $format = FORMAT_HTML;
                $options->filter=false;
            }

            $str = format_text($content, $format, $options);
        } else {
            $str = '';
        }
        
        return $str;
    }

    /**
     * Array of patterns this field supports 
     */
    protected function patterns() {
        $fieldname = $this->_field->name();

        $patterns = array();
        $patterns["[[$fieldname]]"] = array(true);

        return $patterns; 
    }
}
