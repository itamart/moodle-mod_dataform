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
 * @subpackage textarea
 * @copyright 2011 Itamar Tzadok
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') or die();

require_once("$CFG->dirroot/mod/dataform/field/renderer.php");

/**
 *
 */
class dataformfield_textarea_renderer extends dataformfield_renderer {

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

        $replacements = array_fill_keys($tags, '');
        if ($edit) {
            foreach ($tags as $tag => $cleantag) {
                $params = null;
                $required = $this->is_required($tag);
                if ($cleantag == "[[{$fieldname}:wordcount]]") {
                    $replacements[$tag] = '';
                    continue;
                }
                $replacements[$tag] = array('', array(array($this,'display_edit'), array($entry, array('required' => $required))));
                break;
            }

        } else {
            foreach ($tags as $tag => $cleantag) {
                switch ($cleantag) {
                    case "[[$fieldname]]":
                        $replacements[$tag] = array('html', $this->display_browse($entry));
                        break;

                    // plain text, no links
                    case "[[$fieldname:text]]":
                        $replacements[$tag] = array('html', html_to_text($this->display_browse($entry, array('text' => true))));
                        break;

                    // plain text, with links
                    case "[[$fieldname:textlinks]]":
                        $replacements[$tag] = array('html', $this->display_browse($entry, array('text' => true, 'links' => true)));
                        break;

                    case "[[{$fieldname}:wordcount]]":
                        $replacements[$tag] = array('html', $this->word_count($entry));
                        break;
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

        $tags = $this->add_clean_pattern_keys($tags);
        $editabletags = array(
            "[[$fieldname]]",
            "[[$fieldname:text]]",
            "[[$fieldname:textlinks]]"
        );

        if (!$field->is_editor() or !can_use_html_editor()) {
            $formfieldname = "field_{$fieldid}_{$entryid}";
            $cleanformat = PARAM_NOTAGS;
        } else {
            $formfieldname = "field_{$fieldid}_{$entryid}_editor";
            $cleanformat = PARAM_CLEANHTML;
        }

        foreach ($editabletags as $cleantag) {
            $tag = array_search($cleantag, $tags);
            if ($tag !== false and $this->is_required($tag)) {
                if (empty($data->$formfieldname)) {
                    return array($formfieldname, get_string('fieldrequired', 'dataform'));
                }
                if (!$field->is_editor() or !can_use_html_editor()) {
                    if (!$content = clean_param($data->$formfieldname, $cleanformat)) {
                        return array($formfieldname, get_string('fieldrequired', 'dataform'));
                    }
                } else {
                    $editorobj = $data->$formfieldname;
                    if (!$content = clean_param($editorobj['text'], $cleanformat)) {
                        return array($formfieldname, get_string('fieldrequired', 'dataform'));
                    }
                }
            }
        }
        return null;
    }

    /**
     *
     */
    public function display_edit(&$mform, $entry, array $options = null) {
        global $PAGE, $CFG;

        $field = $this->_field;
        $fieldid = $field->id();
        $entryid = $entry->id;
        $fieldname = "field_{$fieldid}_{$entryid}";

        // editor
        $contentid = isset($entry->{"c{$fieldid}_id"}) ? $entry->{"c{$fieldid}_id"} : null;

        $attr = array();
        $attr['cols'] = !$field->get('param2') ? 40 : $field->get('param2');
        $attr['rows'] = !$field->get('param3') ? 20 : $field->get('param3');

        $data = new object;
        $data->$fieldname = isset($entry->{"c{$fieldid}_content"}) ? $entry->{"c{$fieldid}_content"} : '';
        $required = !empty($options['required']);

        if (!$field->is_editor() or !can_use_html_editor()) {
            $mform->addElement('textarea', $fieldname, null, $attr);
            $mform->setDefault($fieldname, $data->$fieldname);
            if ($required) {
                $mform->addRule($fieldname, null, 'required', null, 'client');
            }
        } else {
            // format
            $data->{"{$fieldname}format"} = isset($entry->{"c{$fieldid}_content1"}) ? $entry->{"c{$fieldid}_content1"} : FORMAT_HTML;

            $data = file_prepare_standard_editor($data, $fieldname, $field->editor_options(), $field->df()->context, 'mod_dataform', 'content', $contentid);

            $mform->addElement('editor', "{$fieldname}_editor", null, $attr , $field->editor_options() + array('collapsed' => true));
            $mform->setDefault("{$fieldname}_editor", $data->{"{$fieldname}_editor"});
            $mform->setDefault("{$fieldname}[text]", $data->$fieldname);
            $mform->setDefault("{$fieldname}[format]", $data->{"{$fieldname}format"});
            if ($required) {
                $mform->addRule("{$fieldname}_editor", null, 'required', null, 'client');
            }
        }
    }

    /**
     *
     */
    public function word_count($entry) {

        $fieldid = $this->_field->id();

        if (isset($entry->{"c{$fieldid}_content"})) {
            $text = $entry->{"c{$fieldid}_content"};
            return str_word_count($text);
        } else {
            return '';
        }
    }

    /**
     * Print the content for browsing the entry
     */
    protected function display_browse($entry, $params = null) {

        $field = $this->_field;
        $fieldid = $field->id();

        if (isset($entry->{"c{$fieldid}_content"})) {
            $contentid = $entry->{"c{$fieldid}_id"};
            $text = $entry->{"c{$fieldid}_content"};
            $format = isset($entry->{"c{$fieldid}_content1"}) ? $entry->{"c{$fieldid}_content1"} : FORMAT_PLAIN;

            $text = file_rewrite_pluginfile_urls($text, 'pluginfile.php', $field->df()->context->id, 'mod_dataform', 'content', $contentid);

            $options = new object();
            $options->para = false;
            $str = format_text($text, $format, $options);
            return $str;
        } else {
            return '';
        }
    }

    /**
     *
     */
    public function pluginfile_patterns() {
        return array("[[{$this->_field->name()}]]");
    }

    /**
     * Array of patterns this field supports
     */
    protected function patterns() {
        $fieldname = $this->_field->name();

        $patterns = parent::patterns();
        $patterns["[[$fieldname]]"] = array(true);
        $patterns["[[$fieldname:text]]"] = array(false);
        $patterns["[[$fieldname:wordcount]]"] = array(false);

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
