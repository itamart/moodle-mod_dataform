<?php

/**
 * This file is part of the Dataform module for Moodle - http://moodle.org/.
 *
 * @package mod-dataform
 * @subpackage field-nanogong
 * @copyright 2011 Itamar Tzadok
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * The Dataform has been developed as an enhanced counterpart
 * of Moodle's Database activity module (1.9.11+ (20110323)).
 * To the extent that Dataform code corresponds to Database code,
 * certain copyrights on Database module may obtain.
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

require_once("$CFG->dirroot/mod/dataform/field/file/field_class.php");

class dataform_field_nanogong extends dataform_field_file {
    public $type = 'nanogong';

    /**
     *
     */
    public function display_edit(&$mform, $entry) {
        global $USER;

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

        if ($contentid) {
            $fileurl = new moodle_url("/pluginfile.php/{$this->df->context->id}/mod_dataform/content/$contentid/voicefile.spx");
            $paramfileurl = html_writer::empty_tag('param', array('name' => 'SoundFileURL',
                                                        'value' => $fileurl));
        } else {
            $paramfileurl = '';
        }

        // applet
        $applet = html_writer::tag('applet',
                                    $paramfileurl,
                                    array('archive' => new moodle_url('/mod/dataform/field/nanogong/applet/nanogong.jar'),
                                            'id' => "id_{$fieldname}_filemanager",
                                            'code' => 'gong.NanoGong',
                                            'width' => '180px',
                                            'height' => '40px'));
        
        $appletwrapper = html_writer::tag('div', $applet, array('class' => 'mdl-align'));
 
        $filename = 'voicefile';
        $fileuploaderphp = new moodle_url('/mod/dataform/field/nanogong/ngupload.php',
                                        array('elname' => "id_{$fieldname}_filemanager",
                                            'userid' => $USER->id,
                                            'itemid' => $draftitemid,
                                            'maxbytes' => $this->field->param1,
                                            'author' => $USER->firstname,
                                            'title' => $filename));
        $fileuploaderphp = htmlspecialchars_decode($fileuploaderphp);
        $submitbutton = html_writer::empty_tag('input', array('type' => 'button',
                                                                'value' => get_string('upload'),
                                                                'onclick' => "uploadNanogongRecording('id_{$fieldname}_filemanager'&#44;'{$fileuploaderphp}'&#44;'{$filename}')"));       
        
        $mform->addElement('html', html_writer::tag('div', $appletwrapper. $submitbutton, array('class' => 'mdl-align')));
        $mform->addElement('hidden', "{$fieldname}_filemanager", $draftitemid);

        // alt text
        $options = array();
        $mform->addElement('text', "{$fieldname}_alttext", get_string('alttext','dataformfield_nanogong'), $options);
        $mform->setDefault("{$fieldname}_alttext", s($content1));

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
            return new moodle_url("$path/$filename");
                    
        } else {
        
            $fileurl = new moodle_url("$path/$filename");
       
            $paramfileurl = html_writer::empty_tag('param', array('name' => 'SoundFileURL',
                                                            'value' => $fileurl));
            $paramaudiolevel = html_writer::empty_tag('param', array('name' => 'ShowAudioLevel',
                                                            'value' => 'true'));
            $paramtime = html_writer::empty_tag('param', array('name' => 'ShowTime',
                                                            'value' => 'true'));
            $paramrecordbutton = html_writer::empty_tag('param', array('name' => 'ShowRecordButton',
                                                            'value' => 'false'));

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
