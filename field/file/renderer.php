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
 * @copyright 2011 Itamar Tzadok
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') or die();

require_once("$CFG->dirroot/mod/dataform/field/renderer.php");

/**
 *
 */
class dataformfield_file_renderer extends dataformfield_renderer {

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
            if ($edit) {
                if ($cleantag == "[[$fieldname]]") {
                    $required = $this->is_required($tag);
                    $replacements[$tag] = array('', array(array($this,'display_edit'), array($entry, array('required' => $required))));
                } else {
                    $replacements[$tag] = '';
                }
            } else {
                $displaybrowse = '';
                if ($cleantag == "[[$fieldname]]") {
                    $displaybrowse = $this->display_browse($entry);
                // url    
                } else if ($cleantag == "[[{$fieldname}:url]]") {
                    $displaybrowse = $this->display_browse($entry, array('url' => 1));
                // alt
                } else if ($cleantag == "[[{$fieldname}:alt]]") {
                    $displaybrowse = $this->display_browse($entry, array('alt' => 1));
                // size
                } else if ($cleantag == "[[{$fieldname}:size]]") {
                    $displaybrowse = $this->display_browse($entry, array('size' => 1));
                // content (for html files)
                } else if ($cleantag == "[[{$fieldname}:content]]") {
                    if ($edit) {
                        $replacements[$tag] = array('', array(array($this,'display_edit_content'), array($entry)));
                    } else {
                        $displaybrowse = $this->display_browse($entry, array('content' => 1));
                    }
                // download
                } else if ($cleantag == "[[{$fieldname}:download]]") {
                    $displaybrowse = $this->display_browse($entry, array('download' => 1));
                // download count
                } else if ($cleantag == "[[{$fieldname}:downloadcount]]") {
                    $displaybrowse = $this->display_browse($entry, array('downloadcount' => 1));
                }
                
                if (!empty($displaybrowse)) {
                    if ($this->is_hidden($tag)) {
                        $displaybrowse = html_writer::tag('span', $displaybrowse, array('class' => 'hide'));
                    }
                    $replacements[$tag] = array('html', $displaybrowse);
                } else {
                    $replacements[$tag] = '';
                }
            }           
        }

        return $replacements;
    }

    /**
     *
     */
    public function display_edit(&$mform, $entry, array $options = null) {
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
                            'accepted_types' => explode(',', $field->get('param3')));

        $draftitemid = file_get_submitted_draft_itemid("{$fieldname}_filemanager");
        file_prepare_draft_area($draftitemid, $field->df()->context->id, 'mod_dataform', 'content', $contentid, $fmoptions);

        // file manager
        $mform->addElement('filemanager', "{$fieldname}_filemanager", null, null, $fmoptions);
        $mform->setDefault("{$fieldname}_filemanager", $draftitemid);
        $required = !empty($options['required']);
        if ($required) {
            $mform->addRule("{$fieldname}_filemanager", null, 'required', null, 'client');
        }

        // alt text
        //$altoptions = array();
        //$mform->addElement('text', "{$fieldname}_alttext", get_string('alttext','dataformfield_file'), $altoptions);
        //$mform->setDefault("{$fieldname}_alttext", s($content1));

        // delete (only for multiple files)
        //if ($field->get('param2') > 1) {
        //    $mform->addElement('checkbox', "{$fieldname}_delete", get_string('clearcontent','dataformfield_file'));
        //}
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
        $options['collapsed'] = true;
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
    public function display_browse($entry, $params = null, $hidden = false) {

        $field = $this->_field;
        $fieldid = $field->id();
        $entryid = $entry->id;

        $content = isset($entry->{"c{$fieldid}_content"}) ? $entry->{"c{$fieldid}_content"} : null;
        $content1 = isset($entry->{"c{$fieldid}_content1"}) ? $entry->{"c{$fieldid}_content1"} : null;
        $content2 = isset($entry->{"c{$fieldid}_content2"}) ? $entry->{"c{$fieldid}_content2"} : null;
        $contentid = isset($entry->{"c{$fieldid}_id"}) ? $entry->{"c{$fieldid}_id"} : null;
        
        if (empty($content)) {
            return '';
        }

        if (!empty($params['downloadcount'])) {
            return $content2;
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
                $path = "/{$field->df()->context->id}/mod_dataform/content/$contentid";

                $strfiles[] = $this->display_file($file, $path, $altname, $params);
            }
        }
        return implode("<br />\n", $strfiles);
    }
                
    /**
     *
     */
    protected function display_file($file, $path, $altname, $params = null) {
        global $CFG, $OUTPUT;

        $filename = $file->get_filename();
        $pluginfileurl = '/pluginfile.php';
        
        if (!empty($params['url'])) {
            return moodle_url::make_file_url($pluginfileurl, "$path/$filename");
        
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
        
        } else {
            return $this->display_link($file, $path, $altname, $params);
        }
    }

    /**
     *
     */
    protected function display_link($file, $path, $altname, $params = null) {
        global $OUTPUT;
        
        $filename = $file->get_filename();
        $displayname = $altname ? $altname : $filename;

        $fileicon = html_writer::empty_tag('img', array(
            'src' => $OUTPUT->pix_url(file_mimetype_icon($file->get_mimetype())),
            'alt' => $file->get_mimetype(),
            'height' => 16,
            'width' => 16)
        );
        if (!empty($params['download'])) {
            list(,$context,,,$contentid) = explode('/', $path);
            $url = new moodle_url("/mod/dataform/field/file/download.php", array('cid' => $contentid, 'context' => $context, 'file' => $filename));
        } else {
            $url = moodle_url::make_file_url('/pluginfile.php', "$path/$filename");
        }                
        
        return html_writer::link($url, "$fileicon&nbsp;$displayname");
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

        $patterns = parent::patterns();
        $patterns["[[$fieldname]]"] = array(true);
        $patterns["[[$fieldname:url]]"] = array(false);
        $patterns["[[$fieldname:alt]]"] = array(true);
        $patterns["[[$fieldname:size]]"] = array(false);
        $patterns["[[$fieldname:content]]"] = array(false);
        $patterns["[[$fieldname:download]]"] = array(false);
        $patterns["[[$fieldname:downloadcount]]"] = array(false);

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
