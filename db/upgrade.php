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
/**
 * This file keeps track of upgrades to
 * the dataform module
 *
 * Sometimes, changes between versions involve
 * alterations to database structures and other
 * major things that may break installations.
 *
 * The upgrade function in this file will attempt
 * to perform all the necessary actions to upgrade
 * your older installation to the current version.
 *
 * If there's something it cannot do itself, it
 * will tell you what you need to do.
 *
 * The commands in here will all be database-neutral,
 * using the methods of database_manager class
 *
 * Please do not forget to use upgrade_set_timeout()
 * before any action that may take longer time to finish.
 *
 */

function xmldb_dataform_upgrade($oldversion) {
    global $CFG, $DB, $OUTPUT;

    $dbman = $DB->get_manager();

    // Moodle v2.1.0 release upgrade line
    if ($oldversion < 2012032100) {
        // add field selection to dataform_filters
        $table = new xmldb_table('dataform_filters');
        $field = new xmldb_field('selection', XMLDB_TYPE_INTEGER, '4', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', 'perpage');

        // Launch add field selection
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        
        // dataform savepoint reached
        upgrade_mod_savepoint(true, 2012032100, 'dataform');
    
    
    }

    if ($oldversion < 2012040600) {
        // add field edits to dataform_fields
        $table = new xmldb_table('dataform_fields');
        $field = new xmldb_field('edits', XMLDB_TYPE_INTEGER, '6', null, XMLDB_NOTNULL, null, '-1', 'description');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        
        // dataform savepoint reached
        upgrade_mod_savepoint(true, 2012040600, 'dataform');
    }

    if ($oldversion < 2012050500) {
        // drop field comments from dataform
        $table = new xmldb_table('dataform');
        $field = new xmldb_field('comments');
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }
        // drop field locks
        $field = new xmldb_field('locks');
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }
        // add field rules
        $field = new xmldb_field('rules', XMLDB_TYPE_TEXT, 'small', null, null, null, null, 'rating');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // dataform savepoint reached
        upgrade_mod_savepoint(true, 2012050500, 'dataform');
    }

    if ($oldversion < 2012051600) {
        // drop field grading from entries
        $table = new xmldb_table('dataform_entries');
        $field = new xmldb_field('grading');
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        // dataform savepoint reached
        upgrade_mod_savepoint(true, 2012051600, 'dataform');
    }

    if ($oldversion < 2012053100) {
        $table = new xmldb_table('dataform');

        // add field cssincludes
        $field = new xmldb_field('cssincludes', XMLDB_TYPE_TEXT, 'small', null, null, null, null, 'css');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // add field jsincludes
        $field = new xmldb_field('jsincludes', XMLDB_TYPE_TEXT, 'small', null, null, null, null, 'js');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // dataform savepoint reached
        upgrade_mod_savepoint(true, 2012053100, 'dataform');
    }

    if ($oldversion < 2012060101) {
        // changed stored content of view editors from serialized to formatted string
        // Assumed at this point that serialized content in param fields in the
        // view table is editor content which needs to be unserialized to 
        // $text, $format, $trust and restored as "ft:{$format}tr:{$trust}ct:$text" 
        
        // Get all views
        if ($views = $DB->get_records('dataform_views')) {
            foreach ($views as $view) {
                $update = false;
                // section field
                if (!empty($view->section)) {
                    $editordata = @unserialize($view->section);
                    if ($editordata !== false) {
                        list($text, $format, $trust) = $editordata;
                        $view->section = "ft:{$format}tr:{$trust}ct:$text";
                        $update = true;
                    }
                }                
                // 10 param fields
                for ($i = 1; $i <= 10; ++$i) {
                    $param = "param$i";
                    if (!empty($view->$param)) {
                        $editordata = @unserialize($view->$param);
                        if ($editordata !== false) {
                            list($text, $format, $trust) = $editordata;
                            $view->$param = "ft:{$format}tr:{$trust}ct:$text";
                            $update = true;
                        }
                    }
                } 
                if ($update) {
                    $DB->update_record('dataform_views', $view);
                }
            }
        }

        // dataform savepoint reached
        upgrade_mod_savepoint(true, 2012060101, 'dataform');
    }

    if ($oldversion < 2012061700) {
        // Remove version record of dataform views and fields from config_plugin
        $DB->delete_records_select('config_plugins', $DB->sql_like('plugin', '?'), array('dataform%'));
        // Change type of view block/blockext to matrix/matrixext
        $DB->set_field('dataform_views', 'type', 'matrix', array('type' => 'block'));
        $DB->set_field('dataform_views', 'type', 'matrixext', array('type' => 'blockext'));
        
        // Move content of matrixext param1 -> param4 and param3 -> param5 
       if ($views = $DB->get_records('dataform_views', array('type' => 'matrixext'))) {
            foreach ($views as $view) {
                if (!empty($view->param1) or !empty($view->param3)) {
                    $view->param4 = $view->param1;
                    $view->param5 = $view->param3;
                    $view->param1 = null;
                    $view->param3 = null;
                    $DB->update_record('dataform_views', $view);
                }
            }
        }
        
        // Move content of editon param3 -> param7 
       if ($views = $DB->get_records('dataform_views', array('type' => 'editon'))) {
            foreach ($views as $view) {
                if (!empty($view->param3)) {
                    $view->param7 = $view->param3;
                    $view->param1 = null;
                    $view->param3 = null;
                    $DB->update_record('dataform_views', $view);
                }
            }
        }
        
        // Move content of tabular param1 -> param3 
       if ($views = $DB->get_records('dataform_views', array('type' => 'tabular'))) {
            foreach ($views as $view) {
                $view->param3 = $view->param1;
                $view->param1 = null;
                $DB->update_record('dataform_views', $view);
            }
        }
        
        // dataform savepoint reached
        upgrade_mod_savepoint(true, 2012061700, 'dataform');
    }

    if ($oldversion < 2012070601) {
        // add field default filter to dataform
        $table = new xmldb_table('dataform');
        $field = new xmldb_field('defaultfilter', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'defaultview');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        
        // Move content of dataform->defaultsort to a new default filter
       if ($dataforms = $DB->get_records('dataform')) {
            $strdefault = get_string('default');
            foreach ($dataforms as $dfid => $dataform) {
                if (!empty($dataform->defaultsort)) {
                    // Add a new 'Default filter' filter
                    $filter = new object;
                    $filter->dataid = $dfid;
                    $filter->name = $strdefault. '_0';
                    $filter->description = '';
                    $filter->visible = 0;
                    $filter->customsort = $dataform->defaultsort;

                    if ($filterid = $DB->insert_record('dataform_filters', $filter)) {
                        $DB->set_field('dataform', 'defaultfilter', $filterid, array('id' => $dfid));
                    }
                }
            }
        }
        
        // drop dataform field defaultsort
        $field = new xmldb_field('defaultsort');
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }
        
        // dataform savepoint reached
        upgrade_mod_savepoint(true, 2012070601, 'dataform');
    }

    if ($oldversion < 2012081801) {
        // add field visible to dataform_fields
        $table = new xmldb_table('dataform_fields');
        $field = new xmldb_field('visible', XMLDB_TYPE_INTEGER, '4', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '2', 'description');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        
        // dataform savepoint reached
        upgrade_mod_savepoint(true, 2012081801, 'dataform');
    }

    if ($oldversion < 2012082600) {
        // Change timelimit field to signed, default -1
        $table = new xmldb_table('dataform_fields');
        $field = new xmldb_field('timelimit', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '-1', 'maxentries');
        if ($dbman->field_exists($table, $field)) {
            $dbman->change_field_unsigned($table, $field);
            $dbman->change_field_default($table, $field);
        }
        
        // dataform savepoint reached
        upgrade_mod_savepoint(true, 2012082600, 'dataform');
    }

    if ($oldversion < 2012082900) {
        $fs = get_file_storage();
        // Move presets from course_packages to course_presets
        if ($dataforms = $DB->get_records('dataform')) {
            foreach ($dataforms as $df) {
                $context = context_course::instance($df->course);
                if ($presets = $fs->get_area_files($context->id, 'mod_dataform', 'course_packages')) {
                
                    $filerecord = new object;
                    $filerecord->contextid = $context->id;
                    $filerecord->component = 'mod_dataform';
                    $filerecord->filearea = 'course_presets';
                    $filerecord->filepath = '/';

                    foreach ($presets as $preset) {
                        if (!$preset->is_directory()) {
                            $fs->create_file_from_storedfile($filerecord, $preset);
                        }
                    }
                    $fs->delete_area_files($context->id, 'mod_dataform', 'course_packages');
                }
            }
        }

        // Move presets from site_packages to site_presets
        $filerecord = new object;
        $filerecord->contextid = SYSCONTEXTID;
        $filerecord->component = 'mod_dataform';
        $filerecord->filearea = 'site_presets';
        $filerecord->filepath = '/';

        if ($presets = $fs->get_area_files(SYSCONTEXTID, 'mod_dataform', 'course_packages')) {
            foreach ($presets as $preset) {
                if (!$preset->is_directory()) {
                    $fs->create_file_from_storedfile($filerecord, $preset);
                }
            }
        }
        $fs->delete_area_files(SYSCONTEXTID, 'mod_dataform', 'site_packages');

        // dataform savepoint reached
        upgrade_mod_savepoint(true, 2012082900, 'dataform');
    }

    if ($oldversion < 2012092002) {
        // Add rules table
        $table = new xmldb_table('dataform_rules');
        if (!$dbman->table_exists($table)) {
            $filepath = "$CFG->dirroot/mod/dataform/db/install.xml";
            $dbman->install_one_table_from_xmldb_file($filepath, 'dataform_rules');
        }
        
        // dataform savepoint reached
        upgrade_mod_savepoint(true, 2012092002, 'dataform');
    }

    if ($oldversion < 2012092207) {
        // Change type of view matrix/matrixext to grid/gridext
        $DB->set_field('dataform_views', 'type', 'grid', array('type' => 'matrix'));
        $DB->set_field('dataform_views', 'type', 'gridext', array('type' => 'matrixext'));
        
        // dataform savepoint reached
        upgrade_mod_savepoint(true, 2012092207, 'dataform');
    }

    if ($oldversion < 2012121600) {
        // Convert internal field ids whereever they are cached or referenced
        $newfieldids = array(
            -1 => 'entry',
            -2 => 'timecreated',
            -3 => 'timemodified',
            -4 => 'approve',
            -5 => 'group',
            -6 => 'userid',
            -7 => 'username',
            -8 => 'userfirstname',
            -9 => 'userlastname',
            -10 => 'userusername',
            -11 => 'useridnumber',
            -12 => 'userpicture',
            -13 => 'comment',
            -14 => 'rating',
            -141 => 'ratingavg',
            -142 => 'ratingcount',
            -143 => 'ratingmax',
            -144 => 'ratingmin',
            -145 => 'ratingsum',
        );
        
        // View patterns
        if ($views = $DB->get_records('dataform_views')) {
            foreach ($views as $view) {
                $update = false;
                if ($view->patterns) {
                    $patterns = unserialize($view->patterns);
                    $newpatterns = array('view' => $patterns['view'], 'field' => array());
                    foreach ($patterns['field'] as $fieldid => $tags) {
                        if ($fieldid < 0 and !empty($newfieldids[$fieldid])) {
                            $newpatterns['field'][$newfieldids[$fieldid]] = $tags;
                            $update = true;
                        } else {
                            $newpatterns['field'][$fieldid] = $tags;
                        }
                    }
                    $view->patterns = serialize($newpatterns);
                }
                if ($update) {
                    $DB->update_record('dataform_views', $view);
                }
            }
        }
        // Filter customsort and customsearch
        if ($filters = $DB->get_records('dataform_filters')) {
            foreach ($filters as $filter) {
                $update = false;

                // adjust customsort field ids
                if ($filter->customsort) {
                    $customsort = unserialize($filter->customsort);
                    $sortfields = array();
                    foreach ($customsort as $fieldid => $sortdir) {
                        if ($fieldid < 0 and !empty($newfieldids[$fieldid])) {
                            $sortfields[$newfieldids[$fieldid]] = $sortdir;
                            $update = true;
                        } else {
                            $sortfields[$fieldid] = $sortdir;
                        }
                    }
                    $filter->customsort = serialize($sortfields);
                }
                                
                // adjust customsearch field ids
                if ($filter->customsearch) {
                    $customsearch = unserialize($filter->customsearch);
                    $searchfields = array();
                    foreach ($customsearch as $fieldid => $options) {
                        if ($fieldid < 0 and !empty($newfieldids[$fieldid])) {
                            $searchfields[$newfieldids[$fieldid]] = $options;
                            $update = true;
                        } else {
                            $searchfields[$fieldid] = $options;
                        }
                    }
                    $filter->customsearch = serialize($searchfields);
                }
                if ($update) {
                    $DB->update_record('dataform_filters', $filter);
                }
            }
        }        
        
        // dataform savepoint reached
        upgrade_mod_savepoint(true, 2012121600, 'dataform');
    }

    if ($oldversion < 2012121900) {

        // Changing type of field groupby on table dataform_views to char.
        $table = new xmldb_table('dataform_views');
        $field = new xmldb_field('groupby', XMLDB_TYPE_CHAR, '64', null, null, null, '', 'perpage');
        $dbman->change_field_type($table, $field);

        // Changing type of field groupby on table dataform_filters to char.
        $table = new xmldb_table('dataform_filters');
        $field = new xmldb_field('groupby', XMLDB_TYPE_CHAR, '64', null, null, null, '', 'selection');
        $dbman->change_field_type($table, $field);

        // Change groupby 0 to null in existing views and filters
        $DB->set_field('dataform_views', 'groupby', null, array('groupby' => 0));
        $DB->set_field('dataform_filters', 'groupby', null, array('groupby' => 0));

        // dataform savepoint reached
        upgrade_mod_savepoint(true, 2012121900, 'dataform');
    }

    if ($oldversion < 2013051101) {
        // Add notification format column to dataform
        $table = new xmldb_table('dataform');
        $field = new xmldb_field('notificationformat', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '1', 'notification');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Add label column to dataform fields
        $table = new xmldb_table('dataform_fields');
        $field = new xmldb_field('label', XMLDB_TYPE_TEXT, 'small', null, null, null, null, 'edits');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // dataform savepoint reached
        upgrade_mod_savepoint(true, 2013051101, 'dataform');
    }

    return true;
}
