<?php

/**
 * This file is part of the Dataform module for Moodle - http://moodle.org/.
 *
 * @package mod-dataform
 * @subpackage field-multiselect
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
class mod_dataform_field_multiselect_patterns extends mod_dataform_field_patterns {

    /**
     * 
     */
    public function get_replacements($tags = null, $entry = null, $edit = false, $editable = false) {
        $field = $this->_field;
        $fieldname = $field->name();

        // there is only one possible tag here so no check
        $replacements = array();

        if ($edit) {
            $replacements["[[$fieldname]]"] = array('', array(array($this ,'display_edit'), array($entry)));
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
        $options = $field->options_menu();
        $selected = array();

        if ($entryid > 0){
            if ($content = s($entry->{"c{$fieldid}_content"})) {
                $selected = explode('#', $content);
            }
        }
        
        // check for default values
        if (!$selected and $field->get('param2')) {
            $selected = $field->default_values();
        }

        $fieldname = "field_{$fieldid}_$entryid";
        $this->render($mform, "{$fieldname}_selected", $options, $selected);

        // add option
        if ($field->get('param4') or has_capability('mod/dataform:managetemplates', $field->df()->context)) {
            $mform->addElement('text', "{$fieldname}_newvalue", get_string('newvalue', 'dataform'));
            $mform->disabledIf("{$fieldname}_newvalue", "{$fieldname}_selected", 'neq', 0);
        }

    }

    /**
     *
     */
    public function display_browse($entry, $params = null) {
        $field = $this->_field;
        $fieldid = $field->id();

        if (isset($entry->{"c{$fieldid}_content"})) {
            $content = $entry->{"c{$fieldid}_content"};

            $options = $field->options_menu();
            $optionscount = count($options);

            $contents = explode('#', $content);

            $str = array();           
            foreach ($contents as $cont) {
                if (!$cont = (int) $cont or $cont > $optionscount) {
                    // somebody edited the field definition
                    continue;
                }
                $str[] = $options[$cont];
            }

            $str = implode($field->separators[(int) $field->get('param3')]['chr'], $str);;
        } else {
            $str = '';
        }
        
        return $str;
    }
    
    /**
     *
     */
    public function display_import($mform, $tags) {
        $fieldid = $this->_field->id();
        $tagname = $this->_field->name();
        $name = "f_{$fieldid}_$tagname";

        $grp = array();
        $grp[] = &$mform->createElement('text', "{$name}_name", null, array('size'=>'16'));                   
        $grp[] = &$mform->createElement('selectyesno', "{$name}_allownew");
        $mform->addGroup($grp, "grp$tagname", $tagname, ' '. get_string('newvalueallow', 'dataform'). ': ', false);
                            
        $mform->setType("{$name}_name", PARAM_NOTAGS);
        $mform->setDefault("{$name}_name", $tagname);
    }

    /**
     *
     */
    public function display_search(&$mform, $i = 0, $value = '') {
        $field = $this->_field;
        $fieldid = $field->id();
        
        if (is_array($value)){
            $selected     = $value['selected'];
            $allrequired = $value['allrequired'] ? 'checked = "checked"' : '';
        } else {
            $selected     = array();
            $allrequired = '';
        }

        $options = $field->options_menu();

        $fieldname = "f_{$i}_$fieldid";
        $this->render($mform, $fieldname, $options, $selected);
        
        $mform->addElement('checkbox', "{$fieldname}_allreq", null, ucfirst(get_string('requiredall', 'dataform')));
        $mform->setDefault("{$fieldname}_allreq", $allrequired);
    }

    /**
     *
     */
    protected function render(&$mform, $fieldname, $options, $selected) {
        $select = &$mform->addElement('select', $fieldname, null, $options);
        $select->setMultiple(true);
        $select->setSelected($selected);
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

