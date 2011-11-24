<?php

/**
 * This file is part of the Dataform module for Moodle
 *
 * @copyright 2011 Moodle contributors
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package mod-dataform
 *
 */

require_once("$CFG->dirroot/mod/dataform/field/field_class.php");

class dataform_field__comment extends dataform_field_base {

    public $type = '_comment';

    /**
     * TODO
     */
    public function get_search_sql($value = '') {
        return array(" ", array());
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
    public function update_content($entryid, array $values = null) {
        return true;
    }

    /**
     * Delete all content associated with the field
     */
    public function delete_content($entryid = 0, $commentid = 0) {
/*
        if ($commentid) {
            delete_records('dataform_comments', 'id', $commentid);
        } else if ($entryid) {
            delete_records('dataform_comments', 'entryid', $entryid);
        }
*/
    }

    /**
     * returns an array of distinct content of the field
     */
    public function get_distinct_content($sortdir = 0) {
        return false;
    }

    /**
     *
     */
    public function permissions($params) {
        global $USER;

        if (has_capability('mod/dataform:managecomments', $this->df->context)
//                    or ($params->commentarea == 'dataform_activity' and $params->itemid == $USER->id)
                    or ($params->commentarea == 'dataform_entry' and $this->df->data->comments)) {
            return array('post'=>true, 'view'=>true);
        }
        return array('post'=>false, 'view'=>false);
    }

    /**
     *
     */
    public function validate($params) {
        global $DB, $USER;

        // validate params
        if ($params->context->id != $this->df->context->id
                or $params->courseid != $this->df->course->id
                or $params->cm->id != $this->df->cm->id) {
            throw new comment_exception('invalidid', 'dataform');
        }

        // validate comment area
        if ($params->commentarea != 'dataform_entry'
                or $params->commentarea != 'dataform_activity') {
            throw new comment_exception('invalidcommentarea');
        }

        // validation for non-comment-managers
        if (!has_capability('mod/dataform:managecomments', $this->df->context)) {
        
            // non-comment-managers can add/view comments on their own entries
            // but require df->data->comments for add/view on other entries (excluding grading entries)

            // comments in the activity level are associated (itemid) with participants
            //if ($params->commentarea == 'dataform_activity') {
            //    if ($params->itemid != $USER->id) {
            //        throw new comment_exception('invalidcommentitemid');
            //    }
            //}

            if ($params->commentarea == 'dataform_entry') {

                // check if comments enabled
                if (!$this->df->data->comments) {
                    throw new comment_exception('commentsoff', 'dataform');
                }

                // validate entry
                if (!$entry = $DB->get_record('dataform_entries', array('id' => $params->itemid))) {
                    throw new comment_exception('invalidcommentitemid');
                }

                //check if approved
                if ($this->df->data->approval
                            and !$entry->approved
                            and !($entry->userid === $USER->id)
                            and !has_capability('mod/dataform:approve', $context)) {
                    throw new comment_exception('notapproved', 'dataform');
                }

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
                if ($comment->commentarea != 'dataform_entry') { // or $comment->commentarea != 'dataform_activity') {
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
