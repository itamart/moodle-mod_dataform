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

defined('MOODLE_INTERNAL') or die;

require_once("$CFG->dirroot/mod/dataform/backup/moodle2/restore_dataform_stepslib.php"); // Because it exists (must)

/**
 * dataform restore task that provides all the settings and steps to perform one
 * complete restore of the activity
 */
class restore_dataform_activity_task extends restore_activity_task {

    protected $ownerid = 0; // user id of designated owner of content

    /**
     * 
     */
    public function get_old_moduleid() {
        return $this->oldmoduleid;
    }

    /**
     * 
     */
    public function set_ownerid($ownerid) {
        $this->ownerid = $ownerid;
    }

    /**
     * 
     */
    public function get_ownerid() {
        return $this->ownerid;
    }

    /**
     * 
     */
    public function get_comment_mapping_itemname($commentarea) {
        if ($commentarea == 'entry') {
            return 'dataform_entry';
        } else if ($commentarea == 'activity') {
            return 'user';
        }
    }

    /**
     * Override to remove the course module step if restoring a preset
     */
    public function build() {

        // If restoring into a given activity remove the module_info step b/c there
        // is no need to create a module instance
        if ($this->get_activityid()) {

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

            // Advanced grading methods attached to the module
            $this->add_step(new restore_activity_grading_structure_step('activity_grading', 'grading.xml'));

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

        } else {
            parent::build();
        }
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
        
        $rules[] = new restore_decode_rule('DFVIEWBYID', '/mod/dataform/view.php?id=$1', 'course_module');
        $rules[] = new restore_decode_rule('DFEMBEDBYID', '/mod/dataform/embed.php?id=$1', 'course_module');

        $rules[] = new restore_decode_rule('DFVIEWBYD', '/mod/dataform/view.php?d=$1', 'dataform');
        $rules[] = new restore_decode_rule('DFEMBEDBYD', '/mod/dataform/embed.php?d=$1', 'dataform');

        $rules[] = new restore_decode_rule('DFVIEWVIEW', '/mod/dataform/view.php?d=$1&amp;view=$2', array('dataform', 'dataform_view'));
        $rules[] = new restore_decode_rule('DFEMBEDVIEW', '/mod/dataform/embed.php?d=$1&amp;view=$2', array('dataform', 'dataform_view'));

        $rules[] = new restore_decode_rule('DFVIEWVIEWFILTER', '/mod/dataform/view.php?d=$1&amp;view=$2&amp;filter=$3', array('dataform', 'dataform_view', 'dataform_filter'));
        $rules[] = new restore_decode_rule('DFEMBEDVIEWFILTER', '/mod/dataform/embed.php?d=$1&amp;view=$2&amp;filter=$3', array('dataform', 'dataform_view', 'dataform_filter'));

        $rules[] = new restore_decode_rule('DFVIEWENTRY', '/mod/dataform/view.php?d=$1&amp;eid=$2', array('dataform', 'dataform_entry'));
        $rules[] = new restore_decode_rule('DFEMBEDENTRY', '/mod/dataform/embed.php?d=$1&amp;eid=$2', array('dataform', 'dataform_entry'));

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
        $rules[] = new restore_log_rule('dataform', 'fields add', 'field/index.php?d={dataform}&fid={dataform_field}', '{dataform_field}');
        $rules[] = new restore_log_rule('dataform', 'fields update', 'field/index.php?d={dataform}&fid={dataform_field}', '{dataform_field}');
        $rules[] = new restore_log_rule('dataform', 'fields delete', 'field/index.php?d={dataform}', '[name]');
        $rules[] = new restore_log_rule('dataform', 'views add', 'view/index.php?d={dataform}&vid={dataform_view}', '{dataform_view}');
        $rules[] = new restore_log_rule('dataform', 'views update', 'view/index.php?d={dataform}&vid={dataform_view}', '{dataform_view}');
        $rules[] = new restore_log_rule('dataform', 'views delete', 'view/index.php?d={dataform}', '[name]');
        $rules[] = new restore_log_rule('dataform', 'filters add', 'filter/index.php?d={dataform}&fid={dataform_filter}', '{dataform_filter}');
        $rules[] = new restore_log_rule('dataform', 'filters update', 'filter/index.php?d={dataform}&fid={dataform_filter}', '{dataform_filter}');
        $rules[] = new restore_log_rule('dataform', 'filters delete', 'filter/index.php?d={dataform}', '[name]');
        $rules[] = new restore_log_rule('dataform', 'rules add', 'rule/index.php?d={dataform}&rid={dataform_rule}', '{dataform_rule}');
        $rules[] = new restore_log_rule('dataform', 'rules update', 'rule/index.php?d={dataform}&rid={dataform_rule}', '{dataform_rule}');
        $rules[] = new restore_log_rule('dataform', 'rules delete', 'rule/index.php?d={dataform}', '[name]');

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
