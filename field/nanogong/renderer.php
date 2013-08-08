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
 * @subpackage nanogong
 * @copyright 2011 Itamar Tzadok
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') or die();

require_once("$CFG->dirroot/mod/dataform/field/file/renderer.php");

/**
 *
 */
class dataformfield_nanogong_renderer extends dataformfield_file_renderer {

    /**
     * 
     */
    public function pluginfile_patterns() {
        return array();
    }

    /**
     *
     */
    public function display_edit(&$mform, $entry, array $options = null) {
        global $USER, $PAGE;

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

        if ($contentid) {
            $fileurl = moodle_url::make_file_url('/pluginfile.php', "/{$field->df()->context->id}/mod_dataform/content/$contentid/voicefile_{$fieldid}_{$entryid}.wav");
            $paramfileurl = html_writer::empty_tag('param', array('name' => 'SoundFileURL', 'value' => $fileurl));
        } else {
            $paramfileurl = '';
        }
        $paramaudioformat = html_writer::empty_tag('param', array('name' => 'AudioFormat', 'value' => 'ImaADPCM'));

        // applet
        $applet = html_writer::tag('applet',
                                    "$paramfileurl\n$paramaudioformat",
                                    array('archive' => new moodle_url('/mod/dataform/field/nanogong/applet/nanogong.jar'),
                                            'id' => "id_{$fieldname}_filemanager",
                                            'code' => 'gong.NanoGong',
                                            'width' => '180px',
                                            'height' => '40px'));
        
        $appletwrapper = html_writer::tag('div', $applet, array('class' => 'mdl-align'));
        $submitbutton = html_writer::empty_tag('input', array('type' => 'button', 'id' => "id_{$fieldname}_upload", 'value' => get_string('upload')));

        $mform->addElement('html', html_writer::tag('div', $appletwrapper. $submitbutton, array('class' => 'mdl-align')));
        $mform->addElement('hidden', "{$fieldname}_filemanager", $draftitemid);

        // alt text
        $options = array();
        $mform->addElement('text', "{$fieldname}_alttext", get_string('alttext','dataformfield_nanogong'), $options);
        $mform->setDefault("{$fieldname}_alttext", s($content1));

        // upload js 
        $filename = "voicefile_{$fieldid}_{$entryid}";
        $fileuploaderphp = new moodle_url('/mod/dataform/field/nanogong/ngupload.php',
                                        array('elname' => "id_{$fieldname}_filemanager",
                                            'userid' => $USER->id,
                                            'itemid' => $draftitemid,
                                            'maxbytes' => $field->get('param1'),
                                            'author' => $USER->firstname,
                                            'title' => $filename));

        $options = array(
            'fieldname' => $fieldname,
            'filename' => $filename,
            'acturl' => $fileuploaderphp->out(false)
        );

        $module = array(
            'name' => 'M.dataformfield_nanogong_upload_recording',
            'fullpath' => '/mod/dataform/field/nanogong/nanogong.js',
            'requires' => array('base', 'node')
        );

        $PAGE->requires->js_init_call('M.dataformfield_nanogong_upload_recording.init', array($options), false, $module);       

        // delete (only for multiple files)
        //if ($this->field->param2 > 1) {
        //    $mform->addElement('checkbox', "{$fieldname}_delete", get_string('contentclear','dataform'));
        //}
    }

    /**
     * 
     */
    protected function display_file($file, $path, $altname, $params = null) {
        global $CFG, $OUTPUT;

        $filename = $file->get_filename();
        $displayname = $altname ? $altname : $filename;
        
        if (!empty($params['url'])) {
            return moodle_url::make_file_url('/pluginfile.php', "$path/$filename");
                    
        } else if ($file->is_valid_image()) {
            /*
            return html_writer::empty_tag(
                'img',
                array(
                    'src' => moodle_url::make_file_url('/pluginfile.php', "$path/$filename"),
                    'alt' => $altname,
                    'title' => $altname
                )
            );
            */
        } else {
        
            $fileurl = moodle_url::make_file_url('/pluginfile.php', "$path/$filename");       
            $paramfileurl = html_writer::empty_tag('param', array('name' => 'SoundFileURL', 'value' => $fileurl));
            $paramaudiolevel = html_writer::empty_tag('param', array('name' => 'ShowAudioLevel', 'value' => 'true'));
            $paramtime = html_writer::empty_tag('param', array('name' => 'ShowTime', 'value' => 'true'));
            $paramrecordbutton = html_writer::empty_tag('param', array('name' => 'ShowRecordButton', 'value' => 'false'));

            $applet = html_writer::tag('applet',
                                        $paramfileurl. 
                                        $paramaudiolevel. 
                                        $paramtime. 
                                        $paramrecordbutton,
                                        array('archive' => new moodle_url('/mod/dataform/field/nanogong/applet/nanogong.jar'),
                                                'code' => 'gong.NanoGong',
                                                'width' => '100%',
                                                'height' => '40px',
                                                'style' => 'max-width:180px;'));
            
            $appletwrapper = html_writer::tag('div', $applet);
            return $appletwrapper;
        }
    }

}
