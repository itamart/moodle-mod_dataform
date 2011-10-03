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

require_once($CFG->dirroot . '/mod/dataform/backup/moodle2/backup_dataform_stepslib.php'); // Because it exists (must)

/**
 * data backup task that provides all the settings and steps to perform one
 * complete backup of the activity
 */
class backup_dataform_activity_task extends backup_activity_task {

    /**
     * Define (add) particular settings this activity can have
     */
    protected function define_my_settings() {
        // No particular settings for this activity
    }

    /**
     * Define (add) particular steps this activity can have
     */
    protected function define_my_steps() {
        // Dataform only has one structure step
        $this->add_step(new backup_dataform_activity_structure_step('dataform_structure', 'dataform.xml'));
    }

    /**
     * Code the transformations to perform in the activity in
     * order to get transportable (encoded) links
     */
    static public function encode_content_links($content) {
        global $CFG;

        $base = preg_quote($CFG->wwwroot,"/");

        // Link to the list of dataforms
        $search="/(".$base."\/mod\/dataform\/index.php\?id\=)([0-9]+)/";
        $content= preg_replace($search, '$@DFINDEX*$2@$', $content);

        // Link to dataform by moduleid
        $search="/(".$base."\/mod\/dataform\/view.php\?id\=)([0-9]+)/";
        $content= preg_replace($search, '$@DFBYID*$2@$', $content);

        /// Link to dataform by dataform id
        $search="/(".$base."\/mod\/dataform\/view.php\?d\=)([0-9]+)/";
        $content= preg_replace($search,'$@DFBYD*$2@$', $content);

        /// Link to one dataform view
        $search="/(".$base."\/mod\/dataform\/view.php\?d\=)([0-9]+)\&(amp;)view\=([0-9]+)/";
        $content= preg_replace($search,'$@DFVIEW*$2*$4@$', $content);

        /// Link to one dataform view and filter
        $search="/(".$base."\/mod\/dataform\/view.php\?d\=)([0-9]+)\&(amp;)view\=([0-9]+)\&(amp;)filter\=([0-9]+)/";
        $content= preg_replace($search,'$@DFVIEWFILTER*$2*$4*$6@$', $content);

        /// Link to one entry of the dataform
        $search="/(".$base."\/mod\/dataform\/view.php\?d\=)([0-9]+)\&(amp;)eid\=([0-9]+)/";
        $content= preg_replace($search,'$@DFENTRY*$2*$4@$', $content);

        return $content;
    }
}
