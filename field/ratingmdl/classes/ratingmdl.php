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
 * @package dataformfield_ratingmdl
 * @copyright 2013 Itamar Tzadok
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__. '/../ratinglib.php');

/**
 *
 */
class dataformfield_ratingmdl_ratingmdl extends mod_dataform\pluginbase\dataformfield_nocontent
            implements mod_dataform\interfaces\grading, mod_dataform\interfaces\usingscale {

    protected $_scaleid = 0;
    protected $_allratings = null;

    const AGGREGATE_AVG = 1;
    const AGGREGATE_COUNT = 2;
    const AGGREGATE_MAX = 3;
    const AGGREGATE_MIN = 4;
    const AGGREGATE_SUM = 5;

    /**
     *
     */
    public function __construct($field) {
        parent::__construct($field);

        $this->_scaleid = $this->param1 ? $this->param1 : 0;
    }

    /**
     *
     */
    public function permissions() {
        $context = $this->df->context;
        $ownviewaggregate = has_capability('dataformfield/ratingmdl:ownviewaggregate', $context);
        $anyviewaggregate = has_capability('dataformfield/ratingmdl:anyviewaggregate', $context);
        $ownviewratings = has_capability('dataformfield/ratingmdl:ownviewratings', $context);
        $anyviewratings = has_capability('dataformfield/ratingmdl:anyviewratings', $context);
        $ownrate = has_capability('dataformfield/ratingmdl:ownrate', $context);
        $anyrate = has_capability('dataformfield/ratingmdl:anyrate', $context);

        return array(
            'view'    => $ownviewaggregate,
            'viewany' => $anyviewaggregate,
            'viewall' => $ownviewratings or $anyviewratings,
            'rate'    => $ownrate or $anyrate,
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

    /**
     * Returns true if the user is able to rate in this field in the specified entry.
     *
     * @param stdClass $entry Entry
     * @param int $userid Current user assumed if left empty
     * @return bool
     */
    public function user_can_rate($entry, $userid = null) {
        if (empty($userid)) {
            global $USER;
            $userid = $USER->id;
        }

        // You can't rate if you don't have the system cap
        if (empty($entry->rating->settings->permissions->rate)) {
            return false;
        }

        // Is it your own entry?
        $ownentry = ($userid == $entry->userid);

        // It is your entry!
        if ($ownentry) {
            return has_capability('dataformfield/ratingmdl:ownrate', $this->df->context);
        }

        // It is not your entry!
        if (!$ownentry) {
            return has_capability('dataformfield/ratingmdl:anyrate', $this->df->context);
        }

        return false;
    }

    /**
     * Returns true if the user is able to view ratings in this field in the specified entry.
     *
     * @param stdClass $entry Entry
     * @param int $userid Current user assumed if left empty
     * @return bool
     */
    public function user_can_view_ratings($entry, $userid = null) {
        if (empty($userid)) {
            global $USER;
            $userid = $USER->id;
        }

        // You can't view ratings if you don't have the system cap
        if (empty($entry->rating->settings->permissions->viewall)) {
            return false;
        }

        // Is it your own entry?
        $ownentry = ($userid == $entry->userid);

        // It is your entry!
        if ($ownentry) {
            return has_capability('dataformfield/ratingmdl:ownviewratings', $this->df->context);
        }

        // It is not your entry!
        if (!$ownentry) {
            return has_capability('dataformfield/ratingmdl:anyviewratings', $this->df->context);
        }

        return false;
    }

    /**
     * Returns true if the user is able to view aggregates in this field in the specified entry.
     *
     * @param stdClass $entry Entry
     * @param int $userid Current user assumed if left empty
     * @return bool
     */
    public function user_can_view_aggregates($entry, $userid = null) {
        if (empty($userid)) {
            global $USER;
            $userid = $USER->id;
        }

        // Is it your own entry?
        $ownentry = ($userid == $entry->userid);

        // You can't view own aggregates if you don't have the system cap
        if ($ownentry and empty($entry->rating->settings->permissions->view)) {
            return false;
        }

        // You can't view any aggregates if you don't have the system cap
        if (!$ownentry and empty($entry->rating->settings->permissions->viewany)) {
            return false;
        }

        // It is your entry!
        if ($ownentry) {
            return has_capability('dataformfield/ratingmdl:ownviewaggregate', $this->df->context);
        }

        // It is not your entry!
        if (!$ownentry) {
            return has_capability('dataformfield/ratingmdl:anyviewaggregate', $this->df->context);
        }

        return false;
    }

    // SQL MANAGEMENT.
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

    // Sort.
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

    // Search.
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
     * Returns rating records, all or for a specific entry and/or for a specific rating.
     *
     * @param array $options
     *      itemid => int Entry id
     *      rating => int Rating value
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

    // GRADING.
    /**
     * Returns the value replacement of the pattern for each user with content in the field.
     *
     * @param string $pattern
     * @param array $entryids The ids of entries the field values should be fetched from.
     *      If not provided the method should return values from all applicable entries.
     * @param int $userid   The id of the users whose field values are requested.
     *      If not specified, should return values for all applicable users.
     * @return null|array Array of userid => value pairs.
     */
    public function get_user_values($pattern, array $entryids = null, $userid = 0) {
        $values = array();

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
        $options->scaleid = $this->get_scaleid();

        $rm = new ratingmdl_rating_manager();

        $users = $userid ? array($userid) : array();

        foreach ($users as $userid) {
            $options->userid = $userid;
            if ($usergrades = $rm->get_user_grades($options)) {
                $uservalues = array_map(
                    function($g) {
                        return $g->rawgrade;
                    },
                    $usergrades
                );
                $values[$userid] = $uservalues[$userid];
            }
        }

        return $values;
    }

    // USING SCALE.
    /**
     * Returns the database column used to store the scale.
     *
     * @return string
     */
    public static function is_using_scale($scaleid, $dataformid = 0) {
        global $DB;

        $conds = array();
        $params = array();
        // Dataform.
        if ($dataformid) {
            $conds[] = ' dataid = ? ';
            $params[] = $dataformid;
        }
        // Field type.
        $conds[] = ' type = ? ';
        $params[] = 'ratingmdl';
        // Scale id.
        $conds[] = $DB->sql_compare_text('param1'). ' = ? ';
        $params[] = -$scaleid;

        $select = implode(' AND ', $conds);
        return $DB->record_exists_select('dataform_fields', $select, $params);
    }

    // GETTERS.
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
