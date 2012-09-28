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
 * @copyright 2011 Itamar Tzadok
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * A class representing a single dataform rating 
 * Extends the core rating class
 */

require_once("$CFG->dirroot/rating/lib.php");

class dataform_rating extends rating {

    /**
     * Returns this ratings aggregate value
     *
     * @return string
     */
    public function get_aggregate_value($aggregation) {

        $aggregate = isset($this->aggregate[$aggregation]) ? $this->aggregate[$aggregation] : '';

        if ($aggregate and $aggregation != RATING_AGGREGATE_COUNT) {
            if ($aggregation != RATING_AGGREGATE_SUM and !$this->settings->scale->isnumeric) {
                //round aggregate as we're using it as an index
                $aggregate = $this->settings->scale->scaleitems[round($aggregate)];
            } else {
                // aggregation is SUM or the scale is numeric
                $aggregate = round($aggregate, 1);
            }
        }

        return $aggregate;
    }
}

/**
 * The dataform_rating_manager class extends the rating_manager class 
 * so as to retrieve sets of ratings from the database for sets of entries
 */
class dataform_rating_manager extends rating_manager {

    /**
     * Adds rating objects to an array of entries
     * Rating objects are available at $item->rating
     * @param stdClass $options {
     *            context          => context the context in which the ratings exists [required]
     *            component        => the component name ie mod_forum [required]
     *            ratingarea       => the ratingarea we are interested in [required]
     *            items            => array an array of items such as forum posts or glossary items. They must have an 'id' member ie $items[0]->id[required]
     *            aggregate        => array an array of aggregation method to be applied. RATING_AGGREGATE_AVERAGE, RATING_AGGREGATE_MAXIMUM etc [optional]
     *            scaleid          => int the scale from which the user can select a rating [required]
     *            userid           => int the id of the current user [optional]
     *            returnurl        => string the url to return the user to after submitting a rating. Can be left null for ajax requests [optional]
     *            assesstimestart  => int only allow rating of items created after this timestamp [optional]
     *            assesstimefinish => int only allow rating of items created before this timestamp [optional]
     * @return array the array of items with their ratings attached at $items[0]->rating
     */
    public function get_ratings($options) {
        global $DB, $USER;

        if (!isset($options->context)) {
            throw new coding_exception('The context option is a required option when getting ratings.');
        }

        if (!isset($options->component)) {
            throw new coding_exception('The component option is a required option when getting ratings.');
        }

        if (!isset($options->ratingarea)) {
            throw new coding_exception('The ratingarea option is a required option when getting ratings.');
        }

        if (!isset($options->scaleid)) {
            throw new coding_exception('The scaleid option is a required option when getting ratings.');
        }

        if (!isset($options->items)) {
            throw new coding_exception('The items option is a required option when getting ratings.');
        } else if (empty($options->items)) {
            return array();
        }

        list($sql, $params) = $this->get_sql_aggregate($options);        
        if ($ratingrecords = $DB->get_records_sql($sql, $params)) {
            foreach ($options->items as &$item) {
                if (array_key_exists($item->id, $ratingrecords)) {
                    $rec = $ratingrecords[$item->id];
                    $rec->context = $options->context;
                    $rec->component = $options->component;
                    $rec->ratingarea = $options->ratingarea;
                    $rec->scaleid = $options->scaleid;
                    $rec->settings = $this->generate_rating_settings_object($options);
                    $rec->aggregate = $options->aggregate;
                    $item->rating = $this->get_rating_object($item, $rec);
                }
            }
        }
        return $options->items;
    }

    /**
     * @return array the array of items with their ratings attached at $items[0]->rating
     */
    public function get_sql_aggregate($options) {
        global $DB, $USER;

        // User id; default to current user
        if (empty($options->userid)) {
            $userid = $USER->id;
        } else {
            $userid = $options->userid;
        }

        // Params
        $params = array();
        $params['contextid'] = $options->context->id;
        $params['userid']    = $userid;
        $params['component']    = $options->component;
        $params['ratingarea'] = $options->ratingarea;

        // Aggregation sql
        $optionsaggregate = null;
        if (empty($options->aggregate)) {
            // ugly hack to work around the exception in generate_settings
            $options->aggregate = RATING_AGGREGATE_COUNT;     
        } else {
            $optionsaggregate = $options->aggregate;        
            $aggregatessql = array();
            foreach ($options->aggregate as $aggregation) {
                if (empty($aggregation)) {
                    continue;
                }
                $aggrmethod = $this->get_aggregation_method($aggregation);
                $aggrmethodpref = strtolower($aggrmethod);
                $aggregatessql[$aggrmethodpref] = "$aggrmethod(r.rating) AS {$aggrmethodpref}ratings";
            }
            // ugly hack to work around the exception in generate_settings
            $options->aggregate = RATING_AGGREGATE_COUNT;     
        }
        $aggregationsql = !empty($aggregatessql) ? implode(', ', $aggregatessql). ', ' : '';

        // sql for entry ids
        $andwhereitems = '';
        if (!empty($options->items)) {
            $itemids = array_keys($options->items);
            list($itemidtest, $paramitems) = $DB->get_in_or_equal($itemids, SQL_PARAMS_NAMED);
            $andwhereitems = " AND r.itemid $itemidtest ";
            $params = array_merge($params, $paramitems);
        }

        $sql = "SELECT r.itemid, r.component, r.ratingarea, r.contextid,
                       COUNT(r.rating) AS numratings, $aggregationsql 
                       ur.id, ur.userid, ur.scaleid, ur.rating AS usersrating
                FROM {rating} r
                        LEFT JOIN {rating} ur ON ur.contextid = r.contextid
                                                AND ur.itemid = r.itemid
                                                AND ur.component = r.component
                                                AND ur.ratingarea = r.ratingarea
                                                AND ur.userid = :userid
                WHERE r.contextid = :contextid 
                        AND r.component = :component
                        AND r.ratingarea = :ratingarea
                        $andwhereitems
                GROUP BY r.itemid, r.component, r.ratingarea, r.contextid, ur.id, ur.userid, ur.scaleid
                ORDER BY r.itemid";
                
        return array($sql, $params);
    }
    
    /**
     * @return array the array of items with their ratings attached at $items[0]->rating
     */
    public function get_sql_all($options) {
        global $DB, $USER;

        // User id; default to current user
        if (empty($options->userid)) {
            $userid = $USER->id;
        } else {
            $userid = $options->userid;
        }

        // Params
        $params = array();
        $params['contextid'] = $options->context->id;
        $params['userid']    = $userid;
        $params['component']    = $options->component;
        $params['ratingarea'] = $options->ratingarea;

        // sql for entry ids
        $andwhereitems = '';
        if (!empty($options->items)) {
            $itemids = array_keys($options->items);
            list($itemidtest, $paramitems) = $DB->get_in_or_equal($itemids, SQL_PARAMS_NAMED);
            $andwhereitems = " AND r.itemid $itemidtest ";
            $params = array_merge($params, $paramitems);
        }

        $sql = "SELECT r.id, r.itemid, r.component, r.ratingarea, r.contextid, r.scaleid,
                       r.rating, r.userid, r.timecreated, r.timemodified, ".
                       user_picture::fields('u', array('idnumber', 'username'), 'uid ').
               " FROM {rating} r 
                    JOIN {user} u ON u.id = r.userid 
                    
                WHERE r.contextid = :contextid 
                        AND r.component = :component
                        AND r.ratingarea = :ratingarea
                        $andwhereitems
                ORDER BY r.itemid";
                
        return array($sql, $params);
    }
    
    
    /**
     * @return array the array of items with their ratings attached at $items[0]->rating
     */
    public function get_rating_settings_object($options) {
        return $this->generate_rating_settings_object($options);
    }
    
    /**
     * @return array the array of items with their ratings attached at $items[0]->rating
     */
    public function get_rating_object($item, $ratingrecord) {

        $rec = $ratingrecord;

        $options = new object;
        $options->context = $rec->context;
        $options->component = 'mod_dataform';
        $options->ratingarea = $rec->ratingarea; 
        $options->itemid = $item->id;
        $options->settings = $rec->settings;
        // Note: rec->scaleid = the id of scale at the time the rating was submitted
        // may be different from the current scale id
        $options->scaleid = $rec->scaleid;

        $options->userid = !empty($rec->userid) ? $rec->userid : 0;
        $options->id = !empty($rec->id) ? $rec->id : 0;
        if (!empty($rec->usersrating)) {
            $options->rating = min($rec->usersrating, $rec->settings->scale->max);
        } else {
            $options->rating = null;
        }
        $options->count = $rec->numratings;
        $rec->countratings = $rec->numratings;

        if (!empty($rec->aggregate)) {
            if (!is_array($rec->aggregate)) {
                $rec->aggregate = array($rec->aggregate);
            }
            foreach ($rec->aggregate as $aggregation) {
                if (empty($aggregation)) {
                    continue;
                }
                $aggrmethod = $this->get_aggregation_method($aggregation);
                $aggrmethodpref = strtolower($aggrmethod);
                $options->aggregate[$aggregation] = min($rec->{"{$aggrmethodpref}ratings"}, $rec->settings->scale->max);
            }
        }

        $rating = new dataform_rating($options);
        $rating->itemtimecreated = $this->get_item_time_created($item);
        if (!empty($item->userid)) {
            $rating->itemuserid = $item->userid;
        }

        return $rating;
    }


}
