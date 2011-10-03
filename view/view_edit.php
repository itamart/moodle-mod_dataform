<?php

/**
 * This file is part of the Dataform module for Moodle - http://moodle.org/.
 *
 * @package mod-dataform
 * @copyright 2011 Itamar Tzadok
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * The Dataform has been developed as an enhanced counterpart
 * of Moodle's Database activity module (1.9.11+ (20110323)).
 * To the extent that Dataform code corresponds to Database code,
 * certain copyrights on the Database module may obtain.
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

require_once('../../../config.php');
require_once("$CFG->dirroot/mod/dataform/mod_class.php");

$urlparams = new object();
$urlparams->d          = required_param('d', PARAM_INT);    // dataform ID

$urlparams->type       = optional_param('type','' ,PARAM_ALPHA);   // type of a view to edit
$urlparams->vid        = optional_param('vid',0 ,PARAM_INT);       // view id to edit

// Set a dataform object
$df = new dataform($urlparams->d);

require_capability('mod/dataform:managetemplates', $df->context);

$df->set_page('view/view_edit', array('modjs' => true, 'urlparams' => $urlparams));

if ($urlparams->vid) {
    $view = $df->get_view_from_id($urlparams->vid);
    if ($default = optional_param('resetdefault',0 ,PARAM_INT)) {
        $view->generate_default_view();
    }    
} else if ($urlparams->type) {
    $view = $df->get_view($urlparams->type);
    $view->generate_default_view();
}

$mform = $view->get_form();

if ($mform->is_cancelled()){
    redirect(new moodle_url('/mod/dataform/views.php', array('d' => $urlparams->d)));

// no submit buttons: reset to default, switch editor    
} else if ($mform->no_submit_button_pressed()) {
    $resettodefault = optional_param('resetdefaultbutton', '', PARAM_ALPHA);

    if ($resettodefault) {   // reset view to default
        // TODO is this the best way?
        $urlparams->resetdefault = 1;
        redirect(new moodle_url('/mod/dataform/view/view_edit.php', (array) $urlparams));
        
    }
    


// process validated    
} else if ($data = $mform->get_data()) { 

    $data = $view->from_form($data);    

    if (!$view->id()) {    // add new view
        $view->insert_view($data);
        $df->notifications['good'][] = get_string('viewsadded','dataform');
        add_to_log($df->course->id, 'dataform', 'views add',
                   'view_edit.php?d='. $df->id(), '', $df->cm->id);
        // TODO: default view       
    } else {   // update view

        $view->update_view($data);
        $df->notifications['good'][] = get_string('viewsupdated','dataform');
        add_to_log($df->course->id, 'dataform', 'views update',
                   'views.php?d='. $df->id(). '&amp;vid=', $urlparams->vid, $df->cm->id);
    }
    
    if (isset($data->submitreturnbutton)) {
        // go back to form
    } else {
        redirect(new moodle_url('/mod/dataform/views.php', array('d' => $urlparams->d)));
    }
    
}

// print header
$df->print_header(array('tab' => 'views', 'nonotifications' => true, 'urlparams' => $urlparams));

// display form
$mform = $view->get_form();
$mform->set_data($view->to_form());
$mform->display();

$df->print_footer();
