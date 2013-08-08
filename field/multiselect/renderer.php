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
 * @subpackage multiselect
 * @copyright 2011 Itamar Tzadok
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') or die();

require_once("$CFG->dirroot/mod/dataform/field/renderer.php");

/**
 *
 */
class dataformfield_multiselect_renderer extends dataformfield_renderer {

    /**
     * 
     */
    protected function replacements(array $tags = null, $entry = null, array $options = null) {
        $field = $this->_field;
        $fieldname = $field->name();
        $edit = !empty($options['edit']) ? $options['edit'] : false;

        $replacements = array_fill_keys($tags, '');
        // rules support
        $tags = $this->add_clean_pattern_keys($tags);        

        foreach ($tags as $tag => $cleantag) {
            if ($edit) {
                $params = array('required' => $this->is_required($tag));
                if ($cleantag == "[[$fieldname:addnew]]") {
                    $params['addnew'] = true;
                }
                $replacements[$tag] = array('', array(array($this ,'display_edit'), array($entry, $params)));
                break;
            } else {
                if ($cleantag == "[[$fieldname:options]]") {
                    $replacements[$tag] = array('html', $this->display_browse($entry, array('options' => true)));
                } else {
                    $replacements[$tag] = array('html', $this->display_browse($entry));
                }
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
        $fieldname = "field_{$fieldid}_$entryid";
        $menuoptions = $field->options_menu();
        $required = !empty($options['required']);

        $content = !empty($entry->{"c{$fieldid}_content"}) ? $entry->{"c{$fieldid}_content"} : null;
        
        if ($entryid > 0 and $content){
            $selected = explode('#', $content);
        } else {
            $selected = array();
        }
        
        // check for default values
        if (!$selected and $field->get('param2')) {
            $selected = $field->default_values();
        }

        list($elem, $separators) = $this->render($mform, "{$fieldname}_selected", $menuoptions, $selected, $required);
        // Add group or element
        if (is_array($elem)) {
            $mform->addGroup($elem, "{$fieldname}_grp",null, $separators, false);
        } else {
            $mform->addElement($elem);
        }
        
        if ($required) {
            $this->set_required($mform, $fieldname, $selected);
        }

        // Input field for adding a new option
        if (!empty($options['addnew'])) {
            if ($field->get('param4') or has_capability('mod/dataform:managetemplates', $field->df()->context)) {
                $mform->addElement('text', "{$fieldname}_newvalue", get_string('newvalue', 'dataform'));
                $mform->setType("{$fieldname}_newvalue", PARAM_TEXT);
                $mform->disabledIf("{$fieldname}_newvalue", "{$fieldname}_selected", 'neq', 0);
            }
            return;
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
            $showalloptions = !empty($params['options']);

            $contents = explode('#', $content);

            $str = array();           
            foreach ($options as $key => $option) {
                $selected = (int) in_array($key, $contents);
                if ($showalloptions) {
                    $str[] = "$selected $option";
                } else if ($selected) {
                    $str[] = $option;
                }
            }
            $separator = $showalloptions ? ',' : $field->separators[(int) $field->get('param3')]['chr'];
            $str = implode($separator, $str);
        } else {
            $str = '';
        }
        
        return $str;
    }
    
    /**
     *
     */
    public function display_import(&$mform, $tags) {
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
        list($elem, $separators) = $this->render($mform, $fieldname, $options, $selected);
        $mform->disabledIf($fieldname, "searchoperator$i", 'eq', '');
        
        $allreq = &$mform->createElement('checkbox', "{$fieldname}_allreq", null, ucfirst(get_string('requiredall', 'dataform')));
        $mform->setDefault("{$fieldname}_allreq", $allrequired);
        $mform->disabledIf("{$fieldname}_allreq", "searchoperator$i", 'eq', '');
        
        return array(array_merge($elem, $allreq), $separators);
    }

    /**
     *
     */
    protected function render(&$mform, $fieldname, $options, $selected, $required = false) {
        $select = &$mform->createElement('select', $fieldname, null, $options);
        $select->setMultiple(true);
        $select->setSelected($selected);
        return $select;
    }

    /**
     *
     */
    protected function set_required(&$mform, $fieldname, $selected) {
        $mform->addRule($fieldname, null, 'required', null, 'client');
    }


    /**
     * Array of patterns this field supports 
     */
    protected function patterns() {
        $fieldname = $this->_field->name();

        $patterns = parent::patterns();
        $patterns["[[$fieldname]]"] = array(true);
        $patterns["[[$fieldname:addnew]]"] = array(false);
        $patterns["[[$fieldname:options]]"] = array(false);

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

