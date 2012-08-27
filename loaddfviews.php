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
 * @copyright 2012 Itamar Tzadok
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('AJAX_SCRIPT', true);

require_once(dirname(__FILE__). '/../../config.php');

$d = required_param('dfid', PARAM_INT);

// Check user is logged in
require_login();

$retviews = '';
$retfilters = '';
if ($d) {
    if ($views = $DB->get_records_menu('dataform_views', array('dataid' => $d), 'name', 'id,name')) {
        $viewmenu = array();
        foreach($views as $key => $value) {
            $viewmenu[] = "$key ". strip_tags($value);
        }
        $retviews = implode(',', $viewmenu);
    }
    if ($filters = $DB->get_records_menu('dataform_filters', array('dataid' => $d), 'name', 'id,name')) {
        $filtermenu = array();
        foreach($filters as $key => $value) {
            $filtermenu[] = "$key ". strip_tags($value);
        }
        $retfilters = implode(',', $filtermenu);
    }
}
echo "$retviews#$retfilters";    
