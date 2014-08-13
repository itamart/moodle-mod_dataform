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
 * @package dataformfield
 * @subpackage time
 * @copyright 2011 Itamar Tzadok
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class dataformfield_time_time extends mod_dataform\pluginbase\dataformfield {
    public $dateonly;
    public $masked;
    public $startyear;
    public $stopyear;
    public $displayformat;

    public function __construct($field) {
        parent::__construct($field);
        $this->date_only = $this->param1;
        $this->masked = $this->param5;
        $this->start_year = $this->param2;
        $this->stop_year = $this->param3;
        $this->display_format = $this->param4;
    }

    /**
     *
     */
    protected function content_names() {
        return array('', 'year', 'month', 'day', 'hour', 'minute', 'enabled');
    }

    /**
     *
     */
    protected function format_content($entry, array $values = null) {
        $fieldid = $this->id;
        $oldcontents = array();
        $contents = array();
        // old contents
        if (isset($entry->{"c{$fieldid}_content"})) {
            $oldcontents[] = $entry->{"c{$fieldid}_content"};
        }

        // new contents
        $timestamp = null;
        if (!empty($values)) {
            if (count($values) === 1) {
                $values = reset($values);
            }

            if (!is_array($values)) {
                // assuming timestamp is passed (e.g. in import)
                $timestamp = $values;

            } else {
                // assuming any of year, month, day, hour, minute is passed
                $enabled = $year = $month = $day = $hour = $minute = 0;
                foreach ($values as $name => $val) {
                    if (!empty($name)) {          // the time unit
                        ${$name} = $val;
                    }
                }
                if ($enabled) {
                    if ($year or $month or $day or $hour or $minute) {
                        $timestamp = makentrytimestamp($year, $month, $day, $hour, $minute, 0);
                    }
                }
            }
        }
        $contents[] = $timestamp;
        return array($contents, $oldcontents);
    }

    /**
     *
     */
    public function get_search_sql($search) {
        list($element, $not, $operator, $value) = $search;

        // Time list separated by ..
        if (strpos($value, '..') !== false) {
            $value = array_map('strtotime', explode('..', $value));
            // Must have valid timestamps.
            if (in_array(false, $value, true)) {
                return null;
            }
        } else {
            $value = strtotime($value);
            // Must have valid timestamps.
            if ($value === false) {
                return null;
            }
        }

        return parent::get_search_sql(array($element, $not, $operator, $value));
    }

    /**
     * Overriding parent method to process time strings.
     * Process the first pattern of the field and expects timestamp or valid time string.
     * (@link dataformfield::prepare_import_content()}
     *
     * @return bool
     */
    public function prepare_import_content($data, $importsettings, $csvrecord = null, $entryid = null) {
        // Import only from csv
        if (!$csvrecord) {
            return $data;
        }

        $fieldid = $this->id;
        $csvname = '';

        $setting = reset($importsettings);
        if (!empty($setting['name'])) {
            $csvname = $setting['name'];
        }

        if ($csvname and isset($csvrecord[$csvname]) and $csvrecord[$csvname] !== '') {
            $timestr = !empty($csvrecord[$csvname]) ? $csvrecord[$csvname] : null;

            if ($timestr) {
                if (((string) (int) $timestr === $timestr)
                        && ($timestr <= PHP_INT_MAX)
                        && ($timestr >= ~PHP_INT_MAX)) {
                    // It's a timestamp
                    $data->{"field_{$fieldid}_{$entryid}"} = $timestr;

                } else if ($timestr = strtotime($timestr)) {
                    // It's a valid time string
                    $data->{"field_{$fieldid}_{$entryid}"} = $timestr;
                }
            }
        }

        return $data;
    }

    /**
     *
     */
    public function get_sql_compare_text($column = 'content') {
        global $DB;
        return $DB->sql_cast_char2int("c{$this->id}.$column", true);
    }

}
