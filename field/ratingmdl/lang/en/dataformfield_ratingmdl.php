<?php
// This file is part of Moodle - http://moodle.org/
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
 * @copyright 2014 Itamar Tzadok
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'Rating (moodle)';
$string['rating'] = 'Rating';
$string['usersrating'] = 'User rating';
$string['numratings'] = 'Number of ratings';
$string['avgratings'] = 'Average of ratings';
$string['sumratings'] = 'Sum of ratings';
$string['maxratings'] = 'Highest rating';
$string['minratings'] = 'Lowest rating';
/* CAPABILITIES */
$string['ratingmdl:addinstance'] = 'Add a new Rating (moodle) dataformfield';
$string['ratingmdl:ownrate'] = 'Own Entry - Rate';
$string['ratingmdl:ownviewaggregate'] = 'Own Entry - View Rating Aggregates';
$string['ratingmdl:ownviewratings'] = 'Own Entry - View Ratings';
$string['ratingmdl:anyrate'] = 'Any Entry - Rate';
$string['ratingmdl:anyviewaggregate'] = 'Any Entry - View Rating Aggregates';
$string['ratingmdl:anyviewratings'] = 'Any Entry - View Ratings';
/* EVENTS */
$string['event_rating_created'] = 'Rating created';
$string['event_rating_deleted'] = 'Rating deleted';
/* SETTINGS */
$string['preventzero'] = 'Prevent zero rating';
$string['preventzero_help'] = 'By default point scales include a 0 value for rating. If set to Yes the 0 value will not be available for rating.';
$string['ratelabel'] = 'Rate label';
$string['ratelabel_help'] = 'Rate label';
$string['repititionlimit'] = 'Repitition limit';
$string['repititionlimit_help'] = 'The max number of times a value in the rating scale can be used by the users in scope for rating the entry. (Each user is still restricted to 1 rating per field)';
$string['repititionscope'] = 'Repitition scope';
$string['repititionscope_help'] = 'The scope of users to which the repitition limit applies.';
$string['forceinorder'] = 'Force in order';
$string['forceinorder_help'] = 'When in-order is forced, the user cannot use a particular value for rating before preceding values in the scale have been used the "limit" number of times (if applicable).';
$string['eachuser'] = 'Each user separately';
$string['allusers'] = 'All users as a whole';
