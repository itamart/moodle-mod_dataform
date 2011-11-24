<?php

/**
 * This file is part of the Dataform module for Moodle - http://moodle.org/.
 *
 * @package mod-dataform
 * @subpackage field-_comment
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
class mod_dataform_field__comment_patterns extends mod_dataform_field_patterns {

    /**
     * 
     */
    public function get_replacements($tags = null, $entry = null, $edit = false, $editable = false) {
        $field = $this->_field;

        // no edit mode
        $replacements = array();

        $commentsenabled = $this->_field->df()->data->comments;       
        // no edit mode for this field so just return html
        foreach ($tags as $tag) {
            if ($entry->id > 0 and $commentsenabled) {            
                switch($tag) {
                    case '##comments:count##': $str = ''; break;
                    case '##comments:view##': $str = ''; break;
                    case '##comments:viewurl##': $str = ''; break;
                    case '##comments:add##': $str = $this->display_browse($entry); break;
                    default: $str = '';
                }
                $replacements[$tag] = array('html', $str);

            } else {
                $replacements[$tag] = '';                    
            }            
        }                    

        return $replacements;
    }

    /**
     *
     */
    public function display_browse($entry) {
        global $CFG;

        $df = $this->_field->df();
        $str = '';
        if (!empty($CFG->usecomments)) {
            require_once("$CFG->dirroot/comment/lib.php");
            $cmt = new object();
            $cmt->context = $df->context;
            $cmt->courseid  = $df->course->id;
            $cmt->cm      = $df->cm;
            $cmt->area    = 'dataform_entry';
            $cmt->itemid  = $entry->id;
            $cmt->showcount = true;
            $cmt->component = 'mod_dataform';
            $comment = new comment($cmt);
            $str = $comment->output(true);
        }

        return $str;
    }

    /**
     * Array of patterns this field supports 
     */
    protected function patterns() {
        $cat = get_string('comments', 'dataform');

        $patterns = array();
        $patterns['##comments:count##'] = array(true, $cat);
        $patterns['##comments:view##'] = array(true, $cat);
        $patterns['##comments:viewurl##'] = array(true, $cat);
        $patterns['##comments:add##'] = array(true, $cat);

        return $patterns; 
    }
}
