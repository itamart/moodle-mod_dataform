<?php

/**
 * This file is part of the Dataform module for Moodle - http://moodle.org/.
 *
 * @package mod-dataform
 * @subpackage field-select
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
class mod_dataform_field_select_patterns extends mod_dataform_field_patterns {

    protected $_cats = array();

    /**
     * 
     */
    public function get_replacements($tags = null, $entry = null, $edit = false, $editable = false) {
        $field = $this->_field;
        $fieldname = $field->name();

        $replacements = array();

        foreach ($tags as $tag) {
            if ($edit) {
                $replacements[$tag] = array('', array(array($this ,'display_edit'), array($entry)));
            } else {
                switch ($tag) {
                    case "[[$fieldname]]":
                        $replacements[$tag] = array('html', $this->display_browse($entry));
                        break;
                    case "[[$fieldname:cat]]":
                        $replacements[$tag] = array('html', $this->display_category($entry));
                        break;
                }
            }
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
        $selected = 0;
        
        if ($entryid > 0){
            $selected = (int) $entry->{"c{$fieldid}_content"};
        }
        
        // check for default value
        if (!$selected and $defaultval = $field->get('param2')) {
            $selected = (int) array_search($defaultval, $options);
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
        $str = '';

        if (isset($entry->{"c{$fieldid}_content"})) {
            $selected = (int) $entry->{"c{$fieldid}_content"};

            $options = $field->options_menu();
            if ($selected and $selected <= count($options)) {
                $str = $options[$selected];
            }
        }
        
        return $str;
    }

    /**
     * 
     */
    public function display_search(&$mform, $i = 0, $value = '') {
        $field = $this->_field;
        $fieldid = $field->id();

        $options = $field->options_menu();
        $selected = (int) array_search($value, $options);
        $fieldname = "f_{$i}_$fieldid";
        $this->render($mform, $fieldname, $options, $selected);
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
    protected function render(&$mform, $fieldname, $options, $selected) {
        $select = &$mform->addElement('select', $fieldname, null, array(0 => get_string('choosedots')) + $options);
        $select->setSelected($selected);
    }

    /**
     * Array of patterns this field supports 
     */
    protected function patterns() {
        $fieldname = $this->_field->name();

        $patterns = array();
        $patterns["[[$fieldname]]"] = array(true);
        $patterns["[[$fieldname:cat]]"] = array(false);

        return $patterns; 
    }
}
