<?php

/**
 * This file is part of the Dataform module for Moodle - http://moodle.org/.
 *
 * @package mod-dataform
 * @subpackage field-file
 * @copyright 2011 Itamar Tzadok
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * The Dataform has been developed as an enhanced counterpart
 * of Moodle's Database activity module (1.9.11+ (20110323)).
 * To the extent that Dataform code corresponds to Database code,
 * certain copyrights on the Database module may obtain, including:
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

class dataform_field_file extends dataform_field_base {
    public $type = 'file';

    /**
     * 
     */
    public function patterns($tags = null, $entry = null, $edit = false, $editable = false) {
        $patterns = parent::patterns($tags, $entry, $edit, $editable);
        
        $fieldname = $this->field->name;
        
        // if no tags requested, return select menu
        if (is_null($tags)) {
            $patterns['fields']['fields']["[[{$fieldname}:url]]"] = "[[{$fieldname}:url]]";
            $patterns['fields']['fields']["[[{$fieldname}:alt]]"] = "[[{$fieldname}:alt]]";

        } else {
            foreach ($tags as $tag) {
                if ($tag == "[[{$fieldname}:url]]") {
                    $patterns["[[{$fieldname}:url]]"] = array('html', $this->display_browse($entry, array('url' => 1)));
                } else if ($tag == "[[{$fieldname}:alt]]") {
                    $patterns["[[{$fieldname}:alt]]"] = array('html', $this->display_browse($entry, array('alt' => 1)));
                }
            }
        }

        return $patterns;
    }

    /**
     *
     */
    public function update_content($entry, array $values = null) {
        global $DB, $USER;

        $entryid = $entry->id;
        $fieldid = $this->field->id;

        $filemanager = $alttext = $delete = null;
        if (!empty($values)) {
            foreach ($values as $name => $value) {
                $names = explode('_', $name);
                if (!empty($names[3]) and !empty($value)) {
                    ${$names[3]} = $value;
                }
            }
        }

        $contentid = isset($entry->{"c{$this->field->id}_id"}) ? $entry->{"c{$this->field->id}_id"} : null;

        if ($delete) {
            return $this->delete_content($entryid);
        } else {
            // check if there are files to store
            $fs = get_file_storage();

            $draftarea = $filemanager;
            $usercontext = get_context_instance(CONTEXT_USER, $USER->id);

            $files = $fs->get_area_files($usercontext->id, 'user', 'draft', $draftarea);
            if (count($files)>1) {
                // there are files to upload so add/update content record
                $rec = new object;
                $rec->fieldid = $fieldid;
                $rec->entryid = $entryid;
                $rec->content = 1;
                $rec->content1 = $alttext;

                if (!empty($contentid)) {
                    $rec->id = $contentid;
                    $DB->update_record('dataform_contents', $rec);
                } else {
                    $contentid = $DB->insert_record('dataform_contents', $rec);
                }
                
                // now save files
                $options = array('subdirs' => 0,
                                    'maxbytes' => $this->field->param1,
                                    'maxfiles' => $this->field->param2,
                                    'accepted_types' => $this->field->param3);
                $contextid = $this->df->context->id;
                file_save_draft_area_files($filemanager, $contextid, 'mod_dataform', 'content', $contentid, $options);

                $this->update_content_files($contentid);
            }
            $fs->delete_area_files($usercontext->id, 'user', 'draft', $draftarea);
        }
        return true;
    }

    /**
     *
     */
    public function format_content(array $values = null) {
        return array(null, null, null);
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
    public function export_text_supported() {
        return false;
    }

    /**
     *
     */
    public function import_text_supported() {
        return false;
    }

    /**
     *
     */
    public static function file_ok($path) {
        return true;
    }

    /**
     *
     */
    public function display_edit(&$mform, $entry) {

        $entryid = $entry->id;
        $contentid = isset($entry->{"c{$this->field->id}_id"}) ? $entry->{"c{$this->field->id}_id"} : null;
        $content = isset($entry->{"c{$this->field->id}_content"}) ? $entry->{"c{$this->field->id}_content"} : null;
        $content1 = isset($entry->{"c{$this->field->id}_content1"}) ? $entry->{"c{$this->field->id}_content1"} : null;
        
        $fieldname = "field_{$this->field->id}_{$entryid}";
        $fmoptions = array('subdirs' => 0,
                            'maxbytes' => $this->field->param1,
                            'maxfiles' => $this->field->param2,
                            'accepted_types' => $this->field->param3);

        $draftitemid = file_get_submitted_draft_itemid("{$fieldname}_filemanager");
        file_prepare_draft_area($draftitemid, $this->df->context->id, 'mod_dataform', 'content', $contentid, $fmoptions);

        // file manager
        $mform->addElement('filemanager', "{$fieldname}_filemanager", null, null, $fmoptions);
        $mform->setDefault("{$fieldname}_filemanager", $draftitemid);

        // alt text
        $options = array();
        $mform->addElement('text', "{$fieldname}_alttext", get_string('alttext','dataformfield_file'), $options);
        $mform->setDefault("{$fieldname}_alttext", s($content1));

        // delete (only for multiple files)
        if ($this->field->param2 > 1) {
            $mform->addElement('checkbox', "{$fieldname}_delete", get_string('clearcontent','dataformfield_file'));
        }
    }

    /**
     *
     */
    public function display_browse($entry, $params = null) {

        $fieldid = $this->field->id;
        $entryid = $entry->id;

        $content = isset($entry->{"c{$fieldid}_content"}) ? $entry->{"c{$fieldid}_content"} : null;
        $content1 = isset($entry->{"c{$fieldid}_content1"}) ? $entry->{"c{$fieldid}_content1"} : null;
        $contentid = isset($entry->{"c{$fieldid}_id"}) ? $entry->{"c{$fieldid}_id"} : null;
        
        if (empty($content)) {
            return '';
        }

        $fs = get_file_storage();
        $files = $fs->get_area_files($this->df->context->id, 'mod_dataform', 'content', $contentid);
        if (!$files or !(count($files) > 1)) {
            return '';
        }

        $altname = empty($content1) ? '' : s($content1);

        if (!empty($params['alt'])) {
            return $altname;
        }

        $strfiles = array();
        foreach ($files as $file) {
            if (!$file->is_directory()) {
                $filename = $file->get_filename();
                $filenameinfo = pathinfo($filename);
                $path = "/pluginfile.php/{$this->df->context->id}/mod_dataform/content/$contentid";

                $strfiles[] = $this->display_file($file, $path, $altname, $params);
            }
        }
        return implode('', $strfiles);
    }
                
    /**
     *
     */
    protected function display_file($file, $path, $altname, $params = null) {
        global $OUTPUT;

        $filename = $file->get_filename();
        $displayname = $altname ? $altname : $filename;
        
        if (!empty($params['url'])) {
            return new moodle_url("$path/$filename");
                    
        } else if ($file->is_valid_image()) {
            return html_writer::empty_tag('img', array('src' => new moodle_url("$path/$filename"),
                                                        'alt' => $altname,
                                                        'title' => $altname));
                                                        //'height' => '100%',
                                                        //'width' => '100%'));
        } else {
            $fileicon = html_writer::empty_tag('img', array('src' => $OUTPUT->pix_url(file_mimetype_icon($file->get_mimetype())),
                                                        'alt' => $file->get_mimetype(),
                                                        'height' => 16,
                                                        'width' => 16));
            return html_writer::link(new moodle_url("$path/$filename"), "$fileicon&nbsp;$displayname");
        }
    }

    /**
     *
     */
    protected function update_content_files($contentid, $params = null) {
        return true;
    }

}
