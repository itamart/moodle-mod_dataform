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
 * @package dataformtool
 * @copyright 2011 Itamar Tzadok
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once('../../../config.php');
require_once('../mod_class.php');

$urlparams = new object();

$urlparams->d = optional_param('d', 0, PARAM_INT);             // dataform id
$urlparams->id = optional_param('id', 0, PARAM_INT);           // course module id

// views list actions
$urlparams->run    = optional_param('run', '', PARAM_PLUGIN);  // tool plugin to run

$urlparams->confirmed    = optional_param('confirmed', 0, PARAM_INT);

// Set a dataform object
$df = new dataform($urlparams->d, $urlparams->id);
require_capability('mod/dataform:managetemplates', $df->context);

$df->set_page('tool/index', array('modjs' => true, 'urlparams' => $urlparams));

// activate navigation node
navigation_node::override_active_url(new moodle_url('/mod/dataform/tool/index.php', array('id' => $df->cm->id)));

// DATA PROCESSING
if ($urlparams->run and confirm_sesskey()) {  // Run selected tool
    $tooldir = "$CFG->dirroot/mod/dataform/tool/$urlparams->run";
    $toolclass = "dataformtool_$urlparams->run";
    if (file_exists($tooldir)) {
        require_once("$tooldir/lib.php");
        if ($result = $toolclass::run($df)) {
            list($goodbad, $message) = $result;
        } else {
            $goodbad = 'bad';
            $message = '';
        }
        $df->notifications[$goodbad][] = $message;
    }
}

// Get the list of tools
$directories = get_list_of_plugins('mod/dataform/tool/');
$tools = array();
foreach ($directories as $directory){
    $tools[$directory] = (object) array(
        'name' => get_string('pluginname',"dataformtool_$directory"),
        'description' => get_string('pluginname_help',"dataformtool_$directory")
    );
}
ksort($tools);    //sort in alphabetical order

// any notifications?
if (!$tools) {
    $df->notifications['bad'][] = get_string('toolnoneindataform','dataform');  // nothing in database
}

// print header
$df->print_header(array('tab' => 'tools', 'urlparams' => $urlparams));

// if there are tools print admin style list of them
if ($tools) {
    $actionbaseurl = '/mod/dataform/tool/index.php';
    $linkparams = array('d' => $df->id(), 'sesskey' => sesskey());
                        
    /// table headings
    $strname = get_string('name');
    $strdesc = get_string('description');
    $strrun = get_string('toolrun','dataform');;

    $table = new html_table();
    $table->head = array($strname, $strdesc, $strrun);
    $table->align = array('left', 'left', 'center');
    $table->wrap = array(false, false, false);
    $table->attributes['align'] = 'center';
    
    foreach ($tools as $dir => $tool) {
        
        $runlink = html_writer::link(new moodle_url($actionbaseurl, $linkparams + array('run' => $dir)),
                        $OUTPUT->pix_icon('t/addgreen', $strrun));

        $table->data[] = array(
            $tool->name,
            $tool->description,
            $runlink,
       );
    }
    echo html_writer::table($table);
}

$df->print_footer();

