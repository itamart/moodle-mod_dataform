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

require_once('../../config.php');
require_once('mod_class.php');

$urlparams = new object();

$urlparams->d          = required_param('d', PARAM_INT);             // dataform id
$urlparams->id        = optional_param('id', 0 , PARAM_INT);          // update field id

// fields list actions
$urlparams->new        = optional_param('new', 0, PARAM_ALPHA);     // type of the new field
$urlparams->delete     = optional_param('delete', 0, PARAM_SEQUENCE);   // ids (comma delimited) of fields to delete
$urlparams->duplicate  = optional_param('duplicate', 0, PARAM_SEQUENCE);   // ids (comma delimited) of fields to duplicate

$urlparams->confirmed    = optional_param('confirmed', 0, PARAM_INT);

// Set a dataform object
$df = new dataform($urlparams->d);

require_capability('mod/dataform:managetemplates', $df->context);

$df->set_page('fields', array('urlparams' => $urlparams));

// DATA PROCESSING
if ($forminput = data_submitted() and confirm_sesskey()) {
    $action = '';

    // default sort
    if (!empty($forminput->updatedefaultsort)) {
        $sortlist = array();

        // set fields' sort order and direction
        if ($fields = $df->get_fields(array(dataform::_ENTRY))) {
            foreach ($fields as $field) {
                $fieldid = $field->field->id;
                if ($fieldid and $defaultsortorder = optional_param('defaultsort'. $fieldid, 0, PARAM_INT)) {
                    $sortlist[$defaultsortorder] = array($fieldid => optional_param('defaultdir'. $fieldid, 0, PARAM_INT));
                }
            }
        }
        
        // update dataform default sort
        if (!empty($sortlist)) {
            ksort($sortlist);
            $sorties = array();
            foreach ($sortlist as $sorty) {
                $sorties[key($sorty)] = current($sorty);
            }
            $strsort = serialize($sorties);
        } else {
            $strsort = '';
        }
        $rec->defaultsort = $strsort;
        if (!$df->update($rec)) {
            print_error('Failed to update the dataform');
        }
        // update current record so that the list reflects the changes
        $df->data->defaultsort = $strsort;
    
    // multi add or delete
    } else if (!empty($forminput->multiduplicate) or !empty($forminput->multidelete)) {
        $fids = array();
        foreach ($forminput as $name => $checked) {
            if (strpos($name, 'fieldselector_') !== false) {
                if ($checked) {
                    $namearr = explode('_', $name);  // Second one is the field id                   
                    $fids[] = $namearr[1];
                }
            }
        }
        
        if ($fids) {
            if (!empty($forminput->multiduplicate)) {
                $urlparams->duplicate = implode(',', $fids);        
            } else if (!empty($forminput->multidelete)) {
                $urlparams->delete = implode(',', $fids);        
            }
        }
    }
}

if ($urlparams->duplicate and confirm_sesskey()) {  // Duplicate any requested views
    $df->process_fields('duplicate', $urlparams->duplicate, $urlparams->confirmed);

} else if ($urlparams->delete and confirm_sesskey()) { // Delete any requested views
    $df->process_fields('delete', $urlparams->delete, $urlparams->confirmed);
}

// any notifications
$df->notifications['bad']['getstartedfields'] = '';
if (!$fields = $df->get_fields(null, false, true)) {
    $linktoviews = html_writer::link(new moodle_url('views.php', array('d' => $df->id())), get_string('views', 'dataform'));
    $df->notifications['bad']['getstartedviews'] = get_string('getstartedviews','dataform', $linktoviews);
    $df->notifications['bad'][] = get_string('fieldnoneindataform','dataform');  // nothing in dataform
}

// print header
$df->print_header(array('tab' => 'fields', 'urlparams' => $urlparams));

// Display the field form jump list
$directories = get_list_of_plugins('mod/dataform/field/');
$menufield = array();

foreach ($directories as $directory){
    if ($directory[0] != '_') {
        $menufield[$directory] = get_string('pluginname',"dataformfield_$directory");    //get from language files
    }
}
asort($menufield);    //sort in alphabetical order

$popupurl = new moodle_url('/mod/dataform/field/field_edit.php', array('d' => $df->id(), 'sesskey' => sesskey()));
$fieldselect = new single_select($popupurl, 'type', $menufield, null, array(''=>'choosedots'), 'fieldform');
$fieldselect->set_label(get_string('fieldadd','dataform'). '&nbsp;');
$br = html_writer::empty_tag('br');
echo html_writer::tag('div', $br. $OUTPUT->render($fieldselect). $br, array('class'=>'fieldadd mdl-align'));
//echo $OUTPUT->help_icon('fieldadd', 'dataform');

// if there are user fields print admin style list of them
if ($fields) {
    
    /// table headings
    $strname = get_string('name');
    $strtype = get_string('type', 'dataform');
    $strdescription = get_string('description');
    $strorder = get_string('defaultsortorder', 'dataform');
    $strdir = get_string('defaultsortdir', 'dataform');
    $stredit = get_string('edit');
    $strdelete = get_string('delete');
    $selectallnone = '<input type="checkbox" '.
                        'onclick="inps=document.getElementsByTagName(\'input\');'.
                            'for (var i=0;i<inps.length;i++) {'.
                                'if (inps[i].type==\'checkbox\' && inps[i].name.search(\'fieldselector_\')!=-1){'.
                                    'inps[i].checked=this.checked;'.
                                '}'.
                            '}" />';

    $table = new html_table();
    $table->head = array($strname, $strtype, $strdescription, $strorder, $strdir, $stredit, $strdelete, $selectallnone);
    $table->align = array('left','left','left', 'center', 'center', 'center', 'center', 'center');
    $table->wrap = array(false, false, false, false, false, false, false, false);
    $table->attributes['align'] = 'center';

    // parse dataform default sort
    if ($df->data->defaultsort) {
        $sortfields = unserialize($df->data->defaultsort);
        $sortfieldids = array_keys($sortfields);
    }

    $orderrange = range(1, 50); 
    $orderoptions = array_combine($orderrange, $orderrange);
    $diroptions = array(0 => get_string('ascending', 'dataform'),
                        1 => get_string('descending', 'dataform'));

    $editbaseurl = '/mod/dataform/field/field_edit.php';
    $deletebaseurl = '/mod/dataform/fields.php';
    $linkparams = array('d' => $df->id(), 'sesskey' => sesskey());
                        
    foreach ($fields as $fieldid => $field) {
        $sortorder = $sortdir = 0;
        // check if field participates in default sort
        if (isset($sortfields[$fieldid])) {
            $sortorder = array_search($fieldid,$sortfieldids) + 1;
            $sortdir =  $sortfields[$fieldid];
        }
        
        // set fields table display
        if ($fieldid > 0) {    // user fields
            $fieldname = html_writer::link(new moodle_url($editbaseurl, $linkparams + array('id' => $fieldid)), $field->name());
            $fieldedit = html_writer::link(new moodle_url($editbaseurl, $linkparams + array('id' => $fieldid)),
                            html_writer::empty_tag('img', array('src' => $OUTPUT->pix_url('t/edit'), 'class' => "iconsmall", 'alt' => get_string('edit'), 'title' => get_string('edit'))));
            $fielddelete = html_writer::link(new moodle_url($deletebaseurl, $linkparams + array('delete' => $fieldid)),
                            html_writer::empty_tag('img', array('src' => $OUTPUT->pix_url('t/delete'), 'class' => "iconsmall", 'alt' => get_string('delete'), 'title' => get_string('delete'))));
            $fieldselector = html_writer::checkbox("fieldselector_$fieldid", $fieldid, false);
        } else {                // internal field
            $fieldname = $field->name();
            $fieldedit = '-';
            $fielddelete = '-';
            $fieldselector = '-';
        }
        $fieldtype = $field->image(). '&nbsp;'. $field->typename();
        $fielddescription = shorten_text($field->field->description, 30);
        $fieldsortoption = html_writer::select($orderoptions, "defaultsort$fieldid", $sortorder);
        $fielddiroption = html_writer::select($diroptions, "defaultdir$fieldid", $sortdir);

        $table->data[] = array(
            $fieldname,
            $fieldtype,
            $fielddescription,
            $fieldsortoption,
            $fielddiroption,
            $fieldedit,
            $fielddelete,
            $fieldselector
        );
    }
    
    echo '<form id="sortdefault" action="'. $CFG->wwwroot. '/mod/dataform/fields.php?d='. $df->id(). '" method="post">',
        '<input type="hidden" name="sesskey" value="', sesskey(), '" />';

    // multi action buttons
    echo '<div class="mdl-align">',
        'With selected: ',
        '&nbsp;&nbsp;<input type="submit" name="multiduplicate" value="', get_string('multiduplicate', 'dataform'), '" />',
        '&nbsp;&nbsp;',
        '<input type="submit" name="multidelete" value="', get_string('multidelete', 'dataform'), '" />',
        '</div>',
        '<br />';

    echo html_writer::table($table);
    echo '<br />',
        '<div class="mdl-align">',
        '<input type="submit" name="updatedefaultsort" value="', get_string('updatedefaultsort', 'dataform'), '" />',
        '</div>',
        '</form>';
}

$df->print_footer();
