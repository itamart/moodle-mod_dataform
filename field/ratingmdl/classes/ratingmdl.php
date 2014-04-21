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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * @package dataformfield
 * @subpackage ratingmdl
 * @copyright 2013 Itamar Tzadok
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__. '/../ratinglib.php');

class dataformfield_ratingmdl_ratingmdl extends mod_dataform\pluginbase\dataformfield_nocontent implements mod_dataform\interfaces\grading {
    protected $_scaleid = 0;
    protected $_allratings = null;

    const AGGREGATE_AVG = 1;
    const AGGREGATE_COUNT = 2;
    const AGGREGATE_MAX = 3;
    const AGGREGATE_MIN = 4;
    const AGGREGATE_SUM = 5;

    public function __construct($field) {
        parent::__construct($field);

        $this->_scaleid = $this->param1 ? $this->param1 : 0;
    }

    /**
     *
     */
    public function permissions() {
        return array(
            'view'    => true,
            'viewany' => true,
            'viewall' => true,
            'rate'    => true
        );
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

        $ownentry = ($params['rateduserid'] == $USER->id);
        if ($ownentry) {
            // You can't rate your own entries unless you have the capability
            if (!has_capability('dataformfield/ratingmdl:ownrate', $params['context'])) {
                throw new rating_exception('nopermissiontorate');
            }
        } else {
            // You can't rate other entries unless you have the capability
            if (!has_capability('dataformfield/ratingmdl:anyrate', $params['context'])) {
                throw new rating_exception('nopermissiontorate');
            }
        }

        // If the supplied context doesnt match the item's context
        if ($params['context']->id != $this->df->context->id) {
            throw new rating_exception('invalidcontext');
        }

        // Check the ratingarea is entry or activity
        if ($params['ratingarea'] != $this->name) {
            throw new rating_exception('invalidratingarea');
        }

        // Vaildate entry scale and rating range
        if ($params['scaleid'] != $this->_scaleid) {
            throw new rating_exception('invalidscaleid');
        }

        // Upper limit
        if ($this->_scaleid < 0) {
            // Its a custom scale
            $scalerecord = $DB->get_record('scale', array('id' => -$this->_scaleid));
            if ($scalerecord) {
                $scalearray = explode(',', $scalerecord->scale);
                if ($params['rating'] > count($scalearray)) {
                    throw new rating_exception('invalidnum');
                }
            } else {
                throw new rating_exception('invalidscaleid');
            }
        } else if ($params['rating'] > $this->_scaleid) {
            // If its numeric and submitted rating is above maximum
            throw new rating_exception('invalidnum');
        }

        // Lower limit
        if ($params['rating'] < 0  and $params['rating'] != RATING_UNSET_RATING) {
            throw new rating_exception('invalidnum');
        }

        // Check the item we're rating was created in the assessable time window
        // If (!empty($info->assesstimestart) && !empty($info->assesstimefinish)) {
        // If ($info->timecreated < $info->assesstimestart || $info->timecreated > $info->assesstimefinish) {
        // Throw new rating_exception('notavailable');
        //    }
        // }

        // Make sure groups allow this user to see the item they're rating
        $groupid = $this->df->currentgroup;
        if ($groupid > 0 and $groupmode = groups_get_activity_groupmode($this->df->cm, $this->df->course)) {
            // Groups are being used
            if (!groups_group_exists($groupid)) {
                // Can't find group
                throw new rating_exception('cannotfindgroup');
            }

            if (!groups_is_member($groupid) and !has_capability('moodle/site:accessallgroups', $this->df->context)) {
                // Do not allow rating of posts from other groups when in SEPARATEGROUPS or VISIBLEGROUPS
                throw new rating_exception('notmemberofgroup');
            }
        }

        return true;
    }

    // SQL MANAGEMENT
    /**
     * Whether this field content resides in dataform_contents
     *
     * @return bool
     */
    public function is_dataform_content() {
        return false;
    }

    /**
     * Whether this field provides join sql for fetching content
     *
     * @return bool
     */
    public function is_joined() {
        return true;
    }

    /**
     *
     */
    public function get_select_sql() {
        $alias = $this->get_sql_alias();

        $elements = array(
            'itemid',
            'component',
            'ratingarea',
            'contextid',
            'numratings',
            'avgratings',
            'sumratings',
            'maxratings',
            'minratings',
            'ratingid',
            'ratinguserid',
            'scaleid',
            'usersrating'
        );

        $aliasedelements = array();
        foreach ($elements as $element) {
            $aliasedelements[] = "$alias.$element AS {$alias}_$element";
        }
        $selectsql = implode(',', $aliasedelements);
        return " $selectsql ";
    }

    /**
     *
     */
    public function get_join_sql() {
        global $USER;

        $params = array();
        // $params['ruserid']    = $USER->id;
        // $params['rcontextid'] = $this->df->context->id;
        // $params['rcomponent'] = 'mod_dataform';
        // $params['ratingarea'] = $this->name;
        $userid = $USER->id;
        $contextid = $this->df->context->id;
        $component = 'mod_dataform';
        $ratingarea = $this->name;

        $alias = $this->get_sql_alias();

        $sql = "LEFT JOIN (
                    SELECT
                        r.itemid,
                        r.component,
                        r.ratingarea,
                        r.contextid,
                        COUNT(r.rating) AS numratings,
                        AVG(r.rating) AS avgratings,
                        SUM(r.rating) AS sumratings,
                        MAX(r.rating) AS maxratings,
                        MIN(r.rating) AS minratings,
                        ur.id as ratingid,
                        ur.userid as ratinguserid,
                        ur.scaleid,
                        ur.rating AS usersrating
                    FROM
                        {rating} r
                        LEFT JOIN {rating} ur ON ur.contextid = r.contextid
                                                AND ur.itemid = r.itemid
                                                AND ur.component = r.component
                                                AND ur.ratingarea = r.ratingarea
                                                AND ur.userid = $userid
                    WHERE
                        r.contextid = $contextid
                        AND r.component = '$component'
                        AND r.ratingarea = '$ratingarea'
                    GROUP BY
                        r.itemid,
                        r.component,
                        r.ratingarea,
                        r.contextid,
                        ratingid,
                        ur.userid,
                        ur.scaleid
                    ORDER BY
                        r.itemid
                ) AS $alias ON $alias.itemid = e.id ";
        return array($sql, $params);
    }

    // Sort
    /**
     *
     */
    public function get_sort_sql($element = null) {
        if ($element == 'ratings') {
            return 'usersrating';
        } else if ($element == 'countratings') {
            return 'numratings';
        } else {
            return $element;
        }
    }

    /**
     *
     */
    public function get_sort_from_sql() {
        return null;
    }

    /**
     * Return array of sort options menu as
     * $fieldid,element => name, for the filter form.
     *
     *
     * @return null|array
     */
    public function get_sort_options_menu() {
        $fieldid = $this->id;
        return array(
            "$fieldid,usersrating" => get_string('usersrating', 'dataformfield_ratingmdl'),
            "$fieldid,numratings" => get_string('numratings', 'dataformfield_ratingmdl'),
            "$fieldid,avgratings" => get_string('avgratings', 'dataformfield_ratingmdl'),
            "$fieldid,sumratings" => get_string('sumratings', 'dataformfield_ratingmdl'),
            "$fieldid,maxratings" => get_string('maxratings', 'dataformfield_ratingmdl'),
            "$fieldid,minratings" => get_string('minratings', 'dataformfield_ratingmdl'),
        );
    }

    // Search
    /**
     * Converts the given search string to its content representation.
     *
     * @param string
     * @return mixed
     */
    public function get_search_value($value) {
        return $value;
    }

    /**
     *
     */
    public function get_search_sql($search) {
        return parent::get_search_sql($search);
    }

    /**
     *
     */
    public function get_search_from_sql() {
        return null;
    }

    /**
     *
     */
    public function get_entry_ids_for_content($sql, $params) {
        return null;
    }

    /**
     *
     * @param array
     * @return string
     */
    public function format_search_value($searchparams) {
        return implode(' ', $searchparams);
    }

    // Parent::get_sql_compare_text($element = 'content')

    /**
     *
     * @param object The entry whose rating is retrieved
     * @param bool Whether to include all the rating records of the rating
     * @return object rating
     */
    public function get_entry_rating($entry, $addrecords = false) {
        if (empty($entry->id) or $entry->id < 0) {
            return null;
        }

        global $CFG;

        $rm = new ratingmdl_rating_manager();

        $context = $this->df->context;
        $fieldname = $this->name;

        // Get entry rating objects
        $scaleid = $this->get_scaleid($entry);

        $options = new object;
        $options->context = $context;
        $options->component = 'mod_dataform';
        $options->ratingarea = $fieldname;
        $options->scaleid = $scaleid;

        $rec = new object;
        $rec->itemid = $entry->id;
        $rec->context = $context;
        $rec->component = 'mod_dataform';
        $rec->ratingarea = $fieldname;
        $rec->settings = $rm->get_rating_settings_object($options);
        $rec->aggregate = array_keys($rm->get_aggregate_types());
        $rec->scaleid = $scaleid;
        $rec->userid = $this->get_entry_rating_element($entry, 'ratinguserid');
        $rec->id = $this->get_entry_rating_element($entry, 'ratingid');
        $rec->usersrating = $this->get_entry_rating_element($entry, 'usersrating');
        $rec->numratings = $this->get_entry_rating_element($entry, 'numratings');
        $rec->avgratings = $this->get_entry_rating_element($entry, 'avgratings');
        $rec->sumratings = $this->get_entry_rating_element($entry, 'sumratings');
        $rec->maxratings = $this->get_entry_rating_element($entry, 'maxratings');
        $rec->minratings = $this->get_entry_rating_element($entry, 'minratings');

        $rating = $rm->get_rating_object($entry, $rec);

        if ($addrecords) {
            if ($rating->records = $this->get_rating_records(array('itemid' => $entry->id))) {
                $rating->records = array();
            }
        }
        return $rating;
    }

    /**
     *
     * @return array Recordset
     */
    public function get_rating_records(array $options = null) {
        if (!$allrecords = $this->get_all_rating_records()) {
            return array();
        }

        if (!$options) {
            return $allrecords;
        }

        $itemid = !empty($options['itemid']) ? $options['itemid'] : 0;
        $rating = !empty($options['rating']) ? $options['rating'] : null;

        $raterecords = array();
        foreach ($allrecords as $recordid => $raterecord) {
            if ($raterecord->itemid < $itemid) {
                continue;
            }
            // Break if we already found the respective records
            if ($raterecord->itemid > $itemid) {
                break;
            }

            // Limit to specified rating value
            if ($rating !== null and $raterecord->rating != $rating) {
                continue;
            }

            $raterecords[$recordid] = $raterecord;
        }

        return $raterecords;
    }

    /**
     *
     * @return array Recordset
     */
    protected function get_all_rating_records() {
        global $DB;

        if ($this->_allratings === null) {
            $rm = new ratingmdl_rating_manager();

            $options = new object;
            $options->context = $this->df->context;
            $options->component = 'mod_dataform';
            $options->ratingarea = $this->name;

            list($sql, $params) = $rm->get_sql_all($options, false);

            $this->_allratings = $DB->get_records_sql($sql, $params);
        }
        return $this->_allratings;
    }

    // GRADING
    public function get_user_values($pattern, $userid = 0) {
        global $CFG;

        if (!$aggrs = $this->renderer->get_aggregations(array($pattern))) {
            return null;
        }

        $fieldname = $this->name;
        $aggregation = reset($aggrs);

        $options = new stdClass;
        $options->component = 'mod_dataform';
        $options->ratingarea = $fieldname;
        $options->aggregationmethod = $aggregation;
        $options->itemtable = 'dataform_entries';
        $options->itemtableusercolumn = 'userid';
        $options->modulename = 'dataform';
        $options->moduleid   = $this->df->id;
        $options->userid = $userid;
        $options->scaleid = $this->get_scaleid();

        $rm = new ratingmdl_rating_manager();
        return $rm->get_user_grades($options);
    }

    /**
     * Returns the database column used to store the scale.
     *
     * @return string
     */
    public static function get_scale_param() {
        return 'param1';
    }
    // GETTERS

    /**
     * Returns the effective scaleid, either from the entry or from the field settings.
     *
     * @param stdClass $entry
     * @return int
     */
    public function get_scaleid($entry = null) {
        $aliasscaleid = $this->get_sql_alias(). '_scaleid';
        if (!empty($entry->$aliasscaleid)) {
            return $entry->$aliasscaleid;
        } else {
            return $this->_scaleid;
        }
    }

    /**
     *
     */
    public function get_entry_rating_element($entry, $element) {
        $aliasedelem = $this->get_sql_alias(). "_$element";
        if (isset($entry->$aliasedelem)) {
            return $entry->$aliasedelem;
        }
        return null;
    }



}