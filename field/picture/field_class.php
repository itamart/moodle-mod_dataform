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
 * @subpackage picture
 * @copyright 2011 Itamar Tzadok
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once("$CFG->dirroot/mod/dataform/field/file/field_class.php");

class dataformfield_picture extends dataformfield_file {
    public $type = 'picture';

    /**
     *
     */
    public function update_field($fromform = null) {
        global $DB, $OUTPUT;

        // Get the old field data so that we can check whether the thumbnail dimensions have changed
        $oldfield = $this->field;
        if (!parent::update_field($fromform)) {
            echo $OUTPUT->notification('updating of new field failed!');
            return false;
        }
        // Have the dimensions changed?
        if ($oldfield and
                    ($updatefile = ($oldfield->param7 != $this->field->param7 or $oldfield->param8 != $this->field->param8)
                    or $updatethumb = ($oldfield->param9 != $this->field->param9 or $oldfield->param10 != $this->field->param10))) {
            // Check through all existing records and update the thumbnail
            if ($contents = $DB->get_records('dataform_contents', array('fieldid' => $this->field->id))) {
                if (count($contents) > 20) {
                    echo $OUTPUT->notification(get_string('resizingimages', 'dataformfield_picture'), 'notifysuccess');
                    echo "\n\n";
                    // To make sure that ob_flush() has the desired effect
                    ob_flush();
                }
                foreach ($contents as $content) {
                    @set_time_limit(300);
                    // Might be slow!
                    $this->update_content_files($content->id, array('updatefile' => $updatefile, 'updatethumb' => $updatethumb));
                }
            }
        }
        return true;
    }

    /**
     * (Re)generate pic and thumbnail images according to the dimensions specified in the field settings.
     */
    protected function update_content_files($contentid, $params = null) {

        $updatefile = isset($params['updatefile']) ? $params['updatefile'] : true;
        $updatethumb = isset($params['updatethumb']) ? $params['updatethumb'] : true;

        $fs = get_file_storage();
        if (!$files = $fs->get_area_files($this->df->context->id, 'mod_dataform', 'content', $contentid)) {
            return;
        }

        // update dimensions and regenerate thumbs
        foreach ($files as $file) {
            
            if ($file->is_valid_image() and strpos($file->get_filename(), 'thumb_') === false) {
                // original first
                if ($updatefile) {
                    $maxwidth  = !empty($this->field->param7)?$this->field->param7:'';
                    $maxheight = !empty($this->field->param8)?$this->field->param8:'';

                    // If either width or height try to (re)generate
                    if ($maxwidth or $maxheight) {
                        // this may fail for various reasons
                        try {
                            $fs->convert_image($file, $file, $maxwidth, $maxheight, true);
                        } catch (Exception $e) {
                            return false;
                        }
                    }
                }

                // thumbnail next
                if ($updatethumb) {
                    $thumbwidth  = !empty($this->field->param9)?$this->field->param9:'';
                    $thumbheight = !empty($this->field->param10)?$this->field->param10:'';
                    $thumbname = 'thumb_'.$file->get_filename();

                    if ($thumbfile = $fs->get_file($this->df->context->id, 'mod_dataform', 'content', $contentid, '/', $thumbname)) {
                        $thumbfile->delete();
                    }

                    // If either width or height try to (re)generate, otherwise delete what exists
                    if ($thumbwidth or $thumbheight) {

                        $file_record = array('contextid'=>$this->df->context->id, 'component'=>'mod_dataform', 'filearea'=>'content',
                                             'itemid'=>$contentid, 'filepath'=> '/',
                                             'filename'=>$thumbname, 'userid'=>$file->get_userid());

                        try {
                            $fs->convert_image($file_record, $file, $thumbwidth, $thumbheight, true);
                        } catch (Exception $e) {
                            return false;
                        }
                    }
                }
            }
        }
        return true;
    }

}
