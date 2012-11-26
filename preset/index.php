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
 * @package mod
 * @subpackage dataform
 * @copyright 2011 Itamar Tzadok
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../config.php');
require_once('../mod_class.php');
require_once('preset_form.php');

$urlparams = new object();

$urlparams->d = optional_param('d', 0, PARAM_INT);             // dataform id
$urlparams->id = optional_param('id', 0, PARAM_INT);            // course module id

// presets list actions
$urlparams->apply =     optional_param('apply', 0, PARAM_INT);  // path of preset to apply
$urlparams->torestorer =     optional_param('torestorer', 1, PARAM_INT);  // apply user data to restorer
$urlparams->map =       optional_param('map', 0, PARAM_BOOL);  // map new preset fields to old fields
$urlparams->delete =    optional_param('delete', '', PARAM_SEQUENCE);   // ids of presets to delete
$urlparams->share =     optional_param('share', '', PARAM_SEQUENCE);     // ids of presets to share
$urlparams->download =     optional_param('download', '', PARAM_SEQUENCE);     // ids of presets to download in one zip

$urlparams->confirmed = optional_param('confirmed', 0, PARAM_INT);

// Set a dataform object
$df = new dataform($urlparams->d, $urlparams->id);
require_capability('mod/dataform:managetemplates', $df->context);
$df->set_page('preset/index', array('modjs' => true, 'urlparams' => $urlparams));

// activate navigation node
navigation_node::override_active_url(new moodle_url('/mod/dataform/preset/index.php', array('id' => $df->cm->id)));

$pm = $df->get_preset_manager();

// DATA PROCESSING
$pm->process_presets($urlparams);

$localpresets = $pm->get_user_presets($pm::PRESET_COURSEAREA);
$sharedpresets = $pm->get_user_presets($pm::PRESET_SITEAREA);

// any notifications
if (!$localpresets and !$sharedpresets) {
    $df->notifications['bad'][] = get_string('presetnoneavailable','dataform');  // No presets in dataform
}

// print header
$df->print_header(array('tab' => 'presets', 'urlparams' => $urlparams));

// print the preset form
$pm->print_preset_form();

// if there are presets print admin style list of them
$pm->print_presets_list($localpresets, $sharedpresets);

$df->print_footer();
