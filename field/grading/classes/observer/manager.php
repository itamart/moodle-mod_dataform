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

namespace dataformfield_grading\observer;

/**
 * Observers manager class
 */
class manager {

    /**
     * Returns list of dataform observers.
     *
     * @return array
     */
    public static function observers() {
        global $CFG;

        $observers = array();
        foreach (get_directory_list("$CFG->dirroot/mod/dataform/field/grading/classes/observer") as $filename) {
            $basename = basename($filename, '.php');
            if ($basename == 'manager') {
                continue;
            }
            $observer = '\dataformfield_grading\observer\\'. $basename;
            $observers = array_merge($observers, $observer::observers());
        }
        return $observers;
    }

}
