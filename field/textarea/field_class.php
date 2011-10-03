<?php

/**
 * This file is part of the Dataform module for Moodle - http://moodle.org/.
 *
 * @package mod-dataform
 * @subpackage field-textarea
 * @copyright 2011 Itamar Tzadok
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * The Dataform has been developed as an enhanced counterpart
 * of Moodle's Database activity module (1.9.11+ (20110323)).
 * To the extent that Dataform code corresponds to Database code,
 * certain copyrights on Database module may obtain, including:
 * @copyright 1999 Moodle Pty Ltd http://moodle.com
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

require_once($CFG->dirroot.'/mod/dataform/field/field_class.php');
require_once($CFG->dirroot.'/lib/filelib.php');
require_once($CFG->dirroot.'/repository/lib.php');

class dataform_field_textarea extends dataform_field_base {

    public $type = 'textarea';

    protected $editoroptions;

    public function __construct($df = 0, $field = 0) {
        parent::__construct($df, $field);

        $this->editoroptions = array();
        $this->editoroptions['context'] = $this->df->context;
        $this->editoroptions['trusttext'] = $this->field->param4;
        $this->editoroptions['maxbytes'] = $this->field->param5;
        $this->editoroptions['maxfiles'] = $this->field->param6;
        $this->editoroptions['subdirs'] = false;
        $this->editoroptions['changeformat'] = 0;
        $this->editoroptions['forcehttps'] = false;
        $this->editoroptions['noclean'] = false;
    }

    /**
     * 
     */
    public function patterns($tags = null, $entry = null, $edit = false, $editable = false) {
        $patterns = parent::patterns($tags, $entry, $edit, $editable);
        // if no tags requested, return select menu
        if (is_null($tags)) {
            $patterns['fields']['fields']["[[{$this->field->name}:wordcount]]"] = "[[{$this->field->name}:wordcount]]";

        } else {

            foreach ($tags as $tag) {
                // need only to add word count
                if ($tag == "[[{$this->field->name}:wordcount]]") {
                    if ($edit) {
                        $patterns["[[{$this->field->name}:wordcount]]"] = '';
                    } else {
                        $patterns["[[{$this->field->name}:wordcount]]"] = array('html', $this->word_count($entry));
                    }
                    break;
                }
            }
        }

        return $patterns;
    }

    /**
     * Sets up a field object
     */
    public function set_field($forminput = null) {
        parent::set_field($forminput);

        // sets some defaults
        if (is_null($forminput)) {
            // is editor
            $this->field->param1 = 1;
            // cols
            $this->field->param2 = 40;
            // rows
            $this->field->param3 = 35;
            // trust text
            $this->field->param4 = 0;
            // max files
            $this->field->param6 = -1;
        }

        return true;
    }

    /**
     *
     */
    public function is_editor() {
        return $this->field->param1;
    }

    /**
     *
     */
    public function update_content($entry, array $values = null) {
        global $DB;

        $entryid = $entry->id;
        $fieldid = $this->field->id;

        $contentid = isset($entry->{"c{$fieldid}_id"}) ? $entry->{"c{$fieldid}_id"} : null;

        $fieldname = "field_{$fieldid}_{$entry->id}";

        if (!empty($values)) {
            $data = (object) $values;
        } else {
            return true;
        }

        $rec = new object;
        $rec->fieldid = $fieldid;
        $rec->entryid = $entryid;

        if (!$contentid) {
            $contentid = $DB->insert_record('dataform_contents', $rec);
        }

        // check if the content is from a new entry
        // in which case entry id in the data is < 0
        $names = explode('_',key($values));
        if ((int) $names[2] < 0) {
            $adjustedfieldname = "field_{$fieldid}_{$names[2]}";
        } else {
            $adjustedfieldname = "field_{$fieldid}_{$entry->id}";
        }        

        $data = file_postupdate_standard_editor($data, $adjustedfieldname, $this->editoroptions, $this->df->context, 'mod_dataform', 'content', $contentid);

        $rec->content = $data->{$adjustedfieldname};
        $rec->content1 = $data->{"{$adjustedfieldname}format"};
        $rec->id = $contentid;
        return $DB->update_record('dataform_contents', $rec);
    }

    /**
     *
     */
    public function get_select_sql() {
        $id = " c{$this->field->id}.id AS c{$this->field->id}_id ";
        $content = $this->get_sql_compare_text(). " AS c{$this->field->id}_content";
        $content1 = " c{$this->field->id}.content1 AS c{$this->field->id}_content1";
        return " $id , $content , $content1 ";
    }

    /**
     * 
     */
    public function prepare_import_content(&$data, $entryid, $value) {
        $fieldid = $this->field->id;
        
        $valuearr = explode('##', $value);
        $content = array();
        $content['text'] = !empty($valuearr[0]) ? $valuearr[0] : null;
        $content['format'] = !empty($valuearr[1]) ? $valuearr[1] : FORMAT_MOODLE;
        $content['trust'] = !empty($valuearr[2]) ? $valuearr[2] : $this->field->param4;
        $data->{"field_{$fieldid}_{$entryid}_editor"} = $content;
    
        return true;
    }

    /**
     *
     */
    public function export_text_value($content) {
        $exporttext = $content->content;
        if ($content->content1 != FORMAT_HTML) {
            $exporttext .= "##$content->content1";
        }
        return $exporttext;
    }

    /**
     *
     */
    public function display_edit(&$mform, $entry) {
        global $PAGE, $CFG;
        
        $fieldid = $this->field->id;
        $entryid = $entry->id;
        $fieldname = "field_{$fieldid}_{$entryid}";

        // word count
        if ($this->field->param9) {
            $mform->addElement('html', '<link type="text/css" rel="stylesheet" href="'. "$CFG->libdir/yui/2.8.2/build/progressbar/assets/skins/sam/progressbar.css". '">');

            $pbcontainer = '<div id="'. "id_{$fieldname}_wordcount_pb". '"></div>';
            $minvaluecontainer = '<div id="'. "id_{$fieldname}_wordcount_minvalue". '" class="yui-pb-range" style="float:left;">0</div>';
            $maxvaluecontainer = '<div id="'. "id_{$fieldname}_wordcount_maxvalue". '" class="yui-pb-range" style="float:right;">'.$this->field->param8.'</div>';
            $valuecontainer = '<div class="yui-pb-caption"><span id="'. "id_{$fieldname}_wordcount_value". '"></span></div>';
            $captionscontainer = '<div id="'. "id_{$fieldname}_wordcount_captions". '">'.
                                    $minvaluecontainer. $maxvaluecontainer. $valuecontainer.
                                    '</div>';
            $mform->addElement('html', '<table style="margin-left:16%;"><tr><td>'.
                                        $pbcontainer.
                                        $captionscontainer.
                                        '</td></tr></table>');

            $options = new object;
            $options->minValue = 0;
            $options->maxValue = $this->field->param8;
            $options->value = 0;
            $options->minRequired = $this->field->param7;
            $options->identifier = $fieldname;
                     
            $module = array(
                'name' => 'M.dataform_wordcount_bar',
                'fullpath' => '/mod/dataform/dataform.js',
                'requires' => array('yui2-yahoo-dom-event', 'yui2-element', 'yui2-animation', 'yui2-progressbar'));

            $PAGE->requires->js_init_call('M.dataform_wordcount_bar.init', array($options), true, $module);
        }

        // editor
        $contentid = isset($entry->{"c{$fieldid}_id"}) ? $entry->{"c{$fieldid}_id"} : null;

        $data = new object;
        $data->{$fieldname} = isset($entry->{"c{$fieldid}_content"}) ? $entry->{"c{$fieldid}_content"} : '';
        $data->{"{$fieldname}format"} = isset($entry->{"c{$fieldid}_content1"}) ? $entry->{"c{$fieldid}_content1"} : FORMAT_HTML;

        if (!$this->is_editor() or !can_use_html_editor()) {
            $data->{"{$fieldname}format"} = FORMAT_PLAIN;
        }

        $data = file_prepare_standard_editor($data, $fieldname, $this->editoroptions, $this->df->context, 'mod_dataform', 'content', $contentid);

        $attr = array();
        $attr['cols'] = !$this->field->param2 ? 40 : $this->field->param2;
        $attr['rows'] = !$this->field->param3 ? 20 : $this->field->param3;

        $mform->addElement('editor', "{$fieldname}_editor", null, $attr , $this->editoroptions);
        $mform->setDefault("{$fieldname}_editor", $data->{"{$fieldname}_editor"});
        $mform->setDefault("{$fieldname}[text]", $data->{$fieldname});
        $mform->setDefault("{$fieldname}[format]", $data->{"{$fieldname}format"});

    }

    /**
     * Print the content for browsing the entry
     */
    public function display_browse($entry) {

        $fieldid = $this->field->id;

        if (isset($entry->{"c$fieldid". '_content'})) {
            $contentid = $entry->{"c{$fieldid}_id"};
            $text = $entry->{"c$fieldid". '_content'};
            $format = isset($entry->{"c{$fieldid}_content1"}) ? $entry->{"c{$fieldid}_content1"} : FORMAT_HTML;

            $text = file_rewrite_pluginfile_urls($text, 'pluginfile.php', $this->df->context->id, 'mod_dataform', 'content', $contentid);

            $options = new object();
            $options->para = false;
            $options->overflowdiv = true;
            $str = format_text($text, $format, $options);
            return $str;
        } else {
            return '';
        }
    }

    /**
     * 
     */
    public function word_count($entry) {

        $fieldid = $this->field->id;

        if (isset($entry->{"c$fieldid". '_content'})) {
            $text = $entry->{"c$fieldid". '_content'};

            return '';
            //$options = new object();
            //$options->para = false;
            //$str = format_text($text, FORMAT_PLAIN, $options);
        } else {
            return '';
        }
    }

}
