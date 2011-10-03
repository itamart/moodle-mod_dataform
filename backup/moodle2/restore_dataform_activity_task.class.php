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
 * @copyright 2010 Eloy Lafuente (stronk7) {@link http://stronk7.com}
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

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->dirroot/mod/dataform/backup/moodle2/restore_dataform_stepslib.php"); // Because it exists (must)

/**
 * dataform restore task that provides all the settings and steps to perform one
 * complete restore of the activity
 */
class restore_dataform_activity_task extends restore_activity_task {

    /**
     * 
     */
    public function get_old_moduleid() {
        return $this->oldmoduleid;
    }

    /**
     * Create all the steps that will be part of this task
     */
    public function build() {

        // If we have decided not to restore activities, prevent anything to be built
        if (!$this->get_setting_value('activities')) {
            $this->built = true;
            return;
        }

        // If not restoring into a given activity,
        //   load he course_module estructure, generating it (with instance = 0)
        //   but allowing the creation of the target context needed in following steps
        if (!$this->get_activityid()) {
            $this->add_step(new restore_module_structure_step('module_info', 'module.xml'));
        }

        // Here we add all the common steps for any activity and, in the point of interest
        // we call to define_my_steps() is order to get the particular ones inserted in place.
        $this->define_my_steps();

        // Roles (optionally role assignments and always role overrides)
        $this->add_step(new restore_ras_and_caps_structure_step('course_ras_and_caps', 'roles.xml'));

        // Filters (conditionally)
        if ($this->get_setting_value('filters')) {
            $this->add_step(new restore_filters_structure_step('activity_filters', 'filters.xml'));
        }

        // Comments (conditionally)
        if ($this->get_setting_value('comments')) {
            $this->add_step(new restore_comments_structure_step('activity_comments', 'comments.xml'));
        }

        // Grades (module-related, rest of gradebook is restored later if possible: cats, calculations...)
        $this->add_step(new restore_activity_grades_structure_step('activity_grades', 'grades.xml'));

        // Userscompletion (conditionally)
        if ($this->get_setting_value('userscompletion')) {
            $this->add_step(new restore_userscompletion_structure_step('activity_userscompletion', 'completion.xml'));
        }

        // Logs (conditionally)
        if ($this->get_setting_value('logs')) {
            $this->add_step(new restore_activity_logs_structure_step('activity_logs', 'logs.xml'));
        }

        // At the end, mark it as built
        $this->built = true;
    }

    /**
     * Define (add) particular settings this activity can have
     */
    protected function define_my_settings() {
    }

    /**
     * Define (add) particular steps this activity can have
     */
    protected function define_my_steps() {
        // Dataform only has one structure step
        $this->add_step(new restore_dataform_activity_structure_step('dataform_structure', 'dataform.xml'));
    }

    /**
     * Define the contents in the activity that must be
     * processed by the link decoder
     */
    static public function define_decode_contents() {
        $contents = array();

        $contents[] = new restore_decode_content('dataform', array('intro'), 'dataform');
        $contents[] = new restore_decode_content('dataform_fields', array(
                              'description',
                              'param1', 'param2', 'param3', 'param4', 'param5',
                              'param6', 'param7', 'param8', 'param9', 'param10'), 'dataform_field');
        $contents[] = new restore_decode_content('dataform_views', array(
                              'description', 'section',
                              'param1', 'param2', 'param3', 'param4', 'param5',
                              'param6', 'param7', 'param8', 'param9', 'param10'), 'dataform_view');
        $contents[] = new restore_decode_content('dataform_contents', array(
                              'content', 'content1', 'content2', 'content3', 'content4'), 'dataform_content');

        return $contents;
    }

    /**
     * Define the decoding rules for links belonging
     * to the activity to be executed by the link decoder
     */
    static public function define_decode_rules() {
        $rules = array();

        $rules[] = new restore_decode_rule('DFINDEX', '/mod/dataform/index.php?id=$1', 'course');
        $rules[] = new restore_decode_rule('DFBYID', '/mod/dataform/view.php?id=$1', 'course_module');
        $rules[] = new restore_decode_rule('DFBYD', '/mod/dataform/index.php?d=$1', 'dataform');
        $rules[] = new restore_decode_rule('DFVIEW', '/mod/dataform/view.php?d=$1&amp;view=$2', array('dataform', 'dataform_view'));
        $rules[] = new restore_decode_rule('DFVIEWFILTER', '/mod/dataform/view.php?d=$1&amp;view=$2&amp;filter=$3', array('dataform', 'dataform_view', 'dataform_filter'));
        $rules[] = new restore_decode_rule('DFENTRY', '/mod/dataform/view.php?d=$1&amp;eid=$2', array('dataform', 'dataform_entry'));

        return $rules;

    }

    /**
     * Define the restore log rules that will be applied
     * by the {@link restore_logs_processor} when restoring
     * data logs. It must return one array
     * of {@link restore_log_rule} objects
     */
    static public function define_restore_log_rules() {
        $rules = array();

        $rules[] = new restore_log_rule('dataform', 'add', 'view.php?d={dataform}&eid={dataform_entry}', '{dataform}');
        $rules[] = new restore_log_rule('dataform', 'update', 'view.php?d={dataform}&eid={dataform_entry}', '{dataform}');
        $rules[] = new restore_log_rule('dataform', 'view', 'view.php?id={course_module}', '{dataform}');
        $rules[] = new restore_log_rule('dataform', 'entry delete', 'view.php?id={course_module}', '{dataform}');
        $rules[] = new restore_log_rule('dataform', 'fields add', 'fields.php?d={dataform}&fid={dataform_field}', '{dataform_field}');
        $rules[] = new restore_log_rule('dataform', 'fields update', 'fields.php?d={dataform}&fid={dataform_field}', '{dataform_field}');
        $rules[] = new restore_log_rule('dataform', 'fields delete', 'fields.php?d={dataform}', '[name]');
        $rules[] = new restore_log_rule('dataform', 'views add', 'views.php?d={dataform}&vid={dataform_view}', '{dataform_view}');
        $rules[] = new restore_log_rule('dataform', 'views update', 'views.php?d={dataform}&vid={dataform_view}', '{dataform_view}');
        $rules[] = new restore_log_rule('dataform', 'views delete', 'views.php?d={dataform}', '[name]');
        $rules[] = new restore_log_rule('dataform', 'filters add', 'filters.php?d={dataform}&fid={dataform_filter}', '{dataform_filter}');
        $rules[] = new restore_log_rule('dataform', 'filters update', 'filters.php?d={dataform}&fid={dataform_filter}', '{dataform_filter}');
        $rules[] = new restore_log_rule('dataform', 'filters delete', 'filters.php?d={dataform}', '[name]');

        return $rules;
    }

    /**
     * Define the restore log rules that will be applied
     * by the {@link restore_logs_processor} when restoring
     * course logs. It must return one array
     * of {@link restore_log_rule} objects
     *
     * Note this rules are applied when restoring course logs
     * by the restore final task, but are defined here at
     * activity level. All them are rules not linked to any module instance (cmid = 0)
     */
    static public function define_restore_log_rules_for_course() {
        $rules = array();

        $rules[] = new restore_log_rule('dataform', 'view all', 'index.php?id={course}', null);

        return $rules;
    }
}
