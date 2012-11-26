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
 * @subpackage coursegroup
 * @copyright 2012 Itamar Tzadok
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('AJAX_SCRIPT', true);

require_once('../../../../config.php');

$courseid = required_param('courseid', PARAM_INT);

// Check user is logged in
require_login();

if ($courseid) {
    $groups = $DB->get_records_menu('groups', array('courseid' => $courseid), 'name', 'id,name');
    $groupsmenu = array();
    foreach ($groups as $groupid => $name) {
        $groupsmenu[] = "$groupid $name";
    }

    echo implode(',', $groupsmenu);
}    
