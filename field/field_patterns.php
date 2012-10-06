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
abstract class mod_dataform_field_patterns {

    const PATTERN_SHOW_IN_MENU = 0;
    const PATTERN_CATEGORY = 1;

    const RULE_REQUIRED = '*';

    protected $_field = null;

    /**
     * Constructor
     */
    public function __construct(&$field) {
        $this->_field = $field;
    }

    /**
     *
     */
    public function search($text) {
        $fieldid = $this->_field->field->id;
        
        $found = array();
        $patterns = array_keys($this->patterns());
        $wrapopen = $fieldid > 0 ? '\[\[' : '##';
        $wrapclose =  $fieldid > 0 ? '\]\]' : '##';
        if ($rules = implode('', $this->supports_rules())) {
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
            $keypatterns[str_replace($this->supports_rules(), '', $pattern)] = $pattern;
        }
        return $keypatterns;
    }

    /**
     *
     */
    public function is_required($pattern) {
        // TODO must be after opening brackets and before field name
        return strpos($pattern, '*') !== false;
    }

    /**
     *
     */
    public function is_hidden($pattern) {
        // TODO must be after opening brackets and before field name
        return strpos($pattern, '^') !== false;
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
        $mform->addElement('text', "f_{$i}_$fieldid", null, array('size'=>'32'));
        $mform->setType("f_{$i}_$fieldid", PARAM_NOTAGS);
        $mform->setDefault("f_{$i}_$fieldid", $value);
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
    public function get_replacements($tags = null, $entry = null, array $options = null) {
        throw new coding_exception('get_replacements() method needs to be overridden in each subclass of mod_dataform_field_patterns');
    }

    /**
     *
     */
    public function validate_data($entryid, $tags, $data) {
        return null;
    }

    /**
     * Array of patterns this field supports
     */
    protected function patterns() {
        throw new coding_exception('patterns() method needs to be overridden in each subclass of mod_dataform_field_patterns');
    }
}
