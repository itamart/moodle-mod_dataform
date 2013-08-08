/**
 * This file is part of the Dataform module for Moodle - http://moodle.org/.
 *
 * @package mod-dataform
 * @subpackage dataformfield-nanogong
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

/**
 * Category questions loader
 */
M.dataformfield_nanogong_upload_recording = {};

M.dataformfield_nanogong_upload_recording.init = function(Y, options) {
    YUI().use('node-base', 'event-base', function(Y) {

        var fieldname = options.fieldname;
        var filename = options.filename;
        var actionurl = options.acturl;

        Y.on('click', function(e) {

            var recorder = document.getElementById('id_' + fieldname + '_filemanager');
            if (recorder == null) {
                alert('recorder not found');
                return;
            }
            
            var duration = parseInt(recorder.sendGongRequest('GetMediaDuration', 'audio')) || 0;
            if (duration <= 0) {
                alert('no recording found');
                return;
            }
            
            var ret = recorder.sendGongRequest('PostToForm',
                                                actionurl,
                                                'repo_upload_file',
                                                '',
                                                filename);
            if (ret == null || ret == '') {
                alert('Failed to submit the voice recording');
            } else {
                alert(ret);
            }
        }, '#id_' + fieldname + '_upload');
    });        
};
