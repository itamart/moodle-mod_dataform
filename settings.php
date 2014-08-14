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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * @package mod_dataform
 * @copyright 2012 Itamar Tzadok
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') or die;

$ADMIN->add('modsettings', new admin_category('moddataformfolder', new lang_string('pluginname', 'mod_dataform'), $module->is_enabled() === false));

$settings = new admin_settingpage($section, get_string('settings', 'mod_dataform'), 'moodle/site:config', $module->is_enabled() === false);

if ($ADMIN->fulltree) {
    // enable rss feeds
    if (empty($CFG->enablerssfeeds)) {
        $options = array(0 => get_string('rssglobaldisabled', 'admin'));
        $str = new lang_string('configenablerssfeeds', 'dataform').'<br />'.new lang_string('configenablerssfeedsdisabled2', 'admin');

    } else {
        $options = array(0 => get_string('no'), 1 => get_string('yes'));
        $str = new lang_string('configenablerssfeeds', 'dataform');
    }
    $settings->add(new admin_setting_configselect('dataform_enablerssfeeds', new lang_string('enablerssfeeds', 'admin'),
                       $str, 0, $options));

    $unlimited = get_string('unlimited');
    $keys = range(0, 500);
    $values = range(1, 500);
    array_unshift($values, $unlimited);

    // max fields
    $options = array_combine($keys, $values);
    $settings->add(new admin_setting_configselect('dataform_maxfields', new lang_string('fieldsmax', 'dataform'),
                       new lang_string('configmaxfields', 'dataform'), 0, $options));

    // max views
    $options = array_combine($keys, $values);
    $settings->add(new admin_setting_configselect('dataform_maxviews', new lang_string('viewsmax', 'dataform'),
                       new lang_string('configmaxviews', 'dataform'), 0, $options));

    // max filters
    $options = array_combine($keys, $values);
    $settings->add(new admin_setting_configselect('dataform_maxfilters', new lang_string('filtersmax', 'dataform'),
                       new lang_string('configmaxfilters', 'dataform'), 0, $options));

    // max entries
    $keys = range(-1, 500);
    $values = range(0, 500);
    array_unshift($values, $unlimited);
    $options = array_combine($keys, $values);
    $settings->add(new admin_setting_configselect('dataform_maxentries', new lang_string('entriesmax', 'dataform'),
                       new lang_string('configmaxentries', 'dataform'), -1, $options));

    // allow anonymous entries
    $options = array(0 => get_string('no'), 1 => get_string('yes'));
    $settings->add(new admin_setting_configselect('dataform_anonymous', new lang_string('anonymousentries', 'dataform'),
                       new lang_string('configanonymousentries', 'dataform'), 0, $options));
}

$ADMIN->add('moddataformfolder', $settings);
// Tell core we already added the settings structure.
$settings = null;

// Site presets manager
$ADMIN->add('moddataformfolder', new admin_externalpage('moddataform_sitepresets', new lang_string('presetavailableinsite', 'dataform'), '/mod/dataform/admin/sitepresets.php'));

// Add admin tools
foreach (get_directory_list("$CFG->dirroot/mod/dataform/classes/admin") as $filename) {
    $toolname = basename($filename, '.php');
    $tool = "mod_dataform\admin\\$toolname";
    $ADMIN->add('moddataformfolder', new admin_externalpage("moddataform_$toolname", $tool::get_visible_name(), $tool::get_url()));
}
