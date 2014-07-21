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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * @package dataformfield
 * @subpackage textarea
 * @copyright 2011 Itamar Tzadok
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') or die();

/**
 *
 */
class dataformfield_textarea_renderer extends mod_dataform\pluginbase\dataformfieldrenderer {

    /**
     *
     */
    protected function replacements(array $patterns, $entry, array $options = null) {
        $field = $this->_field;
        $fieldname = $field->name;
        $edit = !empty($options['edit']);

        $replacements = array();

        $replacements = array_fill_keys(array_keys($patterns), '');
        if ($edit) {
            foreach ($patterns as $pattern => $cleanpattern) {
                $params = null;
                $required = $this->is_required($pattern);
                if ($cleanpattern == "[[{$fieldname}:wordcount]]") {
                    $replacements[$pattern] = '';
                    continue;
                }
                $replacements[$pattern] = array(array($this, 'display_edit'), array($entry, array('required' => $required)));
                break;
            }

        } else {
            foreach ($patterns as $pattern => $cleanpattern) {
                switch ($cleanpattern) {
                    case "[[$fieldname]]":
                        $replacements[$pattern] = $this->display_browse($entry);
                        break;

                    // plain text, no links
                    case "[[$fieldname:text]]":
                        $replacements[$pattern] = html_to_text($this->display_browse($entry, array('text' => true)));
                        break;

                    // plain text, with links
                    case "[[$fieldname:textlinks]]":
                        $replacements[$pattern] = $this->display_browse($entry, array('text' => true, 'links' => true));
                        break;

                    case "[[{$fieldname}:wordcount]]":
                        $replacements[$pattern] = $this->word_count($entry);
                        break;
                }
            }
        }

        return $replacements;
    }

    /**
     *
     */
    public function validate_data($entryid, $patterns, $data) {
        $field = $this->_field;
        $fieldid = $field->id;
        $fieldname = $field->name;

        $patterns = $this->add_clean_pattern_keys($patterns);
        $editablepatterns = array(
            "[[$fieldname]]",
            "[[$fieldname:text]]",
            "[[$fieldname:textlinks]]"
        );

        if (!$field->is_editor()) {
            $formfieldname = "field_{$fieldid}_{$entryid}";
            $cleanformat = PARAM_NOTAGS;
        } else {
            $formfieldname = "field_{$fieldid}_{$entryid}_editor";
            $cleanformat = PARAM_CLEANHTML;
        }

        foreach ($editablepatterns as $cleanpattern) {
            $pattern = array_search($cleanpattern, $patterns);
            if ($pattern !== false and $this->is_required($pattern)) {
                if (empty($data->$formfieldname)) {
                    return array($formfieldname, get_string('fieldrequired', 'dataform'));
                }
                if (!$field->is_editor()) {
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
        $fieldid = $field->id;
        $entryid = $entry->id;
        $fieldname = "field_{$fieldid}_{$entryid}";

        // editor
        $contentid = isset($entry->{"c{$fieldid}_id"}) ? $entry->{"c{$fieldid}_id"} : null;

        $attr = array();
        $attr['cols'] = !$field->param2 ? 40 : $field->param2;
        $attr['rows'] = !$field->param3 ? 20 : $field->param3;

        $data = new object;
        $data->$fieldname = isset($entry->{"c{$fieldid}_content"}) ? $entry->{"c{$fieldid}_content"} : '';
        $required = !empty($options['required']);

        if (!$field->is_editor()) {
            $mform->addElement('textarea', $fieldname, null, $attr);
            $mform->setDefault($fieldname, $data->$fieldname);
            if ($required) {
                $mform->addRule($fieldname, null, 'required', null, 'client');
            }
        } else {
            // format
            $data->{"{$fieldname}format"} = isset($entry->{"c{$fieldid}_content1"}) ? $entry->{"c{$fieldid}_content1"} : FORMAT_HTML;

            $data = file_prepare_standard_editor($data, $fieldname, $field->get_editoroptions(), $field->get_df()->context, 'mod_dataform', 'content', $contentid);

            $mform->addElement('editor', "{$fieldname}_editor", null, $attr , $field->get_editoroptions() + array('collapsed' => true));
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

        $fieldid = $this->_field->id;

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
    public function display_browse($entry, $params = null) {

        $field = $this->_field;
        $fieldid = $field->id;

        if (isset($entry->{"c{$fieldid}_content"})) {
            $contentid = $entry->{"c{$fieldid}_id"};
            $text = $entry->{"c{$fieldid}_content"};
            $format = isset($entry->{"c{$fieldid}_content1"}) ? $entry->{"c{$fieldid}_content1"} : FORMAT_PLAIN;

            $text = file_rewrite_pluginfile_urls($text, 'pluginfile.php', $field->get_df()->context->id, 'mod_dataform', 'content', $contentid);

            $options = new stdClass;
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
        return array("[[{$this->_field->name}]]");
    }

    /**
     * Overriding {@link dataformfieldrenderer::get_pattern_import_settings()}
     * to allow only the base pattern.
     */
    public function get_pattern_import_settings(&$mform, $patternname, $header) {
        // Only [[fieldname]] can be imported
        if ($patternname != $this->_field->name) {
            return array(array(), array());
        }

        return parent::get_pattern_import_settings($mform, $patternname, $header);
    }

    /**
     * Array of patterns this field supports
     */
    protected function patterns() {
        $fieldname = $this->_field->name;

        $patterns = parent::patterns();
        $patterns["[[$fieldname]]"] = array(true, $fieldname);
        $patterns["[[$fieldname:text]]"] = array(false);
        $patterns["[[$fieldname:wordcount]]"] = array(false);

        return $patterns;
    }
}
