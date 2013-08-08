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
 * Define all the backup steps that will be used by the backup_dataform_activity_task
 */

/**
 * Define the complete data structure for backup, with file and id annotations
 */
class backup_dataform_activity_structure_step extends backup_activity_structure_step {

    protected function define_structure() {

        // To know if we are including userinfo
        $userinfo = $this->get_setting_value('userinfo');

        // Define each element separated
        $dataform = new backup_nested_element('dataform', array('id'), array(
            'name', 'intro', 'introformat', 'timemodified', 
            'timeavailable', 'timedue', 'timeinterval', 'intervalcount', 'allowlate',
            'grade', 'grademethod', 'anonymous', 'notification', 'notificationformat',
            'entriesrequired', 'entriestoview', 'maxentries', 
            'timelimit', 'approval', 'grouped', 'rating', 'comments',
            'locks', 'singleedit', 'singleview',
            'rssarticles', 'rss', 'css', 'cssincludes', 'js', 'jsincludes',
            'defaultview', 'defaultfilter'));
 
        $module = new backup_nested_element('module', array('id'), array('groupmode'));
        
        $fields = new backup_nested_element('fields');
        $field = new backup_nested_element('field', array('id'), array(
            'type', 'name', 'description', 'visible', 'edits', 'label',
            'param1', 'param2', 'param3', 'param4', 'param5', 
            'param6', 'param7', 'param8', 'param9', 'param10'));

        $filters = new backup_nested_element('filters');
        $filter = new backup_nested_element('filter', array('id'), array(
            'name', 'description',
            'visible', 'perpage', 'selection', 'groupby',
            'search', 'customsort', 'customsearch'));

        $views = new backup_nested_element('views');
        $view = new backup_nested_element('view', array('id'), array(
            'type', 'name', 'description',
            'visible', 'perpage', 'groupby', 'filter', 'section', 'sectionpos', 'patterns',
            'param1', 'param2', 'param3', 'param4', 'param5', 
            'param6', 'param7', 'param8', 'param9', 'param10'));

        $rules = new backup_nested_element('rules');
        $rule = new backup_nested_element('rule', array('id'), array(
            'type', 'name', 'description', 'enabled',
            'param1', 'param2', 'param3', 'param4', 'param5', 
            'param6', 'param7', 'param8', 'param9', 'param10'));

        $entries = new backup_nested_element('entries');
        $entry = new backup_nested_element('entry', array('id'), array(
            'userid', 'groupid', 'timecreated', 'timemodified', 'approved'));

        $contents = new backup_nested_element('contents');
        $content = new backup_nested_element('content', array('id'), array(
            'fieldid', 'content', 'content1', 'content2', 'content3', 'content4'));

        $ratings = new backup_nested_element('ratings');
        $rating = new backup_nested_element('rating', array('id'), array(
            'component', 'ratingarea', 'scaleid', 'value', 'userid', 'timecreated', 'timemodified'));

        $grades = new backup_nested_element('grades');
        $grade = new backup_nested_element('grade', array('id'), array(
            'component', 'ratingarea', 'scaleid', 'value', 'userid', 'timecreated', 'timemodified'));

        // Build the tree
        $dataform->add_child($module);

        $dataform->add_child($fields);
        $fields->add_child($field);

        $dataform->add_child($filters);
        $filters->add_child($filter);

        $dataform->add_child($views);
        $views->add_child($view);

        $dataform->add_child($rules);
        $rules->add_child($rule);

        $dataform->add_child($entries);
        $entries->add_child($entry);

        $entry->add_child($contents);
        $contents->add_child($content);

        $entry->add_child($ratings);
        $ratings->add_child($rating);

        $dataform->add_child($grades);
        $grades->add_child($grade);

        // Define sources
        $dataform->set_source_table('dataform', array('id' => backup::VAR_ACTIVITYID));
        $module->set_source_table('course_modules', array('id' => backup::VAR_MODID));
        $field->set_source_table('dataform_fields', array('dataid' => backup::VAR_PARENTID));
        $filter->set_source_table('dataform_filters', array('dataid' => backup::VAR_PARENTID));
        $view->set_source_table('dataform_views', array('dataid' => backup::VAR_PARENTID));
        $rule->set_source_table('dataform_rules', array('dataid' => backup::VAR_PARENTID));

        // All the rest of elements only happen if we are including user info
        if ($userinfo) {
            $entry->set_source_table('dataform_entries', array('dataid' => backup::VAR_PARENTID));
            $content->set_source_table('dataform_contents', array('entryid' => backup::VAR_PARENTID));

            // Entry ratings
            $rating->set_source_table('rating', array(
                'contextid'  => backup::VAR_CONTEXTID,
                'itemid'     => backup::VAR_PARENTID,
                'component'  => backup_helper::is_sqlparam('mod_dataform'),
                'ratingarea' => backup_helper::is_sqlparam('entry'))
            );
            $rating->set_source_alias('rating', 'value');

            // Activity grade
            $grade->set_source_table('rating', array(
                'contextid'  => backup::VAR_CONTEXTID,
                'component'  => backup_helper::is_sqlparam('mod_dataform'),
                'ratingarea' => backup_helper::is_sqlparam('activity'))
            );
            $grade->set_source_alias('rating', 'value');
        }

        // Define id annotations
        $dataform->annotate_ids('scale', 'grade');
        $dataform->annotate_ids('scale', 'rating');

        $entry->annotate_ids('user', 'userid');
        $entry->annotate_ids('group', 'groupid');

        $rating->annotate_ids('scale', 'scaleid');
        $rating->annotate_ids('user', 'userid');

        // Define file annotations
        $dataform->annotate_files('mod_dataform', 'intro', null); // This file area hasn't itemid
        $view->annotate_files('mod_dataform', 'view', 'id'); // By view->id
        $content->annotate_files('mod_dataform', 'content', 'id'); // By content->id

        // Return the root element (data), wrapped into standard activity structure
        return $this->prepare_activity_structure($dataform);
    }
}
