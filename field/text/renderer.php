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
 * @subpackage text
 * @copyright 2011 Itamar Tzadok
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') or die;

require_once("$CFG->dirroot/mod/dataform/field/renderer.php");

/**
 *
 */
class dataformfield_text_renderer extends dataformfield_renderer {

    /**
     *
     */
    protected function replacements(array $tags = null, $entry = null, array $options = null) {
        $field = $this->_field;
        $fieldname = $field->name();
        $edit = !empty($options['edit']) ? $options['edit'] : false;

        $replacements = array();
        // rules support
        $tags = $this->add_clean_pattern_keys($tags);

        foreach ($tags as $tag => $cleantag) {
            $noedit = $this->is_noedit($tag);
            if ($edit and !$noedit) {
                if ($cleantag == "[[$fieldname]]") {
                    $required = $this->is_required($tag);
                    $replacements[$tag] = array('', array(array($this,'display_edit'), array($entry, array('required' => $required))));
                } else {
                    $replacements[$tag] = '';
                }
            } else {
                switch ($cleantag) {
                    case "[[$fieldname]]":
                        $replacements[$tag] = array('html', $this->display_browse($entry));
                        break;
                    case "[[$fieldname:quest:text]]":
                        $replacements[$tag] = array('html', $this->display_quest_text($entry));
                        break;
                    case "[[$fieldname:quest:select]]":
                        $replacements[$tag] = array('html', $this->display_quest_select($entry));
                        break;
                    default:
                        $replacements[$tag] = '';
                }
            }
        }

        return $replacements;
    }

    /**
     *
     */
    public function validate_data($entryid, $tags, $data) {
        $field = $this->_field;
        $fieldid = $field->id();
        $fieldname = $field->name();

        $formfieldname = "field_{$fieldid}_{$entryid}";
        $tags = $this->add_clean_pattern_keys($tags);

        // only [[$fieldname]] is editable so check it if exists
        if (array_key_exists("[[*$fieldname]]", $tags) and isset($data->$formfieldname)) {
            if (!$content = clean_param($data->$formfieldname, PARAM_NOTAGS)) {
                return array($formfieldname, get_string('fieldrequired', 'dataform'));
            }
        }
        return null;
    }

    /**
     *
     */
    public function display_edit(&$mform, $entry, array $options = null) {
        $field = $this->_field;
        $fieldid = $field->id();
        $entryid = $entry->id;

        $content = '';
        if ($entryid > 0 and !empty($entry->{"c{$fieldid}_content"})){
            $content = $entry->{"c{$fieldid}_content"};
        }

        $fieldattr = array();

        if ($field->get('param2')) {
            $fieldattr['style'] = 'width:'. s($field->get('param2')). s($field->get('param3')). ';';
        }

        if ($field->get('param4')) {
            $fieldattr['class'] = s($field->get('param4'));
        }

        $fieldname = "field_{$fieldid}_{$entryid}";
        $mform->addElement('text', $fieldname, null, $fieldattr);
        $mform->setType($fieldname, PARAM_TEXT);
        $mform->setDefault($fieldname, $content);
        $required = !empty($options['required']);
        if ($required) {
            $mform->addRule($fieldname, null, 'required', null, 'client');
        }
        // format rule
        if ($format = $field->get('param4')) {
            $mform->addRule($fieldname, null, $format, null, 'client');
            // Adjust type
            switch($format) {
                case 'alphanumeric': $mform->setType($fieldname, PARAM_ALPHANUM); break;
                case 'lettersonly': $mform->setType($fieldname, PARAM_ALPHA); break;
                case 'numeric': $mform->setType($fieldname, PARAM_INT); break;
                case 'email': $mform->setType($fieldname, PARAM_EMAIL); break;            
            }
        }
        // length rule
        if ($length = $field->get('param5')) {
            ($min = $field->get('param6')) or ($min = 0);
            ($max = $field->get('param7')) or ($max = 64);
            
            switch ($length) {
                case 'minlength': $val = $min; break;
                case 'maxlength': $val = $max; break;
                case 'rangelength': $val = array($min, $max); break;
            }                
            $mform->addRule($fieldname, null, $length, $val, 'client');
        }        
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
                $format = FORMAT_PLAIN;
                $options->filter=false;
            }

            $str = format_text($content, $format, $options);
        } else {
            $str = '';
        }

        return $str;
    }

    /**
     *
     */
    protected function display_quest_text($entry) {
        $field = $this->_field;
        $fieldid = $field->id();

        $str = '';
        if (isset($entry->{"c{$fieldid}_content"})) {
            if ($content = $entry->{"c{$fieldid}_content"}) {
                $str = html_writer::empty_tag(
                    'input',
                    array('type' => 'text',
                        'onkeyup' => "this.style.backgroundColor=(this.value=='".$content."')?'#ccff99':'#ffcc99';")
                );
            }
        }

        return $str;
    }

    /**
     *
     */
    protected function display_quest_select($entry) {
        $field = $this->_field;
        $fieldid = $field->id();

        $str = '';
        if (isset($entry->{"c{$fieldid}_content"})) {
            if ($content = $entry->{"c{$fieldid}_content"}) {
                $fieldcontents = $field->get_distinct_content();
                shuffle($fieldcontents);
                $options = array_slice($fieldcontents, 0, 5);
                // not this content in options, add it in place of a random option
                if (!in_array($content, $options)) {
                    $options[rand(0, 4)] = $content;
                }
                $str = html_writer::select(
                    array_combine($options, $options),
                    null,
                    '',
                    array('' => 'choosedots'),
                    array('onchange' => "this.style.backgroundColor=(this.options[this.selectedIndex].value=='".$content."')?'#ccff99':'#ffcc99';")
                );
            }
        }

        return $str;
    }

    /**
     * Array of patterns this field supports
     */
    protected function patterns() {
        $fieldname = $this->_field->name();

        $patterns = parent::patterns();
        $patterns["[[$fieldname]]"] = array(true);
        $patterns["[[$fieldname:quest:text]]"] = array(false);
        $patterns["[[$fieldname:quest:select]]"] = array(false);

       return $patterns;
    }

    /**
     * Array of patterns this field supports
     */
    protected function supports_rules() {
        return array(
            self::RULE_REQUIRED,
            self::RULE_NOEDIT,
        );
    }
}
