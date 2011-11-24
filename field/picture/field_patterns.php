<?php

/**
 * This file is part of the Dataform module for Moodle - http://moodle.org/.
 *
 * @package mod-dataform
 * @package field-picture
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

require_once("$CFG->dirroot/mod/dataform/field/file/field_patterns.php");

/**
 *
 */
class mod_dataform_field_picture_patterns extends mod_dataform_field_file_patterns {

    /**
     * 
     */
    public function get_replacements($tags = null, $entry = null, $edit = false, $editable = false) {
        $field = $this->_field;
        $fieldname = $field->name();

        // there is only one possible tag here so no check
        $replacements = parent::get_replacements($tags, $entry, $edit, $editable);

        foreach ($tags as $tag) {
            if ($tag == "[[$fieldname:tn-url]]") {
                // no edit for the url so just output
                $replacements["[[$fieldname:tn-url]]"] = array('html', $this->display_browse($entry, array('tn' => 1, 'url' => 1)));
            } else if ($edit) {
                if (in_array($tag, array_keys($this->patterns()))) {
                    $replacements[$tag] = array('', array(array($this,'display_edit'), array($entry)));
                }
            } else {
                switch ($tag) {
                    case "[[$fieldname:linked]]":
                        $replacements[$tag] = array('html', $this->display_browse($entry, array('linked' => 1)));
                        break;
                    case "[[$fieldname:base64]]":
                        $replacements[$tag] = array('html', $this->display_browse($entry, array('base64' => 1)));
                        break;
                    case "[[$fieldname:tn]]":
                        $replacements["[[{$fieldname}:tn]]"] = array('html', $this->display_browse($entry, array('tn' => 1)));
                        break;
                    case "[[$fieldname:tn-linked]]":
                        $replacements["[[$fieldname:tn-linked]]"] = array('html', $this->display_browse($entry, array('tn' => 1, 'linked' => 1)));
                        break;
                    case "[[$fieldname:tn-base64]]":
                        $replacements[$tag] = array('html', $this->display_browse($entry, array('tn' => 1, 'base64' => 1)));
                        break;
                }
            }
        }

        return $replacements;
    }

    /**
     * 
     */
    public function pluginfile_patterns() {
        $fieldname =  $this->_field->name();
        return array(
                    "[[{$fieldname}]]",
                    "[[{$fieldname}:linked]]",
                    "[[{$fieldname}:tn-linked]]",
                    );
    }

    /**
     *
     */
    protected function display_file($file, $path, $altname, $params = null) {
        $field = $this->_field;
        
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
                if ($field->get('param4')) {
                    $imgattr['style'][] = 'width:'. s($field->get('param4')). s($field->get('param6'));
                }
                if ($field->get('param5')) {
                    $imgattr['style'][] = 'height:'. s($field->get('param5')). s($field->get('param6'));
                }
            }

            // calculate src: either moodle url or base64
            if (!empty($params['base64'])) {
                $src = 'data:'. $file->get_mimetype(). ';base64,'. base64_encode($file->get_content());
            } else {
                $src = new moodle_url("$path/$filename");
                
                // for url request return it here
                if (!empty($params['url'])) {
                    return $src;
                }
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

    /**
     * Array of patterns this field supports 
     */
    protected function patterns() {
        $fieldname = $this->_field->name();

        $patterns = parent::patterns();
        $patterns["[[{$fieldname}:linked]]"] = array(false);
        $patterns["[[{$fieldname}:base64]]"] = array(false);
        $patterns["[[{$fieldname}:tn]]"] = array(false);
        $patterns["[[{$fieldname}:tn-url]]"] = array(false);
        $patterns["[[{$fieldname}:tn-linked]]"] = array(false);
        $patterns["[[{$fieldname}:tn-base64]]"] = array(false);

        return $patterns; 
    }
}
