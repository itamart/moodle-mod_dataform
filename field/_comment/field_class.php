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

    protected $_patterns = array(
        '##comments:count##',
        '##comments:view##',
        '##comments:viewurl##',
        '##comments:add##',
    );

    /**
     * 
     */
    public function patterns($tags = null, $entry = null, $edit = false, $enabled = false) {
        global $USER, $OUTPUT;

        // if no tags requested, return select menu
        if (is_null($tags)) {
            $patterns = array('comments' => array('comments' => array()));
                               
            // TODO use get strings
            foreach ($this->_patterns as $pattern) {
                $patterns['comments']['comments'][$pattern] = $pattern;
            }
            
        } else {
        
            $patterns = array();
            $commentsenabled = $this->df->data->comments;
            
            if ($entry->id > 0 and $commentsenabled) {            
                // no edit mode for this field so just return html
                foreach ($tags as $tag) {
                    switch($tag) {
                        case '##comments:count##':
                            $patterns[$tag] = array('html', $entry->comment->count);
                            break;
                            
                        case '##comments:view##':
                        case '##comments:viewurl##':
                            if (isset($entry->comment)) {
                                $comment = $entry->comment;
                                if ($comment->settings->permissions->viewall
                                    and $comment->settings->pluginpermissions->viewall) {

                                    $nonpopuplink = $rating->get_view_comments_url();
                                    $popuplink = $rating->get_view_comments_url(true);
                                    $popupaction = new popup_action('click', $popuplink, 'comments', array('height' => 400, 'width' => 600));
                                    
                                    if ($tag == '##comments:view##') {
                                        $patterns[$tag] = array('html', $OUTPUT->action_link($nonpopuplink, 'view all', $popupaction));
                                    } else {
                                        $patterns[$tag] = array('html', $popuplink);
                                    }
                                } else {
                                    $patterns[$tag] = '';
                                }
                            } else {
                                $patterns[$tag] = '';
                            }
                            break;
                            
                        case '##comments:add##':
                            $patterns[$tag] = array('html', $this->display_browse($entry));
                            break;
                    }
                }
                
            } else {
                foreach ($tags as $tag) {            
                    $patterns[$tag] = '';                    
                }                
            }                    
        }       
            
        return $patterns;
    }

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
/*
        global $DB, $CFG, $USER;

        if ($value) {
            if ($commentid = optional_param('comment_'. $entryid, 0, PARAM_INT)) {
                $comment = $DB->get_record('dataform_comments','id', $commentid);
                if ($comment->content != $value) {
                    $comment->content  = $value;
                    // TODO
                    //$comment->format   = $formadata->format;
                    $comment->modified = time();
                    return $DB->update_record('dataform_comments',$comment);
                } else {
                    return false;
                }

            // new comment
            } else {
                $comment = new object();
                $comment->userid   = $USER->id;
                $comment->created  = time();
                $comment->modified = time();
                $comment->content  = $value;
                $comment->entryid = $entryid;
                return $DB->insert_record('dataform_comments',$comment);
            }
        } else {
            return false;
        }
*/
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
     * returns an array of distinct content of the field
     */
    public function print_after_form() {
        $str = '';
/*
        if (can_use_richtext_editor()) {
            ob_start();
            use_html_editor('field_comment', '', 'edit-field_comment');
            $str = ob_get_contents();
            ob_end_clean();
        }
*/
        return $str;
    }

    /**
     *
     */
    public function export_text_supported() {
        return false;
    }

    /**
     *
     */
    public function import_text_supported() {
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


    /**
     *
     */
    public function display_edit(&$mform, $entry = null) {
    }

    /**
     *
     */
    public function display_browse($entry, $params = null) {
        global $CFG;

        $str = '';
        if (!empty($CFG->usecomments)) {
            require_once("$CFG->dirroot/comment/lib.php");
            $cmt = new object();
            $cmt->context = $this->df->context;
            $cmt->courseid  = $this->df->course->id;
            $cmt->cm      = $this->df->cm;
            $cmt->area    = 'dataform_entry';
            $cmt->itemid  = $entry->id;
            $cmt->showcount = true;
            $cmt->component = 'mod_dataform';
            $comment = new comment($cmt);
            $str = $comment->output(true);
        }

        return $str;
    }

}
