<?php

/**
 * This file is part of the Dataform module for Moodle - http://moodle.org/.
 *
 * @package mod-dataform
 * @package field-file
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
class mod_dataform_field_file_patterns extends mod_dataform_field_patterns {

    /**
     * 
     */
    public function get_replacements($tags = null, $entry = null, $edit = false, $editable = false) {
        $field = $this->_field;
        $fieldname = $field->name();
        $replacements = array();

        foreach ($tags as $tag) {
            if ($tag == "[[$fieldname]]") {
                if ($edit) {
                    $replacements[$tag] = array('', array(array($this,'display_edit'), array($entry)));
                } else {
                    $replacements[$tag] = array('html', $this->display_browse($entry));
                }
            // url    
            } else if ($tag == "[[{$fieldname}:url]]") {
                $replacements[$tag] = array('html', $this->display_browse($entry, array('url' => 1)));
            // alt
            } else if ($tag == "[[{$fieldname}:alt]]") {
                $replacements[$tag] = array('html', $this->display_browse($entry, array('alt' => 1)));
            // size
            } else if ($tag == "[[{$fieldname}:size]]") {
                $replacements[$tag] = array('html', $this->display_browse($entry, array('size' => 1)));
            // content (for html files)
            } else if ($tag == "[[{$fieldname}:content]]") {
                if ($edit) {
                    $replacements[$tag] = array('', array(array($this,'display_edit_content'), array($entry)));
                } else {
                    $replacements[$tag] = array('html', $this->display_browse($entry, array('content' => 1)));
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
        $contentid = isset($entry->{"c{$fieldid}_id"}) ? $entry->{"c{$fieldid}_id"} : null;
        $content = isset($entry->{"c{$fieldid}_content"}) ? $entry->{"c{$fieldid}_content"} : null;
        $content1 = isset($entry->{"c{$fieldid}_content1"}) ? $entry->{"c{$fieldid}_content1"} : null;
        
        $fieldname = "field_{$fieldid}_{$entryid}";
        $fmoptions = array('subdirs' => 0,
                            'maxbytes' => $field->get('param1'),
                            'maxfiles' => $field->get('param2'),
                            'accepted_types' => array($field->get('param3')));

        $draftitemid = file_get_submitted_draft_itemid("{$fieldname}_filemanager");
        file_prepare_draft_area($draftitemid, $field->df()->context->id, 'mod_dataform', 'content', $contentid, $fmoptions);

        // file manager
        $mform->addElement('filemanager', "{$fieldname}_filemanager", null, null, $fmoptions);
        $mform->setDefault("{$fieldname}_filemanager", $draftitemid);

        // alt text
        $options = array();
        $mform->addElement('text', "{$fieldname}_alttext", get_string('alttext','dataformfield_file'), $options);
        $mform->setDefault("{$fieldname}_alttext", s($content1));

        // delete (only for multiple files)
        if ($field->get('param2') > 1) {
            $mform->addElement('checkbox', "{$fieldname}_delete", get_string('clearcontent','dataformfield_file'));
        }
    }

    /**
     *
     */
    public function display_edit_content(&$mform, $entry) {
        $field = $this->_field;
        $fieldid = $field->id();
        $entryid = $entry->id;
        $fieldname = "field_{$fieldid}_{$entryid}";

        $contentid = isset($entry->{"c{$fieldid}_id"}) ? $entry->{"c{$fieldid}_id"} : null;
        $content = isset($entry->{"c{$fieldid}_content"}) ? $entry->{"c{$fieldid}_content"} : null;
        $content1 = isset($entry->{"c{$fieldid}_content1"}) ? $entry->{"c{$fieldid}_content1"} : null;
        
        // get the file content
        $fs = get_file_storage();
        $files = $fs->get_area_files($field->df()->context->id, 'mod_dataform', 'content', $contentid);
        if (!$files or !(count($files) > 1)) {
            return '';
        }

        $strcontent = '';
        foreach ($files as $file) {
            if (!$file->is_directory()) {
                $strcontent = $file->get_content();
                break;
            }
        }

        $data = new object;
        $data->{$fieldname} = $strcontent;
        $data->{"{$fieldname}format"} = FORMAT_HTML;

        //if (!$field->is_editor() or !can_use_html_editor()) {
        //    $data->{"{$fieldname}format"} = FORMAT_PLAIN;
        //}

        $options = array();
        $options['context'] = $field->df()->context;
//        $options['trusttext'] = true;
//        $options['maxbytes'] = $field->df()->course->maxbytes;
//        $options['maxfiles'] = EDITOR_UNLIMITED_FILES;
//        $options['subdirs'] = false;
//        $options['changeformat'] = 0;
//        $options['forcehttps'] = false;
//        $options['noclean'] = true;

        $data = file_prepare_standard_editor($data, $fieldname, $options, $field->df()->context, 'mod_dataform', 'content', $contentid);

        $attr = array();
        //$attr['cols'] = !$field->get('param2') ? 40 : $field->get('param2');
        //$attr['rows'] = !$field->get('param3') ? 20 : $field->get('param3');

        $mform->addElement('editor', "{$fieldname}_editor", null, null, $options);
        $mform->setDefault("{$fieldname}_editor", $data->{"{$fieldname}_editor"});
        $mform->setDefault("{$fieldname}[text]", $strcontent);
        $mform->setDefault("{$fieldname}[format]", $data->{"{$fieldname}format"});
    }

    /**
     *
     */
    public function display_browse($entry, $params = null) {

        $field = $this->_field;
        $fieldid = $field->id();
        $entryid = $entry->id;

        $content = isset($entry->{"c{$fieldid}_content"}) ? $entry->{"c{$fieldid}_content"} : null;
        $content1 = isset($entry->{"c{$fieldid}_content1"}) ? $entry->{"c{$fieldid}_content1"} : null;
        $contentid = isset($entry->{"c{$fieldid}_id"}) ? $entry->{"c{$fieldid}_id"} : null;
        
        if (empty($content)) {
            return '';
        }

        $fs = get_file_storage();
        $files = $fs->get_area_files($field->df()->context->id, 'mod_dataform', 'content', $contentid);
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
                $path = "/pluginfile.php/{$field->df()->context->id}/mod_dataform/content/$contentid";

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
        
        } else if (!empty($params['size'])) {
            $bsize = $file->get_filesize();
            if ($bsize < 1000000) {
                $size = round($bsize/1000,1). 'KB';
            } else {
                $size = round($bsize/1000000,1). 'MB';
            }
            return $size;
        
        } else if (!empty($params['content'])) {
            return $file->get_content();
        
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
    public function pluginfile_patterns() {
        return array("[[{$this->_field->name()}]]");
    }

    /**
     *
     */
    public function display_import(&$mform, $tags) {
        $field = $this->_field;
        $fieldid = $field->id();
        $fieldname = $this->_field->name();

        foreach ($tags as $tag) {
            $tagname = trim($tag, "[]#");
            switch ($tagname) {
                case $fieldname:
                    $fmoptions = array('subdirs' => 0,
                                        'maxbytes' => $field->df()->course->maxbytes,
                                        'maxfiles' => 1,
                                        'accepted_types' => array('*.zip'),
                                );

                    $grp = array();
                    $grp[] = &$mform->createElement('text', "f_{$fieldid}_{$tagname}_name", null, array('size'=>'16'));                   
                    $grp[] = &$mform->createElement('filepicker', "f_{$fieldid}_{$tagname}_filepicker", null, null, $fmoptions);
                    $mform->addGroup($grp, "grp$tagname", $tagname, ' ', false);
                                        
                    $mform->setType("f_{$fieldid}_$tagname", PARAM_NOTAGS);
                    $mform->setDefault("f_{$fieldid}_$tagname", $tagname);


                    break;
                    
                case "$fieldname:alt":
                    $mform->addElement('text', "f_{$fieldid}_{$tagname}_name", $tagname, array('size'=>'16'));
                    $mform->setType("f_{$fieldid}_$tagname", PARAM_NOTAGS);
                    $mform->setDefault("f_{$fieldid}_$tagname", $tagname);
                    break;                
            }

        }
    }

    /**
     * Array of patterns this field supports 
     */
    protected function patterns() {
        $fieldname = $this->_field->name();

        $patterns = array();
        $patterns["[[$fieldname]]"] = array(true);
        $patterns["[[$fieldname:url]]"] = array(false);
        $patterns["[[$fieldname:alt]]"] = array(true);
        $patterns["[[$fieldname:size]]"] = array(false);
        $patterns["[[$fieldname:content]]"] = array(false);

        return $patterns; 
    }
}
