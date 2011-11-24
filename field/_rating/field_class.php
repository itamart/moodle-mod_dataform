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
    }

    /**
     * returns an array of distinct content of the field
     */
    public function get_distinct_content($sortdir = 0) {
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

}
