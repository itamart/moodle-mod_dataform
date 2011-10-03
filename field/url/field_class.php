<?php

/**
 * This file is part of the Dataform module for Moodle - http://moodle.org/.
 *
 * @package mod-dataform
 * @subpackage field-url
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

require_once("$CFG->dirroot/mod/dataform/field/field_class.php");

class dataform_field_url extends dataform_field_base {
    public $type = 'url';

    /**
     *
     */
    public function update_content($entry, array $values = null) {
        global $DB;

        $fieldid = $this->field->id;
        
        list($content, $content1) = $this->format_content($values);
        $oldcontent = isset($entry->{"c$fieldid". '_content'}) ? $entry->{"c$fieldid". '_content'} : null;
        $oldcontent1 = isset($entry->{"c$fieldid". '_content1'}) ? $entry->{"c$fieldid". '_content1'} : null;
        $oldcontentid = isset($entry->{"c$fieldid". '_id'}) ? $entry->{"c$fieldid". '_id'} : null;
                    
        $rec = new object();
        $rec->fieldid = $fieldid;
        $rec->entryid = $entry->id;
        $rec->content = $content;
        $rec->content1 = $content1;

        if (!empty($oldcontent)) {
            if ($content != $oldcontent) {
                if (empty($content)) {
                    $this->delete_content($entry->id);
                } else {
                    $rec->id = $oldcontentid; // MUST_EXIST              
                    return $DB->update_record('dataform_contents', $rec);
                }
            } else if ($content1 != $oldcontent1) {
                return $DB->update_record('dataform_contents', $rec);
            }
        } else {
            if (!empty($content)) {
                return $DB->insert_record('dataform_contents', $rec);
            }
        }
        return true;
    }

    /**
     *
     */
    public function format_content(array $values = null) {
        $url = $alttext = null;
        if (!empty($values)) {
            foreach ($values as $name => $value) {
                if ($name) { // update from form
                    $names = explode('_', $name);
                    if (!empty($names[3])) {
                        switch ($names[3]) {
                            case 'url':
                                if ($value and $value != 'http://') {
                                    $url = clean_param($value, PARAM_URL);
                                }
                                break;
                            case 'alt':
                                $alttext = clean_param($value, PARAM_NOTAGS);
                                break;
                        }
                    }
                } else { // update from import
                    if (strpos($value, '##') !== false) {
                        $value = explode('##', $value);
                        $url = clean_param($value[0], PARAM_URL);
                        $alttext = clean_param($value[1], PARAM_NOTAGS);
                    } else {
                        $url = clean_param($value, PARAM_URL);
                    }
                    // there should be only one from import, so break
                    break;
                }
            }
        }
        return array($url, $alttext);
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
    public function notemptyfield($value, $name) {
        $names = explode('_',$name);
        $value = clean_param($value, PARAM_URL);
        //clean first
        if ($names[3] == '0') {
            return ($value!='http://' && !empty($value));
        }
        return false;
    }

    /**
     *
     */
    public function export_text_value($content) {
        $exporttext = $content->content;
        if ($content->content1) {
            $exporttext .= "##$content->content1";
        }
        return $exporttext;
    }

    /**
     *
     */
    public function display_edit(&$mform, $entry) {
        global $CFG, $PAGE;

        $entryid = $entry->id;
        $fieldid = $this->field->id;
        $fieldname = "field_{$fieldid}_{$entryid}";

        $contentid = isset($entry->{"c{$fieldid}_id"}) ? $entry->{"c{$fieldid}_id"} : null;
        $url = isset($entry->{"c{$fieldid}_content"}) ? $entry->{"c{$fieldid}_content"} : null;
        $alt = isset($entry->{"c{$fieldid}_content1"}) ? $entry->{"c{$fieldid}_content1"} : null;
        
        // prepare url picker if needed
        if ($usepicker = $this->field->param4) {
            require_once($CFG->dirroot. '/repository/lib.php'); // necessary for the constants used in args

            $args = new object();
            $args->accepted_types = '*';
            $args->return_types = FILE_EXTERNAL;
            $args->context = $this->df->context;
            $args->env = 'url';
            $fp = new file_picker($args);
            $options = $fp->options;

            $fieldinputid = "field_url_{$options->client_id}";

            $straddlink = get_string('choosealink', 'repository');
            $mform->addElement('button', null, $straddlink, array('id' => "filepicker-button-{$options->client_id}", 'style' => "display:none"));

            $module = array('name'=>'dataform_urlpicker', 'fullpath'=>'/mod/dataform/dataform.js', 'requires'=>array('core_filepicker'));
            $PAGE->requires->js_init_call('M.dataform_urlpicker.init', array($options), true, $module);
            $PAGE->requires->js_function_call('show_item', array('filepicker-button-'.$options->client_id));
        } else {
            $fieldinputid = '';
        }
        
        $url = empty($url) ? 'http://' : $url;
        $fieldattr = array();
        $fieldattr['title'] = s($this->field->description);
        $fieldattr['size'] = 60;        
        
        // add url input field
        $mform->addElement('text', "{$fieldname}_url", null, $fieldattr + array('id' => $fieldinputid));
        $mform->setDefault("{$fieldname}_url", s($url));

        // add alt name if not forcing name
        if (empty($this->field->param2)) {
            $mform->addElement('text', "{$fieldname}_alt", get_string('alttext','dataformfield_url'), $fieldattr);
            $mform->setDefault("{$fieldname}_alt", s($alt));
        }
    }

    /**
     *
     */
    public function display_browse($entry, $params = null) {
        global $CFG;
        $fieldid = $this->field->id;
        if (isset($entry->{"c$fieldid". '_content'})) {
            $url = $entry->{"c$fieldid". '_content'};
            if (empty($url) or ($url == 'http://')) {
                return '';
            }
            if (!empty($this->field->param2)) {
                // param2 forces the text to something
                $alttext = s($this->field->param2);
            } else {
                $alttext = empty($entry->{"c$fieldid". '_content1'}) ? '' : $entry->{"c$fieldid". '_content1'};
            }
           
            if ($this->field->param1) {
                // param1 defines whether we want to make the url a link
                if (!empty($alttext)) {
                    $str = '<a href="'.$url.'">'.$text.'</a>';
                } else {
                    $str = '<a href="'.$url.'">'.$url.'</a>';
                }
            } else {
                $str = $url;
            }
            if ($this->field->param3) {
                require_once("$CFG->dirroot/filter/mediaplugin/filter.php");
                $str = mediaplugin_filter($this->df->course->id, $str);
            }
            return $str;
        }
        return '';
    }
}
