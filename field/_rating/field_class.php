<?php

/**
 * This file is part of the Dataform module for Moodle - http://moodle.org/.
 *
 * @package mod-dataform
 * @subpackage field-_rating
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

require_once("$CFG->dirroot/mod/dataform/field/field_class.php");

class dataform_field__rating extends dataform_field_base {

    const AGGREGATE_AVG = 1;
    const AGGREGATE_COUNT = 2;
    const AGGREGATE_MAX = 3;
    const AGGREGATE_MIN = 4;
    const AGGREGATE_SUM = 5;

    public $type = '_rating';

    protected $_patterns = array(
                        '##ratings:count##',
                        '##ratings:avg##',
                        '##ratings:max##',
                        '##ratings:min##',
                        '##ratings:sum##',
                        '##ratings:rate##',
                        '##ratings:view##',
                        '##ratings:viewurl##');


    /**
     * 
     */
    public function patterns_intersect($patterns) {
        return array_intersect($this->_patterns, $patterns);
    }

    /**
     * 
     */
    public function get_aggregations($patterns) {
        if ($aggregations = array_intersect($patterns, array(
                        self::AGGREGATE_AVG => '##ratings:avg##',
                        self::AGGREGATE_MAX => '##ratings:max##',
                        self::AGGREGATE_MIN => '##ratings:min##',
                        self::AGGREGATE_SUM => '##ratings:sum##'))) {
            return array_keys($aggregations);
        } else {
            return null;
        }
    }

    /**
     * 
     */
    public function get_scaleid($area) {
        if ($area == 'entry' and $this->df->data->rating) {
            return $this->df->data->rating;
        } else if ($area == 'activity' and $this->df->data->grade) {
            return $this->df->data->grade;
        }
        return 0;
    }

    /**
     * 
     */
    public function patterns($tags = null, $entry = null, $edit = false, $enabled = false) {
        global $USER, $OUTPUT;

        // if no tags requested, return select menu
        if (is_null($tags)) {
            $patterns = array('ratings' => array('ratings' => array()));
                               
            // TODO use get strings
            foreach ($this->_patterns as $pattern) {
                $patterns['ratings']['ratings'][$pattern] = $pattern;
            }
            
        } else {
        
            $patterns = array();
            $ratingenabled = $this->df->data->rating;
            
            if ($entry->id > 0 and $ratingenabled) {
            
                // no edit mode for this field so just return html
                foreach ($tags as $tag) {
                    switch($tag) {
                        case '##ratings:count##':
                            $patterns[$tag] = array('html', $entry->rating->count);
                            break;
                            
                        case '##ratings:avg##':
                            $patterns[$tag] = array('html', $entry->rating->aggregate[self::AGGREGATE_AVG]);
                            break;
                            
                        case '##ratings:max##':
                            $patterns[$tag] = array('html', $entry->rating->aggregate[self::AGGREGATE_MAX]);
                            break;
                            
                        case '##ratings:min##':
                            $patterns[$tag] = array('html', $entry->rating->aggregate[self::AGGREGATE_MIN]);
                            break;
                            
                        case '##ratings:sum##':
                            $patterns[$tag] = array('html', $entry->rating->aggregate[self::AGGREGATE_SUM]);
                            break;
                            
                        case '##ratings:view##':
                        case '##ratings:viewurl##':
                            if (isset($entry->rating)) {
                                $rating = $entry->rating;
                                if ($rating->settings->permissions->viewall
                                    and $rating->settings->pluginpermissions->viewall) {

                                    $nonpopuplink = $rating->get_view_ratings_url();
                                    $popuplink = $rating->get_view_ratings_url(true);
                                    $popupaction = new popup_action('click', $popuplink, 'ratings', array('height' => 400, 'width' => 600));
                                    
                                    if ($tag == '##ratings:view##') {
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
                            
                        case '##ratings:rate##':
                            if (isset($entry->rating)) {
                                $patterns[$tag] = array('html', $this->render_rating($entry->rating));
                            } else {
                                $patterns[$tag] = '';
                            }
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
        //return "(Select count(entryid) From mdl_dataform_ratings as cr Where cr.entryid = r.id)";
    }

    /**
     * 
     */
    public function update_content($entryid, array $values = null) {
        return true;
/*        global $DB, $USER;

        $updategrades = false;
        $userid = 0;
        
        // update existing rating
        if ($ratingid = optional_param('rating_'. $entryid, 0, PARAM_INT)) {
            $rating = $DB->get_record('dataform_ratings','id', $ratingid);
            if ($rating->grade != $value) {
                if ($value !== '') {
                        $rating->grade = $value;
                        $updategrades = $DB->update_record('dataform_ratings', $rating);
                } else {
                    $updategrades = delete_records('dataform_ratings', 'id', $ratingid);
                    // reset this user's grade
                    $userid = $DB->get_field('dataform_entries', 'userid', array('id' => $entryid));
                }
            }
    
        // add new rating
        } else {
            if ($value !== '') {
                $rating = new object();
                $rating->userid   = $USER->id;
                $rating->entryid = $entryid;
                $rating->grade  = $value;
                $updategrades = $DB->insert_record('dataform_ratings',$rating);
            }
        }
        // update gradebook
        if ($this->df->data->grade and $updategrades) {
            $this->df->data->cmidnumber = $this->df->cm->id;
            dataform_update_grades($this->df->data, $userid);
        }
        
        return $updategrades;
*/
    }
    
    /**
     * Delete all content associated with the field
     */
    public function delete_content($entryid = 0, $ratingid = 0) {
/*    
        if ($ratingid) {
            delete_records('dataform_ratings', 'id', $ratingid);
        } else if ($entryid) {

            delete_records('dataform_ratings', 'entryid', $entryid);
        }
        // update gradebook
        if ($this->df->data->grade) {
            $this->df->data->cmidnumber = $this->df->cm->id;
            dataform_update_grades($this->df->data);
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
    public function permissions($params) {
    }
     */

    /**
     *
     */
    public function validate($params) {
        global $DB, $USER;
        
        $data = $this->df->data;
        
        // if the supplied context doesnt match the item's context
        if ($params['context']->id != $this->df->context->id) {
            throw new rating_exception('invalidcontext');
        }

        if ($data->approval and !$data->approved) {
            //database requires approval but this item isnt approved
            throw new rating_exception('nopermissiontorate');
        }

        // Check the ratingarea is entry or activity
        if ($params['ratingarea'] != 'entry' and $params['ratingarea'] != 'activity') {
            throw new rating_exception('invalidratingarea');
        }

        // vaildate activity scale and rating range
        if ($params['ratingarea'] == 'activity') {
            if ($params['scaleid'] != $data->grade) {
                throw new rating_exception('invalidscaleid');
            }
            
            // upper limit
            if ($data->grade < 0) {
                //its a custom scale
                $scalerecord = $DB->get_record('scale', array('id' => -$data->grade));
                if ($scalerecord) {
                    $scalearray = explode(',', $scalerecord->scale);
                    if ($params['rating'] > count($scalearray)) {
                        throw new rating_exception('invalidnum');
                    }
                } else {
                    throw new rating_exception('invalidscaleid');
                }
            } else if ($params['rating'] > $data->grade) {
                //if its numeric and submitted rating is above maximum
                throw new rating_exception('invalidnum');
            }
            
        }

        // vaildate entry scale and rating range
        if ($params['ratingarea'] == 'entry') {
            if ($params['scaleid'] != $data->rating) {
                throw new rating_exception('invalidscaleid');
            }
            
            // upper limit
            if ($data->rating < 0) {
                //its a custom scale
                $scalerecord = $DB->get_record('scale', array('id' => -$data->rating));
                if ($scalerecord) {
                    $scalearray = explode(',', $scalerecord->scale);
                    if ($params['rating'] > count($scalearray)) {
                        throw new rating_exception('invalidnum');
                    }
                } else {
                    throw new rating_exception('invalidscaleid');
                }
            } else if ($params['rating'] > $data->rating) {
                //if its numeric and submitted rating is above maximum
                throw new rating_exception('invalidnum');
            }
            
        }

        // lower limit
        if ($params['rating'] < 0  and $params['rating'] != RATING_UNSET_RATING) {
            throw new rating_exception('invalidnum');
        }

        // check the item we're rating was created in the assessable time window
        //if (!empty($info->assesstimestart) && !empty($info->assesstimefinish)) {
        //    if ($info->timecreated < $info->assesstimestart || $info->timecreated > $info->assesstimefinish) {
        //        throw new rating_exception('notavailable');
        //    }
        //}

        // Make sure groups allow this user to see the item they're rating
        $groupid = $this->df->currentgroup;
        if ($groupid > 0 and $groupmode = groups_get_activity_groupmode($this->df->cm, $this->df->course)) {   // Groups are being used
            if (!groups_group_exists($groupid)) { // Can't find group
                throw new rating_exception('cannotfindgroup');//something is wrong
            }

            if (!groups_is_member($groupid) and !has_capability('moodle/site:accessallgroups', $this->df->context)) {
                // do not allow rating of posts from other groups when in SEPARATEGROUPS or VISIBLEGROUPS
                throw new rating_exception('notmemberofgroup');
            }
        }

        return true;
    }

    /**
     * 
     */
    function render_rating(rating $rating) {
        global $CFG, $USER, $PAGE;
/*
        if ($rating->settings->aggregationmethod == RATING_AGGREGATE_NONE) {
            return null;//ratings are turned off
        }
*/
        $rm = new dataform_rating_manager();
        // Initialise the JavaScript so ratings can be done by AJAX.
        $rm->initialise_rating_javascript($PAGE);

        $strrate = get_string("rate", "rating");
        $ratinghtml = ''; //the string we'll return

        // hack to work around the js updating imposed text
        $ratinghtml .= html_writer::tag('span', '', array('id' => "ratingaggregate{$rating->itemid}",
                                                            'style' => 'display:none;'));
        $ratinghtml .= html_writer::tag('span', '', array('id' => "ratingcount{$rating->itemid}",
                                                            'style' => 'display:none;'));
        
        $formstart = null;
        // if the item doesn't belong to the current user, the user has permission to rate
        // and we're within the assessable period
        if ($rating->user_can_rate() or has_capability('mod/dataform:manageratings', $this->df->context)) {

            $rateurl = $rating->get_rate_url();
            $inputs = $rateurl->params();

            //start the rating form
            $formattrs = array(
                'id'     => "postrating{$rating->itemid}",
                'class'  => 'postratingform',
                'method' => 'post',
                'action' => $rateurl->out_omit_querystring()
            );
            $formstart  = html_writer::start_tag('form', $formattrs);
            $formstart .= html_writer::start_tag('div', array('class' => 'ratingform'));

            // add the hidden inputs
            foreach ($inputs as $name => $value) {
                $attributes = array('type' => 'hidden', 'class' => 'ratinginput', 'name' => $name, 'value' => $value);
                $formstart .= html_writer::empty_tag('input', $attributes);
            }


            $ratinghtml = $formstart.$ratinghtml;

            $scalearray = array(RATING_UNSET_RATING => $strrate.'...') + $rating->settings->scale->scaleitems;
            $scaleattrs = array('class'=>'postratingmenu ratinginput','id'=>'menurating'.$rating->itemid);
            $ratinghtml .= html_writer::select($scalearray, 'rating', $rating->rating, false, $scaleattrs);

            //output submit button
            $ratinghtml .= html_writer::start_tag('span', array('class'=>"ratingsubmit"));

            $attributes = array('type' => 'submit', 'class' => 'postratingmenusubmit', 'id' => 'postratingsubmit'.$rating->itemid, 'value' => s(get_string('rate', 'rating')));
            $ratinghtml .= html_writer::empty_tag('input', $attributes);

            if (!$rating->settings->scale->isnumeric) {
                $ratinghtml .= $this->help_icon_scale($rating->settings->scale->courseid, $rating->settings->scale);
            }
            $ratinghtml .= html_writer::end_tag('span');
            $ratinghtml .= html_writer::end_tag('div');
            $ratinghtml .= html_writer::end_tag('form');
        }

        return $ratinghtml;
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
        return '';
    }
    
}
