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
 * @package mod_dataform
 * @category admin
 * @copyright 2014 Itamar Tzadok
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../config.php');
require_once("$CFG->libdir/adminlib.php");

admin_externalpage_setup('moddataform_stringschecker');

$verify = optional_param('verify', '', PARAM_ALPHA);

//$results = null;
//if ($verify) {
//    $results = mod_dataform_strings_helper::verify($verify);
//}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('stringschecker', 'dataform'));

echo \mod_dataform_strings_helper::get_summary();

echo $OUTPUT->footer();