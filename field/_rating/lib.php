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
                $aggregate = $this->settings->scale->scaleitems[round($aggregate)]; //round aggregate as we're using it as an index
            } else { // aggregation is SUM or the scale is numeric
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
     * Adds rating objects to an array of items (forum posts, glossary entries etc)
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

        $optionsaggregate = null;
        if (empty($options->aggregate)) {
            // ugly hack to work around the exception in generate_settings
            $options->aggregate = RATING_AGGREGATE_COUNT;     
        } else {
            $optionsaggregate = $options->aggregate;        
            $aggregatessql = array();
            foreach ($options->aggregate as $aggregation) {
                $aggrmethod = $this->get_aggregation_method($aggregation);
                $aggrmethodpref = strtolower($aggrmethod);
                $aggregatessql[$aggrmethodpref] = "$aggrmethod(r.rating) AS {$aggrmethodpref}rating";
            }
            // ugly hack to work around the exception in generate_settings
            $options->aggregate = RATING_AGGREGATE_COUNT;     
        }

        // Default the userid to the current user if it is not set
        if (empty($options->userid)) {
            $userid = $USER->id;
        } else {
            $userid = $options->userid;
        }

        // set sql for entry ids
        $itemids = array_keys($options->items);
        list($itemidtest, $params) = $DB->get_in_or_equal($itemids, SQL_PARAMS_NAMED);

        // get the items from the database
        $params['contextid'] = $options->context->id;
        $params['userid']    = $userid;
        $params['component']    = $options->component;
        $params['ratingarea'] = $options->ratingarea;

        $aggregationsql = !empty($aggregatessql) ? implode(', ', $aggregatessql). ', ' : '';
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
                        AND r.itemid {$itemidtest}
                        AND r.component = :component
                        AND r.ratingarea = :ratingarea
                GROUP BY r.itemid, r.component, r.ratingarea, r.contextid, ur.id, ur.userid, ur.scaleid
                ORDER BY r.itemid";
        $ratingsrecords = $DB->get_records_sql($sql, $params);

        $ratingoptions = new stdClass;
        $ratingoptions->context = $options->context;
        $ratingoptions->component = $options->component;
        $ratingoptions->ratingarea = $options->ratingarea;
        $ratingoptions->settings = $this->generate_rating_settings_object($options);
        foreach ($options->items as $item) {
            $ratingoptions->aggregate = null;
            if (array_key_exists($item->id, $ratingsrecords)) {
                // Note: rec->scaleid = the id of scale at the time the rating was submitted
                // may be different from the current scale id
                $rec = $ratingsrecords[$item->id];
                $ratingoptions->itemid = $item->id;
                $ratingoptions->scaleid = $rec->scaleid;
                $ratingoptions->userid = $rec->userid;
                $ratingoptions->id = $rec->id;
                $ratingoptions->rating = min($rec->usersrating, $ratingoptions->settings->scale->max);
                $ratingoptions->count = $rec->numratings;

                if (!empty($optionsaggregate)) {
                    foreach ($optionsaggregate as $aggregation) {
                        $aggrmethod = $this->get_aggregation_method($aggregation);
                        $aggrmethodpref = strtolower($aggrmethod);
                        $ratingoptions->aggregate[$aggregation] = min($rec->{"{$aggrmethodpref}rating"}, $ratingoptions->settings->scale->max);
                    }
                }
            } else {
                $ratingoptions->itemid = $item->id;
                $ratingoptions->scaleid = null;
                $ratingoptions->userid = null;
                $ratingoptions->id = null;
                $ratingoptions->count = 0;
                $ratingoptions->rating =  null;
            }

            $rating = new dataform_rating($ratingoptions);
            $rating->itemtimecreated = $this->get_item_time_created($item);
            if (!empty($item->userid)) {
                $rating->itemuserid = $item->userid;
            }
            $item->rating = $rating;
        }

        return $options->items;
    }
}
