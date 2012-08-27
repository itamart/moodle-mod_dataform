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
 * @package mod-dataform
 * @copyright 2011 Itamar Tzadok
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once('mod_class.php');
require_once('packages_form.php');

$urlparams = new object();

$urlparams->d = optional_param('d', 0, PARAM_INT);             // dataform id
$urlparams->id = optional_param('id', 0, PARAM_INT);            // course module id

// packages list actions
$urlparams->apply =     optional_param('apply', 0, PARAM_INT);  // path of package to apply
$urlparams->map =       optional_param('map', 0, PARAM_BOOL);  // map new package fields to old fields
$urlparams->delete =    optional_param('delete', '', PARAM_SEQUENCE);   // ids of packages to delete
$urlparams->share =     optional_param('share', '', PARAM_SEQUENCE);     // ids of packages to share
$urlparams->download =     optional_param('download', '', PARAM_SEQUENCE);     // ids of packages to download in one zip

$urlparams->confirmed = optional_param('confirmed', 0, PARAM_INT);

// Set a dataform object
$df = new dataform($urlparams->d, $urlparams->id);
require_capability('mod/dataform:managetemplates', $df->context);

$df->set_page('packages', array('modjs' => true, 'urlparams' => $urlparams));

// activate navigation node
navigation_node::override_active_url(new moodle_url('/mod/dataform/packages.php', array('id' => $df->cm->id)));

// DATA PROCESSING
$df->process_packages('/mod/dataform/packages.php', $urlparams);

$localpackages = $df->get_user_packages(dataform::PACKAGE_COURSEAREA);
$sharedpackages = $df->get_user_packages(dataform::PACKAGE_SITEAREA);

// any notifications
$df->notifications['bad']['getstartedpackages'] = '';
if (!$localpackages and !$sharedpackages) {
    $df->notifications['bad']['getstartedpackages'] = get_string('packagenoneavailable','dataform');  // nothing in dataform
    if (!$df->get_user_defined_fields()) {
        $linktofields = html_writer::link(new moodle_url('fields.php', array('d' => $df->id())), get_string('fields', 'dataform'));
        $df->notifications['bad']['getstartedfields'] = get_string('getstartedfields','dataform', $linktofields);
    }
    if (!$df->get_views()) {
        $linktoviews = html_writer::link(new moodle_url('views.php', array('d' => $df->id())), get_string('views', 'dataform'));
        $df->notifications['bad']['getstartedviews'] = get_string('getstartedviews','dataform', $linktoviews);
    }
}

// print header
$df->print_header(array('tab' => 'packages', 'urlparams' => $urlparams));

// print the add form
echo html_writer::start_tag('div', array('style' => 'width:80%;margin:auto;'));
$mform = new mod_dataform_packages_form(new moodle_url('/mod/dataform/packages.php', array('d' => $df->id(), 'sesskey' => sesskey(), 'add' => 1)));
$mform->set_data(null);
$mform->display();
echo html_writer::end_tag('div');

// if there are packages print admin style list of them
$df->print_packages_list('/mod/dataform/packages.php', $localpackages, $sharedpackages);

$df->print_footer();
