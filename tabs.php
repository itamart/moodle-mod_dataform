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
 * certain copyrights on the Database module may obtain, including:
 * @copyright 2005 Martin Dougiamas http://dougiamas.com
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

if (isloggedin() and has_capability('mod/dataform:managetemplates', $this->context)) {
    if (empty($currenttab) or empty($this->data) or empty($this->course)) {
        print_error('You cannot call this script in that way');
    }

    $inactive = array();
    $activated = array();
    $tabs = array();
       
    // Browse/Management
    $row = array();
    $row[] = new tabobject('browse', new moodle_url('/mod/dataform/view.php', array('d' => $this->id())), get_string('browse','dataform'));
    $row[] = new tabobject('manage', new moodle_url('/mod/dataform/packages.php', array('d' => $this->id())), get_string('manage','dataform'));

    $tabs[] = $row;

    if ($currenttab != 'browse') {
        $inactive[] = 'manage';
        $activated[] = 'manage';

        $row  = array();
        $row[] = new tabobject('packages', new moodle_url('/mod/dataform/packages.php', array('d' => $this->id())), get_string('packages', 'dataform'));
        $row[] = new tabobject('fields', new moodle_url('/mod/dataform/fields.php', array('d' => $this->id())), get_string('fields','dataform'));
        $row[] = new tabobject('views', new moodle_url('/mod/dataform/views.php', array('d' => $this->id())), get_string('views','dataform'));
        $row[] = new tabobject('filters', new moodle_url('/mod/dataform/filters.php', array('d' => $this->id())), get_string('filters','dataform'));
        $row[] = new tabobject('settings', new moodle_url('/mod/dataform/mod_edit.php', array('d' => $this->id(), 'section' => 'entry')), get_string('settings','dataform'));
        $row[] = new tabobject('js', new moodle_url('/mod/dataform/js.php', array('d' => $this->id(), 'jsedit' => 1)), get_string('jsinclude', 'dataform'));
        $row[] = new tabobject('css', new moodle_url('/mod/dataform/css.php', array('d' => $this->id(), 'cssedit' => 1)), get_string('cssinclude', 'dataform'));
        $row[] = new tabobject('import', new moodle_url('/mod/dataform/import.php', array('d' => $this->id())), get_string('import', 'dataform'));
        $row[] = new tabobject('export', new moodle_url('/mod/dataform/export.php', array('d' => $this->id())), get_string('export', 'dataform'));

        $tabs[] = $row;
    }
        
    //$settings = array('timing', 'entry');
     
    //if (in_array($currenttab, $settings)) {
    //    $inactive[] = 'settings';
    //    $activated[] = 'settings';
    //    $activated[] = 'manage';
        
    //    $row  = array();
    //    $row[] = new tabobject('timing', new moodle_url('/mod/dataform/mod_edit.php', array('d' => $this->id(), 'section' => 'entry')), get_string('settings','dataform'));
    //    $row[] = new tabobject('entry', new moodle_url('/mod/dataform/mod_edit.php', array('d' => $this->id(), 'section' => 'entry')), get_string('settings','dataform'));

    //    $tabs[] = $row;
    //}

    // Print out the tabs and continue!
    
    print_tabs($tabs, $currenttab, $inactive, $activated);
}
