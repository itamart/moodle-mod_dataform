<?php
// This file is part of Moodle - http://moodle.org/
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
 * @package dataformfield_grading
 * @copyright 2018 Itamar Tzadok
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class dataformfield_grading_grading extends mod_dataform\pluginbase\dataformfield {
    protected $_config;

    /**
     * Returns the field config data that is stored in param1.
     *
     * @return array|null.
     */
    public function get_config() {
        if (!isset($this->_config)) {
            $this->_config = [];
            if ($this->param1) {
                $this->_config = (array) json_decode($this->param1);
            }
        }
        return $this->_config;
    }

    /**
     * Sets the field config data regardless of what is stored in param1.
     *
     * @return void.
     */
    public function set_config($config) {
        $this->_config = (array) $config;
    }

    /**
     *
     */
    protected function format_content($entry, array $values = null) {
        $fieldid = $this->id;

        $oldcontents = array();
        $contents = array();

        // Old contents.
        if (!empty($entry->{"c{$fieldid}_content"})) {
            $oldcontents[] = $entry->{"c{$fieldid}_content"};
        }

        // New contents.
        $contents[] = time();
        // Also queue the grading task.
        $this->queue_grading_task($this->df->id, $entry->userid);

        return array($contents, $oldcontents);
    }

    /**
     *
     */
    public function can_update_grade($entry) {
        $config = $this->config;
        $multiupdate = !empty($config['multiupdate']);
        $lastupdated = null;
        if (!empty($entry->{"c{$this->id}_content"})) {
            $lastupdated = $entry->{"c{$this->id}_content"};
        }
        return ($multiupdate or !$lastupdated);
    }

    /**
     *
     */
    public function queue_grading_task($dataformid, $entryuserid) {
        // Create the task instance
        $gu = new \mod_dataform\task\grade_update;
        // add custom data
        $gu->set_custom_data(array(
           'dataformid' => $dataformid,
           'userid' => $entryuserid,
        ));

        // queue it
        \core\task\manager::queue_adhoc_task($gu);
    }
}
