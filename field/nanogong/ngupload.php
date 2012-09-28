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
 * @subpackage nanogong
 * @copyright 2011 Itamar Tzadok
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../../config.php');
require_once("$CFG->libdir/filelib.php");

$elname = required_param('elname', PARAM_NOTAGS);
$userid = required_param('userid',PARAM_INT);
$itemid = required_param('itemid', PARAM_INT);
$saveas_filename = optional_param('title', '', PARAM_FILE);
$maxbytes = optional_param('maxbytes', 0, PARAM_INT);

$usercontext = context_user::instance($userid);

$record = new object;
$record->userid = $userid;
$record->contextid = $usercontext->id;
$record->filearea = 'draft';
$record->component = 'user';
$record->filepath = '/';
$record->itemid   = $itemid;
$record->license  = optional_param('license', $CFG->sitedefaultlicense, PARAM_TEXT);
$record->author   = optional_param('author', '', PARAM_TEXT);

$elname = 'repo_upload_file';

$fs = get_file_storage();
$sm = get_string_manager();

if (!isset($_FILES[$elname])) {
    print 'File not found'; die;
}
if (!empty($_FILES[$elname]['error'])) {
    print 'File not found'; die;
}

if (empty($saveas_filename)) {
    $record->filename = clean_param($_FILES[$elname]['name'], PARAM_FILE);
} else {
    $ext = '';
    $match = array();
    $filename = clean_param($_FILES[$elname]['name'], PARAM_FILE);
    if (preg_match('/\.([a-z0-9]+)$/i', $filename, $match)) {
        if (isset($match[1])) {
            $ext = $match[1];
        }
    }
    $ext = !empty($ext) ? $ext : '';
    if (preg_match('#\.(' . $ext . ')$#i', $saveas_filename)) {
        // saveas filename contains file extension already
        $record->filename = $saveas_filename;
    } else {
        $record->filename = $saveas_filename . '.' . $ext;
    }
}

if (($maxbytes !== -1) and (filesize($_FILES[$elname]['tmp_name']) > $maxbytes)) {
    print "Max bytes $maxbytes exceeded"; die;
}

// clean the file area from any file before creating the new one
$fs->delete_area_files($record->contextid, 'user', 'draft', $record->itemid);

if ($stored_file = $fs->create_file_from_pathname($record, $_FILES[$elname]['tmp_name'])) {
    print $saveas_filename. ' uploaded';
} else {
    print 'Failed to upload file';
}
