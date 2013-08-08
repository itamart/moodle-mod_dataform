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
 * @preset mod-dataform
 * @copyright 2012 Itamar Tzadok
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * The Dataform has been developed as an enhanced counterpart
 * of Moodle's Database activity module (1.9.11+ (20110323)).
 * To the extent that Dataform code corresponds to Database code,
 * certain copyrights on the Database module may obtain.
 */

$is_templatemanager = has_capability('mod/dataform:managetemplates', $this->context);
$is_entriesmanager = has_capability('mod/dataform:manageentries', $this->context);

// tabs are displayed only for template managers
if (isloggedin() and $is_templatemanager) {
    if (empty($currenttab) or empty($this->data) or empty($this->course)) {
        throw new moodle_exception('emptytab', 'dataform');
    }

    $inactive = array();
    $activated = array();
    $tabs = array();

    // Browse/Management
    $row = array();
    $row[] = new tabobject('browse', new moodle_url('/mod/dataform/view.php', array('d' => $this->id())), get_string('browse','dataform'));
    $row[] = new tabobject('manage', new moodle_url('/mod/dataform/view/index.php', array('d' => $this->id())), get_string('manage','dataform'));
    // Add view edit tab
    if ($currenttab == 'browse' and !empty($this->_currentview)) {
        $params = array('d' => $this->id(), 'sesskey' => sesskey(), 'vedit' => $this->_currentview->id());
        $editviewurl = new moodle_url('/mod/dataform/view/view_edit.php', $params);
        $row[] = new tabobject('editview', $editviewurl, $OUTPUT->pix_icon('t/edit', get_string('vieweditthis','dataform')));
    }


    $tabs[] = $row;

    if ($currenttab != 'browse') {
        $inactive[] = 'manage';
        $activated[] = 'manage';

        $row  = array();
        // template manager can do everything
        if ($is_templatemanager)  {
            $row[] = new tabobject('views', new moodle_url('/mod/dataform/view/index.php', array('d' => $this->id())), get_string('views','dataform'));
            $row[] = new tabobject('fields', new moodle_url('/mod/dataform/field/index.php', array('d' => $this->id())), get_string('fields','dataform'));
            $row[] = new tabobject('filters', new moodle_url('/mod/dataform/filter/index.php', array('d' => $this->id())), get_string('filters','dataform'));
            $row[] = new tabobject('rules', new moodle_url('/mod/dataform/rule/index.php', array('d' => $this->id())), get_string('rules','dataform'));
            $row[] = new tabobject('tools', new moodle_url('/mod/dataform/tool/index.php', array('d' => $this->id())), get_string('tools','dataform'));
            $row[] = new tabobject('js', new moodle_url('/mod/dataform/js.php', array('d' => $this->id(), 'jsedit' => 1)), get_string('jsinclude', 'dataform'));
            $row[] = new tabobject('css', new moodle_url('/mod/dataform/css.php', array('d' => $this->id(), 'cssedit' => 1)), get_string('cssinclude', 'dataform'));
            $row[] = new tabobject('presets', new moodle_url('/mod/dataform/preset/index.php', array('d' => $this->id())), get_string('presets', 'dataform'));
            $row[] = new tabobject('import', new moodle_url('/mod/dataform/import.php', array('d' => $this->id())), get_string('import', 'dataform'));
            //$row[] = new tabobject('reports', new moodle_url('/mod/dataform/reports.php', array('d' => $this->id())), get_string('reports','dataform'));

        // entries manager can do import
        //} else if ($is_entriesmanager)  {
        //    $row[] = new tabobject('import', new moodle_url('/mod/dataform/import.php', array('d' => $this->id())), get_string('import', 'dataform'));
        }

        $tabs[] = $row;
    }

    // Print out the tabs and continue!
    print_tabs($tabs, $currenttab, $inactive, $activated);
}
