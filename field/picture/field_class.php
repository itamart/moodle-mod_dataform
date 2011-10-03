<?php

/**
 * This file is part of the Dataform module for Moodle - http://moodle.org/.
 *
 * @package mod-dataform
 * @subpackage field-picture
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

require_once("$CFG->dirroot/mod/dataform/field/file/field_class.php");

class dataform_field_picture extends dataform_field_file {
    public $type = 'picture';

    /**
     *
     */
    public function patterns($tags = null, $entry = null, $edit = false, $editable = false) {
        $patterns = parent::patterns($tags, $entry, $edit, $editable);

        $fieldname =  $this->field->name;
        $extrapatterns = array("[[{$fieldname}:linked]]",
                                "[[{$fieldname}:tn]]",
                                "[[{$fieldname}:tn-url]]",
                                "[[{$fieldname}:tn-linked]]");

        // if no tags requested, return select menu
        if (is_null($tags)) {
            foreach ($extrapatterns as $pattern) {
                $patterns['fields']['fields'][$pattern] = $pattern;
            }

        } else {

            foreach ($tags as $tag) {
                if ($tag == "[[{$fieldname}:tn-url]]") {
                    // no edit for the url so just output
                    $patterns["[[{$fieldname}:tn-url]]"] = array('html', $this->display_browse($entry, array('tn' => 1, 'url' => 1)));
                } else if ($edit) {
                    if (in_array($tag, $extrapatterns)) {
                        $patterns[$tag] = array('', array(array($this,'display_edit'), array($entry)));
                    }
                } else {
                    if ($tag == "[[{$fieldname}:linked]]") {
                        $patterns["[[{$fieldname}:linked]]"] = array('html', $this->display_browse($entry, array('linked' => 1)));
                    } else if ($tag == "[[{$fieldname}:tn]]") {
                        $patterns["[[{$fieldname}:tn]]"] = array('html', $this->display_browse($entry, array('tn' => 1)));
                    } else if ($tag == "[[{$fieldname}:tn-linked]]") {
                        $patterns["[[{$fieldname}:tn-linked]]"] = array('html', $this->display_browse($entry, array('tn' => 1, 'linked' => 1)));
                    }
                }
            }
        }

        return $patterns;
    }

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

    /**
     *
     */
    protected function display_file($file, $path, $altname, $params = null) {

        if ($file->is_valid_image()) {
            $filename = $file->get_filename();
            $imgattr = array('style' => array());

            if (!empty($params['tn'])) {
                // decline if the file is not really a thumbnail
                if (strpos($filename, 'thumb_') === false) {
                    return '';
                }
            } else {
                // decline if the file is a thumbnail
                if (strpos($filename, 'thumb_') !== false) {
                    return '';
                }

                // the picture's display dimension may be set in the field
                if ($this->field->param4) {
                    $imgattr['style'][] = 'width:'. s($this->field->param4). s($this->field->param6);
                }
                if ($this->field->param5) {
                    $imgattr['style'][] = 'height:'. s($this->field->param5). s($this->field->param6);
                }
            }


            $src = new moodle_url("$path/$filename");

            if (!empty($params['url'])) {
                return $src;
            }

            $imgattr['src'] = $src;
            //$imgattr['alt'] = $altname;
            //$imgattr['title'] = $altname;
            $imgattr['style'][] = "border:0px";
            $imgattr['style'] = implode(';', $imgattr['style']);

            $str = html_writer::empty_tag('img', $imgattr);

            if (!empty($params['linked'])) {
                return html_writer::link($src, $str);
            } else {
                return $str;
            }
        } else {
            return '';
        }
    }

}
