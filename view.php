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
 *
 * The Dataform has been developed as an enhanced counterpart
 * of Moodle's Database activity module (1.9.11+ (20110323)).
 * To the extent that Dataform code corresponds to Database code,
 * certain copyrights on the Database module may obtain.
 */

require_once('../../config.php');
require_once("$CFG->dirroot/mod/dataform/mod_class.php");

$urlparams = new object();
$urlparams->d = optional_param('d', 0, PARAM_INT);             // dataform id
$urlparams->id = optional_param('id', 0, PARAM_INT);           // course module id

$urlparams->view = optional_param('view', 0, PARAM_INT);       // current view id
$urlparams->filter = optional_param('filter', 0, PARAM_INT);     // current filter (-1 for user filter)
$urlparams->pagelayout = optional_param('pagelayout', '', PARAM_ALPHAEXT);
$urlparams->refresh = optional_param('refresh', 0, PARAM_INT);
$urlparams->renew = optional_param('renew', 0, PARAM_INT);

// Set a dataform object with guest autologin
$df = new dataform($urlparams->d, $urlparams->id, true);

$pageparams = array(
        'js' => true,
        'css' => true,
        'rss' => true,
        'modjs' => true,
        'completion' => true,
        'comments' => true,
        'urlparams' => $urlparams);        
$df->set_page('view', $pageparams);

require_capability('mod/dataform:viewentry', $df->context);

$df->set_content();

$headerparams = array(
        'heading' => 'true',
        'tab' => 'browse',
        'groups' => true,
        'urlparams' => $urlparams);
$df->print_header($headerparams);

$df->display();

$df->print_footer();
