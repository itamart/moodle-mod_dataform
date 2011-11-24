<?php

/**
 * This file is part of the Dataform module for Moodle - http://moodle.org/.
 *
 * @package mod-dataform
 * @package field-nanogong
 * @copyright 2011 Itamar Tzadok
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * The Dataform has been developed as an enhanced counterpart
 * of Moodle's Database activity module (1.9.11+ (20110323)).
 * To the extent that Dataform code corresponds to Database code,
 * certain copyrights on the Database module may obtain.
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

defined('MOODLE_INTERNAL') or die();

require_once("$CFG->dirroot/mod/dataform/field/file/field_patterns.php");

/**
 *
 */
class mod_dataform_field_nanogong_patterns extends mod_dataform_field_file_patterns {

    /**
     * 
     */
    public function pluginfile_patterns() {
        return array();
    }

    /**
     * 
     */
    protected function display_file($file, $path, $altname, $params = null) {
        global $CFG, $OUTPUT;

        $filename = $file->get_filename();
        $displayname = $altname ? $altname : $filename;
        
        if (!empty($params['url'])) {
            return new moodle_url("$path/$filename");
                    
        } else {
        
            $fileurl = new moodle_url("$path/$filename");
       
            $paramfileurl = html_writer::empty_tag('param', array('name' => 'SoundFileURL',
                                                            'value' => $fileurl));
            $paramaudiolevel = html_writer::empty_tag('param', array('name' => 'ShowAudioLevel',
                                                            'value' => 'true'));
            $paramtime = html_writer::empty_tag('param', array('name' => 'ShowTime',
                                                            'value' => 'true'));
            $paramrecordbutton = html_writer::empty_tag('param', array('name' => 'ShowRecordButton',
                                                            'value' => 'false'));

            $applet = html_writer::tag('applet',
                                        $paramfileurl. 
                                        $paramaudiolevel. 
                                        $paramtime. 
                                        $paramrecordbutton,
                                        array('archive' => new moodle_url('/mod/dataform/field/nanogong/applet/nanogong.jar'),
                                                'code' => 'gong.NanoGong',
                                                'width' => '100%',
                                                'height' => '40px',
                                                'style' => 'max-width:180px;'));
            
            $appletwrapper = html_writer::tag('div', $applet);
            return $appletwrapper;

        }
    }

}
