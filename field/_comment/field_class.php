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
 * @subpackage _comment
 * @copyright 2012 Itamar Tzadok
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once("$CFG->dirroot/mod/dataform/field/field_class.php");

class dataformfield__comment extends dataformfield_no_content {

    public $type = '_comment';

    const _COMMENT = 'comment';

    /**
     *
     */
    public static function is_internal() {
        true;
    }
    
    /**
     *
     */
    public static function get_field_objects($dataid) {
        $fieldobjects = array();
        
        $fieldobjects[self::_COMMENT] = (object) array('id' => self::_COMMENT, 'dataid' => $dataid, 'type' => '_comment', 'name' => get_string('comments', 'dataform'), 'description' => '', 'visible' => 2, 'internalname' => 'comments');

        return $fieldobjects;
    }

    /**
     * TODO: use join?
     */
    public function get_sort_sql() {
        return '';
        //return "(Select count(entryid) From mdl_dataform_comments as cm Where cm.entryid = e.id)";
    }

    /**
     *
     */
    public function permissions($params) {
        global $USER;

        if (has_capability('mod/dataform:managecomments', $this->df->context)
                    or ($params->commentarea == 'activity' and $params->itemid == $USER->id)
                    or ($params->commentarea == 'entry')) {
            return array('post'=>true, 'view'=>true);
        }
        return array('post'=>false, 'view'=>false);
    }

    /**
     *
     */
    public function validation($params) {
        global $DB, $USER;

        // Validate context
        if (empty($params->context) or $params->context->id != $this->df->context->id) {
            throw new comment_exception('invalidcontextid', 'dataform');
        }

        // Validate course
        if ($params->courseid != $this->df->course->id) {
            throw new comment_exception('invalidcourseid', 'dataform');
        }

        // Validate cm
        if ($params->cm->id != $this->df->cm->id) {
            throw new comment_exception('invalidcmid', 'dataform');
        }

        // validate comment area
        if ($params->commentarea != 'entry' and $params->commentarea != 'activity') {
            throw new comment_exception('invalidcommentarea');
        }

        // validation for non-comment-managers
        if (!has_capability('mod/dataform:managecomments', $this->df->context)) {
        
            // non-comment-managers can add/view comments on their own entries
            // but require df->data->comments for add/view on other entries (excluding grading entries)

            // comments in the activity level are associated (itemid) with participants
            if ($params->commentarea == 'activity') {
                if ($params->itemid != $USER->id) {
                    throw new comment_exception('invalidcommentitemid');
                }
            }

            if ($params->commentarea == 'entry') {

                // check if comments enabled
                //if (!$this->df->data->comments) {
                //    throw new comment_exception('commentsoff', 'dataform');
                //}

                // validate entry
                if (!$entry = $DB->get_record('dataform_entries', array('id' => $params->itemid))) {
                    throw new comment_exception('invalidcommentitemid');
                }

                //check if approved
                //if ($this->df->data->approval
                //            and !$entry->approved
                //            and !($entry->userid === $USER->id)
                //            and !has_capability('mod/dataform:approve', $context)) {
                //    throw new comment_exception('notapproved', 'dataform');
                //}

                // group access
                if ($entry->groupid) {
                    $groupmode = groups_get_activity_groupmode($this->df->cm, $this->df->course);
                    if ($groupmode == SEPARATEGROUPS and !has_capability('moodle/site:accessallgroups', $this->df->context)) {
                        if (!groups_is_member($entry->groupid)) {
                            throw new comment_exception('notmemberofgroup');
                        }
                    }
                }
            }
        }

        // validation for comment deletion
        if (!empty($params->commentid)) {
            if ($comment = $DB->get_record('comments', array('id' => $params->commentid))) {
                if ($comment->commentarea != 'entry' and $comment->commentarea != 'activity') {
                    throw new comment_exception('invalidcommentarea');
                }
                if ($comment->contextid != $params->context->id) {
                    throw new comment_exception('invalidcontext');
                }
                if ($comment->itemid != $params->itemid) {
                    throw new comment_exception('invalidcommentitemid');
                }
            } else {
                throw new comment_exception('invalidcommentid');
            }
        }

        return true;
    }
}
