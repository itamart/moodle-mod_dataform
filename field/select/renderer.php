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
 * @subpackage select
 * @copyright 2011 Itamar Tzadok
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') or die;

require_once("$CFG->dirroot/mod/dataform/field/renderer.php");

/**
 * 
 */
class dataformfield_select_renderer extends dataformfield_renderer {

    protected $_cats = array();

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
                } else if ($cleantag == "[[$fieldname:key]]") {
                    $replacements[$tag] = array('html', $this->display_category($entry, array('key' => true)));
                } else if ($cleantag == "[[$fieldname:cat]]") {
                    $replacements[$tag] = array('html', $this->display_category($entry));
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
        $menuoptions = $field->options_menu();
        $fieldname = "field_{$fieldid}_$entryid";
        $required = !empty($options['required']);
        $selected = !empty($entry->{"c{$fieldid}_content"}) ? (int) $entry->{"c{$fieldid}_content"} : 0;
        
        // check for default value
        if (!$selected and $defaultval = $field->get('param2')) {
            $selected = (int) array_search($defaultval, $menuoptions);
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
            $selected = (int) $entry->{"c{$fieldid}_content"};
            $options = $field->options_menu();

            if (!empty($params['options'])) {
                $str = array();           
                foreach ($options as $key => $option) {
                    $isselected = (int) ($key == $selected);
                    $str[] = "$isselected $option";
                }
                $str = implode(',', $str);
                return $str;
            }

            if (!empty($params['key'])) {
                if ($selected) {
                    return $selected;
                } else {
                    return '';
                }
            }

            if ($selected and $selected <= count($options)) {
                return $options[$selected];
            }
        }
        
        return '';
    }

    /**
     * $value is the selected index 
     */
    public function display_search(&$mform, $i = 0, $value = '') {
        $field = $this->_field;
        $fieldid = $field->id();

        $options = $field->options_menu();
        $selected = $value ? (int) $value : '';
        $fieldname = "f_{$i}_$fieldid";
        list($elem, $separator) = $this->render($mform, $fieldname, $options, $selected);
        $mform->disabledIf($fieldname, "searchoperator$i", 'eq', '');
        
        // Return group or element
        if (!is_array($elem)) {
            $elem = array($elem);
        }
        return array($elem, $separator);
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
    protected function display_category($entry, $params = null) {
        $field = $this->_field;
        $fieldid = $field->id();
        if (!isset($this->_cats[$fieldid])) {
            $this->_cats[$fieldid] = null;
        }

        $str = '';
        if (isset($entry->{"c{$fieldid}_content"})) {
            $selected = (int) $entry->{"c{$fieldid}_content"};
            
            $options = $field->options_menu();
            if ($selected and $selected <= count($options) and $selected != $this->_cats[$fieldid]) {
                $this->_cats[$fieldid] = $selected;
                $str = $options[$selected];
            }
        }
        
        return $str;
    }

    /**
     * 
     */
    protected function render(&$mform, $fieldname, $options, $selected, $required = false) {
        $select = &$mform->createElement('select', $fieldname, null, array('' => get_string('choosedots')) + $options);
        $select->setSelected($selected);
        return array($select, null);
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
        $patterns["[[$fieldname:cat]]"] = array(false);
        $patterns["[[$fieldname:key]]"] = array(false);

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
