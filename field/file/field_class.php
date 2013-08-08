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
 * @subpackage file
 * @copyright 2012 Itamar Tzadok
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once("$CFG->dirroot/mod/dataform/field/field_class.php");

/**
 * 
 */
class dataformfield_file extends dataformfield_base {
    public $type = 'file';
    
    // content - file manager
    // content1 - alt name
    // content2 - download counter

    /**
     *
     */
    protected function content_names() {
        return array('filemanager', 'alttext', 'delete', 'editor');
    }
    
    /**
     *
     */
    public function update_content($entry, array $values = null) {
        global $DB, $USER;

        $entryid = $entry->id;
        $fieldid = $this->field->id;

        $filemanager = $alttext = $delete = $editor = null;
        if (!empty($values)) {
            foreach ($values as $name => $value) {
                if (!empty($name) and !empty($value)) {
                    ${$name} = $value;
                }
            }
        }

        // update file content
        if ($editor) {
            return $this->save_changes_to_file($entry, $values);
        }
            
        // delete files
        //if ($delete) {
        //    return $this->delete_content($entryid);
        //}
        
        // store uploaded files
        $contentid = isset($entry->{"c{$this->field->id}_id"}) ? $entry->{"c{$this->field->id}_id"} : null;
        $draftarea = $filemanager;
        $usercontext = context_user::instance($USER->id);

        $fs = get_file_storage();
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

        // user cleared files from the field
        } else if (!empty($contentid)) {
            $this->delete_content($entryid);
        }
        return true;
    }

    /**
     *
     */
    protected function format_content($entry, array $values = null) {
        return array(null, null, null);
    }

    /**
     *
     */
    public function get_content_parts() {
        return array('content', 'content1', 'content2');
    }

    /**
     *
     */
    public function prepare_import_content(&$data, $importsettings, $csvrecord = null, $entryid = null) {
        global $USER;
    
        $fieldid = $this->field->id;
        $fieldname = $this->field->name;
        
        $draftid = $importsettings[$fieldname]['filepicker'];
        $usercontext = context_user::instance($USER->id);
        $fs = get_file_storage();
        if ($files = $fs->get_area_files($usercontext->id, 'user', 'draft', $draftid, 'sortorder', false)) {
            $zipfile = reset($files);
            // extract files to the draft area
            $zipper = get_file_packer('application/zip');
            $zipfile->extract_to_storage($zipper, $usercontext->id, 'user', 'draft', $draftid, '/');
            $zipfile->delete();
        
            // move each file to its own area and add info to data
            if ($files = $fs->get_area_files($usercontext->id, 'user', 'draft', $draftid, 'sortorder', false)) {
                $rec = new object;
                $rec->contextid = $usercontext->id;
                $rec->component = 'user';
                $rec->filearea = 'draft';

                $i = 0;
                foreach ($files as $file) {
                    //if ($file->is_valid_image()) {
                        // $get unused draft area
                        $itemid = file_get_unused_draft_itemid();
                        // move image to the new draft area 
                        $rec->itemid = $itemid;
                        $fs->create_file_from_storedfile($rec, $file);
                        // add info to data
                        $i--;
                        $fieldname = "field_{$fieldid}_$i";
                        $data->{"{$fieldname}_filemanager"} = $itemid;
                        $data->{"{$fieldname}_alttext"} = $file->get_filename();
                        $data->eids[$i] = $i;
                    //}
                }
                $fs->delete_area_files($usercontext->id, 'user', 'draft', $draftid);
            }
        }
        return $data;        
    }

    /**
     *
     */
    protected function update_content_files($contentid, $params = null) {
        return true;
    }

    /**
     *
     */
    protected function save_changes_to_file($entry, array $values = null) {

        $fieldid = $this->field->id;
        $entryid = $entry->id;
        $fieldname = "field_{$fieldid}_{$entry->id}";

        $contentid = isset($entry->{"c{$this->field->id}_id"}) ? $entry->{"c{$this->field->id}_id"} : null;

        $options = array('context' => $this->df->context);
        $data = (object) $values;
        $data = file_postupdate_standard_editor((object) $values, $fieldname, $options, $this->df->context, 'mod_dataform', 'content', $contentid);

        // get the file content
        $fs = get_file_storage();
        $file = reset($fs->get_area_files($this->df->context->id, 'mod_dataform', 'content', $contentid, 'sortorder', false));
        $filecontent = $file->get_content();
        
        // find content position (between body tags)
        $tmpbodypos = stripos($filecontent, '<body');
        $openbodypos = strpos($filecontent, '>', $tmpbodypos) + 1;
        $sublength = strripos($filecontent, '</body>') - $openbodypos;
        
        // replace body content with new content
        $filecontent = substr_replace($filecontent, $data->$fieldname, $openbodypos, $sublength);

        // prepare new file record
        $rec = new object;
        $rec->contextid = $this->df->context->id;
        $rec->component = 'mod_dataform';
        $rec->filearea = 'content';
        $rec->itemid = $contentid;
        $rec->filename = $file->get_filename();
        $rec->filepath = '/';
        $rec->timecreated = $file->get_timecreated();
        $rec->userid = $file->get_userid();
        $rec->source = $file->get_source();
        $rec->author = $file->get_author();
        $rec->license = $file->get_license();
        
        // delete old file
        $fs->delete_area_files($this->df->context->id, 'mod_dataform', 'content', $contentid);
        
        // create a new file from string
        $fs->create_file_from_string($rec, $filecontent);
        return true;           
    }
}
