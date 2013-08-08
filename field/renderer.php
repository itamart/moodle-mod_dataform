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
 * @copyright 2011 Itamar Tzadok
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') or die;

/**
 * Base class for field patterns
 */
abstract class dataformfield_renderer {

    const PATTERN_SHOW_IN_MENU = 0;
    const PATTERN_CATEGORY = 1;

    const RULE_REQUIRED = '*';
    const RULE_HIDDEN = '^';
    const RULE_NOEDIT = '!';

    protected $_field = null;

    /**
     * Constructor
     */
    public function __construct(&$field) {
        $this->_field = $field;
    }

    /**
     * Search and collate field patterns that occur in given text
     *
     * @param string Text that may contain field patterns
     * @return array Field patterns found in the text
     */
    public function search($text) {
        $fieldid = $this->_field->field->id;
        $fieldname = $this->_field->name();

        $found = array();
        
        // Capture label patterns
        if (strpos($text, "[[$fieldname@]]") !== false and !empty($this->_field->field->label)) {
            $found[] = "[[$fieldname@]]";
            
            $text = str_replace("[[$fieldname@]]", $this->_field->field->label, $text);
        }
        
        // Search and collate field patterns
        $patterns = array_keys($this->patterns());
        $wrapopen = $fieldid > 0 ? '\[\[' : '##';
        $wrapclose =  $fieldid > 0 ? '\]\]' : '##';
        
        $labelpattern = false;
        if ($rules = implode('', $this->supports_rules())) {
            // Patterns may have rule prefix
            foreach ($patterns as $pattern) {
                $pattern = trim($pattern, '[]#');
                $pattern = $wrapopen. "[$rules]*$pattern". $wrapclose;
                preg_match_all("/$pattern/", $text, $matches);
                if (!empty($matches[0])) {
                    $found = array_merge($found, $matches[0]);
                }
            }
        } else {
            foreach ($patterns as $pattern) {
                if (strpos($text, $pattern) !== false) {
                    $found[] = $pattern;
                }
            }
        }

        return $found;
    }

    /**
     * @return string characters of supported rulesCleans a pattern from auxiliary indicators (e.g. * for required)
     */
    protected function supports_rules() {
        return array();
    }

    /**
     * Cleans a pattern from auxiliary indicators (e.g. * for required)
     */
    protected function add_clean_pattern_keys(array $patterns) {
        $keypatterns = array();
        foreach ($patterns as $pattern) {
            $keypatterns[$pattern] = str_replace($this->supports_rules(), '', $pattern);
        }
        return $keypatterns;
    }

    /**
     *
     */
    public function is_required($pattern) {
        // TODO must be after opening brackets and before field name
        return strpos($pattern, self::RULE_REQUIRED) !== false;
    }

    /**
     *
     */
    public function is_hidden($pattern) {
        // TODO must be after opening brackets and before field name
        return strpos($pattern, self::RULE_HIDDEN) !== false;
    }

    /**
     *
     */
    public function is_noedit($pattern) {
        // TODO must be after opening brackets and before field name
        return strpos($pattern, self::RULE_NOEDIT) !== false;
    }

    /**
     *
     */
    public function pluginfile_patterns() {
        return array();
    }

    /**
     *
     */
    public function display_search(&$mform, $i = 0, $value = '') {
        $fieldid = $this->_field->id();
        $fieldname = "f_{$i}_$fieldid";
        
        $arr = array();
        $arr[] = &$mform->createElement('text', $fieldname, null, array('size'=>'32'));
        $mform->setType($fieldname, PARAM_NOTAGS);
        $mform->setDefault($fieldname, $value);
        $mform->disabledIf($fieldname, "searchoperator$i", 'eq', '');
        
        return array($arr, null);
    }

    /**
     *
     */
    public function display_import(&$mform, $tags) {
        $fieldid = $this->_field->id();
        foreach ($tags as $tag) {
            $cleantag = trim($tag, "[]#");
            $name = "f_{$fieldid}_{$cleantag}_name";
            $mform->addElement('text', $name, $cleantag, array('size'=>'16'));
            $mform->setType($name, PARAM_NOTAGS);
            $mform->setDefault($name, $cleantag);
        }
    }

    /**
     *
     */
    public final function get_menu($showall = false) {
        // the default menu category for fields
        $patternsmenu = array();
        foreach ($this->patterns() as $tag => $pattern) {
            if ($showall or $pattern[self::PATTERN_SHOW_IN_MENU]) {
                // which category
                if (!empty($pattern[self::PATTERN_CATEGORY])) {
                    $cat = $pattern[self::PATTERN_CATEGORY];
                } else {
                    $cat = get_string('fields', 'dataform');
                }
                // prepare array
                if (!isset($patternsmenu[$cat])) {
                    $patternsmenu[$cat] = array($cat => array());
                }
                // add tag
                $patternsmenu[$cat][$cat][$tag] = $tag;
            }
        }
        return $patternsmenu;
    }

    /**
     *
     */
    public function get_replacements(array $tags = null, $entry = null, array $options = null) {
        $replacements = $this->replacements($tags, $entry, $options);
        $edit = !empty($options['edit']) ? $options['edit'] : false;

        // Set the label replacement if applicable
        $labelreplacement = array();
        $field = $this->_field;
        $fieldname = $field->name();
        if (in_array("[[$fieldname@]]", $tags) and !empty($field->field->label)) {
            if ($edit) {
                $labelreplacement["[[$fieldname@]]"] = array('', array(array($this ,'parse_label'), array($replacements)));
            } else {
                $labelcontent = $field->field->label;
                foreach ($replacements as $pattern => $replacement) {
                    if (empty($replacement)) {
                        continue;
                    }
                    list(,$content) = $replacement;                   
                    $labelcontent = str_replace($pattern, $content, $labelcontent);
                }
                $labelreplacement["[[$fieldname@]]"] = array('html', $labelcontent);
            }
            $replacements = $labelreplacement + $replacements;
        }
        return $replacements;
    }

    /**
     * @param array $patterns array of arrays of pattern replacement pairs
     */
    public function parse_label(&$mform, $definitions) {
        $field = $this->_field;
        $patterns = array_keys($definitions);
        $delims = implode('|', $patterns);
        // escape [ and ] and the pattern rule character *
        // TODO organize this
        $delims = str_replace(array('[', ']', '*', '^'), array('\[', '\]', '\*', '\^'), $delims);

        $parts = preg_split("/($delims)/", $field->field->label, null, PREG_SPLIT_DELIM_CAPTURE);
        foreach ($parts as $part) {
            if (in_array($part, $patterns)) {
                if ($def = $definitions[$part]) {
                    list($type, $content) = $def;
                    if ($type === 'html') {
                        $mform->addElement('html', $content);
                    } else {
                        list($func, $params) = $content;
                        call_user_func_array($func, array_merge(array($mform),$params));
                    }
                }
            } else {
                $mform->addElement('html', $part);
            }
        }
    }

    /**
     *
     */
    public function validate_data($entryid, $tags, $data) {
        return null;
    }

    /**
     * Returns array of replacements for the field patterns
     * The label pattern should always be first where applicable
     * so that it is processed first in view templates 
     * so that in turn patterns it may contain could be processed.
     *
     * @return array pattern => array(visible in menu, category) 
     */
    protected function replacements(array $tags = null, $entry = null, array $options = null) {
        throw new coding_exception('replacements() method needs to be overridden in each subclass of dataformfield_renderer');
    }

    /**
     * Array of patterns this field supports
     * The label pattern should always be first where applicable
     * so that it is processed first in view templates 
     * so that in turn patterns it may contain could be processed.
     *
     * @return array pattern => array(visible in menu, category) 
     */
    protected function patterns() {
        $fieldname = $this->_field->name();

        $patterns = array();
        $patterns["[[$fieldname@]]"] = array(true);
        
        return $patterns;
    }
}
