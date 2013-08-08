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
 * @package dataformfield
 * @subpackage nanogong
 * @copyright 2011 Itamar Tzadok
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// how much detail we want. Larger number means less detail
// (basically, how many bytes/frames to skip processing)
// the lower the number means longer processing time
define("DETAIL", 20);

define("DEFAULT_WIDTH", 400);
define("DEFAULT_HEIGHT", 100);
define("DEFAULT_FOREGROUND", "#0000FF");
define("DEFAULT_BACKGROUND", "#DDDDDD");

require_once("$CFG->dirroot/mod/dataform/field/file/field_class.php");

class dataformfield_nanogong extends dataformfield_file {
    public $type = 'nanogong';

    /**
     * (Re)generate graphical wav pattern of the recording
     */
    protected function update_content_files1($contentid, $params = null) {

        $fs = get_file_storage();

        // get the wavfile or exit
        $wavfile = null;
        if ($files = $fs->get_area_files($this->df->context->id, 'mod_dataform', 'content', $contentid, 'sortorder', false)) {
            foreach ($files as $file) {
                $pathinfo = pathinfo($file->get_filename());
                if (!empty($pathinfo['extension']) and $pathinfo['extension'] == 'wav') {
                    $wavfile = $file;
                    break;
                }
            } 
        }

        if (!$wavfile) {
            return;
        }
  
        // get user vars from form
        $width = !empty($this->field->param4) ? $this->field->param4 : DEFAULT_WIDTH;
        $height = !empty($this->field->param5) ? $this->field->param5 : DEFAULT_HEIGHT;
        $foreground = !empty($this->field->param6) ? $this->field->param6 : DEFAULT_FOREGROUND;
        $background = !empty($this->field->param7) ? $this->field->param7 : DEFAULT_BACKGROUND;
        $draw_flat = !empty($this->field->param8) ? $this->field->param8 : false;

        $img = false;

        // generate foreground color
        list($r, $g, $b) = html2rgb($foreground);

        $filename = $wavfile->get_filename();
        $filesize = $wavfile->get_filesize();
        $wave = $wavfile->get_content();

        // RIFF chunk
        //$riff = substr($wave, 0, 4);
        //$riffchunksize = bin2hex(substr($wave, 4, 4));
        //$rifftype = substr($wave, 8, 4);
        
        // fmt chunk
        //$fmt = substr($wave, 12, 4);
        //$fmtchunksize = bin2hex(substr($wave, 16, 4));
        //$compression = bin2hex(substr($wave, 20, 2)); // should be 17
        //$numchannels = bin2hex(substr($wave, 22, 2)); // Should be 1
        //$samplerate = bin2hex(substr($wave, 24, 4));
        //$bytepersec = bin2hex(substr($wave, 28, 4));
        //$blockalign = bin2hex(substr($wave, 32, 2));
        //$bitpersample = bin2hex(substr($wave, 34, 2)); // Should be 4
        
        // data chunk
        //$data = substr($wave, 36, 4);
        //$datachunksize = bin2hex(substr($wave, 40, 4));

        // initialize image canvas
        if (!$img) {
            // create original image width based on amount of detail
            // each waveform to be processed with be $height high, but will be condensed
            // and resized later (if specified)
            $img = imagecreatetruecolor($width, $height);

            // fill background of image
            if ($background == "") {
                // transparent background specified
                imagesavealpha($img, true);
                $transparentColor = imagecolorallocatealpha($img, 0, 0, 0, 127);
                imagefill($img, 0, 0, $transparentColor);
            } else {
                list($br, $bg, $bb) = html2rgb($background);
                imagefilledrectangle($img, 0, 0, $width, $height, imagecolorallocate($img, $br, $bg, $bb));
            }
        }

        $chunk = floor(($filesize - 44) / $width);
        $x1 = $y1 = 0;
        for ($x2 = DETAIL; $x2 < $width; $x2 += DETAIL) {

            $data = hexdec(bin2hex($wave[$x2 * $chunk]));
            $y2 = (int) ($data / 255 * $height);

            imageline($img, $x1, $y1, $x2, $y2, imagecolorallocate($img, $r, $g, $b));
            $x1 = $x2;
            $y1 = $y2;
        }
          
        ob_start();
        imagepng($img);
        $imgdata = ob_get_contents();
        ob_end_clean();

        imagedestroy($img);

        if ($imgfile = $fs->get_file($this->df->context->id, 'mod_dataform', 'content', $contentid, '/', 'voicefile.png')) {
            $imgfile->delete();
        }

        $file_record = array(
            'contextid'=> $this->df->context->id,
            'component'=>'mod_dataform',
            'filearea'=>'content',
            'itemid'=> $contentid,
            'filepath'=> '/',
            'filename'=> 'voicefile.png',
            'userid'=> $wavfile->get_userid()
        );

        try {
            $fs->create_file_from_string($file_record, $imgdata);
        } catch (Exception $e) {
            return false;
        }

        return true;
    }

}

/**
 * GENERAL FUNCTIONS
 */
function findValues($byte1, $byte2, $offset = 256){
    $byte1 = hexdec(bin2hex($byte1));
    $byte2 = hexdec(bin2hex($byte2));
    return ($byte1 + ($byte2*$offset));
}
  
/**
 * Great function slightly modified as posted by Minux at
 * http://forums.clantemplates.com/showthread.php?t=133805
 */
function html2rgb($input) {
    $input=($input[0]=="#")?substr($input, 1,6):substr($input, 0,6);
    return array(
         hexdec(substr($input, 0, 2)),
         hexdec(substr($input, 2, 2)),
         hexdec(substr($input, 4, 2))
    );
}
