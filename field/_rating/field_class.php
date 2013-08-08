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
 * @subpackage _rating
 * @copyright 2012 Itamar Tzadok
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__). '/../field_class.php');

class dataformfield__rating extends dataformfield_no_content {

    public $type = '_rating';

    const AGGREGATE_AVG = 1;
    const AGGREGATE_COUNT = 2;
    const AGGREGATE_MAX = 3;
    const AGGREGATE_MIN = 4;
    const AGGREGATE_SUM = 5;

    const _RATING = 'rating';
    const _RATINGAVG = 'ratingavg';
    const _RATINGCOUNT = 'ratingcount';
    const _RATINGMAX = 'ratingmax';
    const _RATINGMIN = 'ratingmin';
    const _RATINGSUM = 'ratingsum';

    /**
     *
     */
    public static function is_internal() {
        true;
    }
    
    /**
     * Overriding parent to indicate that this field provides join sql
     *
     * @return bool
     */
    public function is_joined() {
        return true;
    }
    
    /**
     *
     */
    public static function get_field_objects($dataid) {
        $fieldobjects = array();
        
        $fieldobjects[self::_RATING] = (object) array('id' => self::_RATING, 'dataid' => $dataid, 'type' => '_rating', 'name' => get_string('ratings', 'dataform'), 'description' => '', 'visible' => 2, 'internalname' => 'ratings');

        $fieldobjects[self::_RATINGAVG] = (object) array('id' => self::_RATINGAVG, 'dataid' => $dataid, 'type' => '_rating', 'name' => get_string('ratingsavg', 'dataform'), 'description' => '', 'visible' => 2, 'internalname' => 'avgratings');

        $fieldobjects[self::_RATINGCOUNT] = (object) array('id' => self::_RATINGCOUNT, 'dataid' => $dataid, 'type' => '_rating', 'name' => get_string('ratingscount', 'dataform'), 'description' => '', 'visible' => 2, 'internalname' => 'countratings');

        $fieldobjects[self::_RATINGMAX] = (object) array('id' => self::_RATINGMAX, 'dataid' => $dataid, 'type' => '_rating', 'name' => get_string('ratingsmax', 'dataform'), 'description' => '', 'visible' => 2, 'internalname' => 'maxratings');

        $fieldobjects[self::_RATINGMIN] = (object) array('id' => self::_RATINGMIN, 'dataid' => $dataid, 'type' => '_rating', 'name' => get_string('ratingsmin', 'dataform'), 'description' => '', 'visible' => 2, 'internalname' => 'minratings');

        $fieldobjects[self::_RATINGSUM] = (object) array('id' => self::_RATINGSUM, 'dataid' => $dataid, 'type' => '_rating', 'name' => get_string('ratingssum', 'dataform'), 'description' => '', 'visible' => 2, 'internalname' => 'sumratings');

        return $fieldobjects;
    }

    /**
     *
     */
    public function get_select_sql() {
        return ' er.itemid, er.component, er.ratingarea, er.contextid,
                er.numratings, er.avgratings, er.sumratings, er.maxratings, er.minratings, 
                er.ratingid, er.ratinguserid, er.scaleid, er.usersrating ';
    }

    /**
     *
     */
    protected function get_sql_compare_text($column = 'content') {
        return $this->get_sort_sql();
    }

    /**
     *
     */
    public function get_sort_sql() {
        $internalname = $this->field->internalname;
        if ($internalname == 'ratings') {
            return "er.usersrating";
        } else if ($internalname == 'countratings') {
            return "er.numratings";
        } else {
            return "er.$internalname";
        }
    }

    /**
     *
     */
    public function get_join_sql() {
        global $USER;

        $params = array();
        $params['rcontextid'] = $this->df()->context->id;
        $params['ruserid']    = $USER->id;
        $params['rcomponent'] = 'mod_dataform';
        $params['ratingarea'] = 'entry';
    
        $sql = "LEFT JOIN 
                (SELECT r.itemid, r.component, r.ratingarea, r.contextid,
                           COUNT(r.rating) AS numratings,
                           AVG(r.rating) AS avgratings,
                           SUM(r.rating) AS sumratings,
                           MAX(r.rating) AS maxratings,
                           MIN(r.rating) AS minratings,
                           ur.id as ratingid, ur.userid as ratinguserid, ur.scaleid, ur.rating AS usersrating
                    FROM {rating} r
                            LEFT JOIN {rating} ur ON ur.contextid = r.contextid
                                                    AND ur.itemid = r.itemid
                                                    AND ur.component = r.component
                                                    AND ur.ratingarea = r.ratingarea
                                                    AND ur.userid = :ruserid
                    WHERE r.contextid = :rcontextid 
                            AND r.component = :rcomponent
                            AND r.ratingarea = :ratingarea
                    GROUP BY r.itemid, r.component, r.ratingarea, r.contextid, ratingid, ur.userid, ur.scaleid
                    ORDER BY r.itemid) AS er ON er.itemid = e.id ";
        return array($sql, $params);
    }

    /**
     *
    public function permissions($params) {
    }
     */

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
    public function validation($params) {
        global $DB, $USER;
        
        // Check the component is mod_dataform
        if ($params['component'] != 'mod_dataform') {
            throw new rating_exception('invalidcomponent');
        }

        // you can't rate your own entries unless you can manage ratings
        if (!has_capability('mod/dataform:manageratings', $params['context']) and $params['rateduserid'] == $USER->id) {
            throw new rating_exception('nopermissiontorate');
        }

        // if the supplied context doesnt match the item's context
        if ($params['context']->id != $this->df->context->id) {
            throw new rating_exception('invalidcontext');
        }

        // Check the ratingarea is entry or activity
        if ($params['ratingarea'] != 'entry' and $params['ratingarea'] != 'activity') {
            throw new rating_exception('invalidratingarea');
        }

        $data = $this->df->data;
        
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
        if ($groupid > 0 and $groupmode = groups_get_activity_groupmode($this->df->cm, $this->df->course)) {  
            // Groups are being used
            if (!groups_group_exists($groupid)) {
                // Can't find group
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
