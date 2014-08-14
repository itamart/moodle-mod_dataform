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
 * This page receives ratingmdl ajax rating submissions
 *
 * Similar to rating/rate_ajax.php except for it allows retrieving multiple aggregations.
 *
 * @package dataformfield
 * @subpackage ratingmdl
 * @copyright 2013 Itamar Tzadok
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('AJAX_SCRIPT', true);

require_once('../../../../config.php');
require_once('ratinglib.php');

$contextid         = required_param('contextid', PARAM_INT);
$component         = required_param('component', PARAM_COMPONENT);
$ratingarea        = required_param('ratingarea', PARAM_AREA);
$itemid            = required_param('itemid', PARAM_INT);
$scaleid           = required_param('scaleid', PARAM_INT);
$userrating        = required_param('rating', PARAM_INT);
$rateduserid       = required_param('rateduserid', PARAM_INT); // Which user is being rated. Required to update their grade
$aggregationmethod = optional_param('aggregation', RATING_AGGREGATE_NONE, PARAM_SEQUENCE); // We're going to calculate the aggregate and return it to the client

$result = new stdClass;

// If session has expired and its an ajax request so we cant do a page redirect
if (!isloggedin()) {
    $result->error = get_string('sessionerroruser', 'error');
    echo json_encode($result);
    die();
}

list($context, $course, $cm) = get_context_info_array($contextid);

// Instantiate the Dataform
$df = mod_dataform_dataform::instance($cm->instance);
require_login($df->course->id, false, $df->cm);

// Sesskey
if (!confirm_sesskey()) {
    echo $OUTPUT->header();
    echo get_string('ratepermissiondenied', 'rating');
    echo $OUTPUT->footer();
    die();
}

$PAGE->set_context($df->context);
$PAGE->set_url('/mod/dataform/field/ratingmdl/rate_ajax.php', array('contextid' => $context->id));

// Get the field
$field = $df->field_manager->get_field_by_name($ratingarea);

// Get the entry
$entry = $DB->get_record('dataform_entries', array('id' => $itemid));

// Get the entry rating
if (!$entryrating = $field->get_entry_rating($entry)) {
    $result->error = get_string('ratepermissiondenied', 'rating');
    echo json_encode($result);
    die();
}
$entry->rating = $entryrating;

$rm = new ratingmdl_rating_manager();

// Check the module rating permissions
if (!$field->user_can_rate($entry, $USER->id)) {
    $result->error = get_string('ratepermissiondenied', 'rating');
    echo json_encode($result);
    die();
}

// Check that the rating is valid
$params = array(
    'context'     => $context,
    'component'   => $component,
    'ratingarea'  => $ratingarea,
    'itemid'      => $itemid,
    'scaleid'     => $scaleid,
    'rating'      => $userrating,
    'rateduserid' => $rateduserid,
    'aggregation' => $aggregationmethod
);
if (!$rm->check_rating_is_valid($params)) {
    $result->error = get_string('ratinginvalid', 'rating');
    echo json_encode($result);
    die();
}

// Rating options used to update the rating then retrieving the aggregations
$ratingoptions = new stdClass;
$ratingoptions->context = $context;
$ratingoptions->ratingarea = $ratingarea;
$ratingoptions->component = $component;
$ratingoptions->itemid  = $itemid;
$ratingoptions->scaleid = $scaleid;
$ratingoptions->userid  = $USER->id;

if ($userrating != RATING_UNSET_RATING) {
    $rating = new rating($ratingoptions);
    $rating->update_rating($userrating);
} else {
    // Delete the rating if the user set to Rate.
    $options = new stdClass;
    $options->contextid = $context->id;
    $options->component = $component;
    $options->ratingarea = $ratingarea;
    $options->userid = $USER->id;
    $options->itemid = $itemid;

    $rm->delete_ratings($options);
}

// Need to retrieve the updated item to get its new aggregate value
$item = new stdClass;
$item->id = $itemid;

// Most of $ratingoptions variables were previously set
$ratingoptions->items = array($itemid => $item);
$ratingoptions->aggregate = array(
    RATING_AGGREGATE_AVERAGE,
    RATING_AGGREGATE_MAXIMUM,
    RATING_AGGREGATE_MINIMUM,
    RATING_AGGREGATE_SUM,
);

$items = $rm->get_ratings($ratingoptions);
$firstitem = reset($items);
$firstrating = $firstitem->rating;
$ratingcount = $firstrating->count;
$ratingavg = '';
$ratingmax = '';
$ratingmin = '';
$ratingsum = '';

// Add aggregations
if ($firstrating->user_can_view_aggregate()) {
    $ratingavg = round($firstrating->ratingavg, 2);
    $ratingmax = round($firstrating->ratingmax, 2);
    $ratingmin = round($firstrating->ratingmin, 2);
    $ratingsum = round($firstrating->ratingsum, 2);

    // For custom scales return text not the value
    // This scales weirdness will go away when scales are refactored
    if ($firstrating->settings->scale->id < 0) {
        $scalerecord = $DB->get_record('scale', array('id' => -$firstrating->settings->scale->id));
        $scalearray = explode(',', $scalerecord->scale);

        $ratingavg = $scalearray[round($ratingavg) - 1];
        $ratingmax = $scalearray[round($ratingmax) - 1];
        $ratingmin = $scalearray[round($ratingmin) - 1];
        // For sum take the highest
        if (round($ratingsum, 1) > count($scalearray)) {
            $ratingsum = count($scalearray);
        }
        $ratingsum = $scalearray[round($ratingsum) - 1];
    }
}

// Result
$result->success = true;
$result->ratingcount = $ratingcount;
$result->ratingavg = $ratingavg;
$result->ratingmax = $ratingmax;
$result->ratingmin = $ratingmin;
$result->ratingsum = $ratingsum;
$result->itemid = $itemid;

echo json_encode($result);
