<?php

/**
 * This file is part of the Dataform module for Moodle - http://moodle.org/.
 *
 * @package mod-dataform
 * @subpackage field-_user
 * @copyright 2011 Itamar Tzadok
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * The Dataform has been developed as an enhanced counterpart
 * of Moodle's Database activity module (1.9.11+ (20110323)).
 * To the extent that Dataform code corresponds to Database code,
 * certain copyrights on Database module may obtain.
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

require_once($CFG->dirroot.'/mod/dataform/field/field_class.php');

class dataform_field__user extends dataform_field_base {

    public $type = '_user';

    /**
     * 
     */
    public function patterns($tags = null, $entry = null, $edit = false, $editable = false) {
        global $CFG, $USER, $DB, $OUTPUT;
        
        $authorinfo = array(
                    '##author:firstname##',
                    '##author:lastname##',
                    '##author:username##',
                    '##author:id##',
                    '##author:idnumber##',
                    '##author:picture##',
                    '##author:picturelarge##');

        // if no tags requested, return select menu
        if (is_null($tags)) {
            $patterns = array('authorinfo' => array('authorinfo' => array()));
                               
            // TODO use get strings
            $pattern = '##author:'. $this->field->internalname. '##';
            $patterns['authorinfo']['authorinfo'][$pattern] = $pattern;
            if ($this->field->internalname == 'picture') {
                $pattern = '##author:'. $this->field->internalname. 'large##';
                $patterns['authorinfo']['authorinfo'][$pattern] = $pattern;
            }

        } else {

            $patterns = array();

            if ($entry->id < 0) { // new record (0)
                $entry = new object();
                $entry->userid = $USER->id;
                $entry->username = $USER->username;
                $entry->firstname = $USER->firstname;
                $entry->lastname = $USER->lastname;
                $entry->idnumber = $USER->idnumber;

                $user = $USER;
            } else {
                $user = new object();
                foreach (explode(',', user_picture::fields()) as $userfield) {
                    if ($userfield == 'id') {
                        $user->id = $entry->uid;
                    } else {
                        $user->{$userfield} = $entry->{$userfield};
                    }
                }
            }
            
            // TODO
            foreach ($tags as $tag) {
                // no edit mode for this field
                 switch ($this->field->internalname) {
                    case 'name':
                        $patterns['##author:name##'] = array('html', html_writer::link(
                                            new moodle_url('/user/view.php', array('id' => $entry->userid, 'course' => $this->df->course->id)),
                                            fullname($entry)));                            
                        break;

                    case 'firstname':
                        $patterns['##author:firstname##'] = array('html', $entry->firstname);
                        break;

                    case 'lastname':
                        $patterns['##author:lastname##'] = array('html', $entry->lastname);
                        break;

                    case 'username':
                        $patterns['authorinfo']['##author:username##'] = array('html', $entry->username);
                        break;

                    case 'id':
                        $patterns['##author:id##'] = array('html', $entry->userid);
                        break;

                    case 'idnumber':
                        $patterns['##author:idnumber##'] = array('html', $entry->idnumber);
                        break;

                    case 'picture':
                        $patterns['##author:picture##'] = array('html', $OUTPUT->user_picture($user, array('courseid' => $this->df->course->id)));
                        $patterns['##author:picturelarge##'] = array('html', $OUTPUT->user_picture($user, array('courseid' => $this->df->course->id, 'size' => 100)));
                        break;
                }
            }
        }
        
        return $patterns;
    }
    
    /**
     *
     */
    public function get_sql_compare_text() {
        global $DB;
        // the sort sql here returns the field's sql name       
        return $DB->sql_compare_text($this->get_sort_sql());    
    }

    /**
     * 
     */
    public function get_sort_sql() {
        if ($this->field->internalname != 'picture') {
            if ($this->field->internalname == 'name') {
                $internalname = 'id';
            } else {
                $internalname = $this->field->internalname;
            }
            return 'u.'. $internalname;
        } else {
            return '';
        }
    }

    /**
     * returns an array of distinct content of the field
     */
    public function get_distinct_content($sortdir = 0) {
        global $CFG, $DB;
        
        $sortdir = $sortdir ? 'DESC' : 'ASC';
        $contentfull = $this->get_sort_sql();
        $sql = "SELECT DISTINCT $contentfull 
                         FROM {user} u 
                         WHERE $contentfull IS NOT NULL 
                         ORDER BY $contentfull $sortdir";

        $distinctvalues = array();
        if ($options = $DB->get_records_sql($sql)) {
            if ($this->field->internalname == 'name') {
                $internalname = 'id';
            } else {
                $internalname = $this->field->internalname;
            }
            foreach ($options as $data) {
                $value = $data->{$internalname};
                if ($value === '') {
                    continue;
                }
                $distinctvalues[] = $value;
            }
        }
        return $distinctvalues;
    }

    /**
     * 
     */
    public function update_content($entry, array $values = null) {
        return true;
    }

    /**
     *
     */
    public function export_text_supported() {
        if ($this->field->internalname == 'picture') {
            return false;
        } else {
            return true;
        }
    }

    /**
     *
     */
    public function export_text_value($entry) {
        if ($this->field->internalname != 'picture') {
            if ($this->field->internalname == 'name' or $this->field->internalname == 'id') {
                $internalname = 'userid';
            } else {
                $internalname = $this->field->internalname;
            }
            return $entry->{$internalname};
        } else {
            return '';
        }
    }

}
