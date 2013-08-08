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
 * @subpackage _rating
 * @copyright 2011 Itamar Tzadok
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') or die;

require_once("$CFG->dirroot/mod/dataform/field/renderer.php");

/**
 *
 */
class dataformfield__rating_renderer extends dataformfield_renderer {

    /**
     * 
     */
    protected function replacements(array $tags = null, $entry = null, array $options = null) {
        global $CFG, $DB;
        
        $field = $this->_field;
        $fieldname = $field->get('internalname');
        $edit = !empty($options['edit']) ? $options['edit'] : false;

        // no edit mode
        if ($edit or !$this->_field->df()->data->rating) {
            if ($tags) {
                $replacements = array();
                foreach ($tags as $tag) {
                    switch($tag) {
                        case '##ratings:count##':
                        case '##ratings:avg##': 
                        case '##ratings:max##':
                        case '##ratings:min##':
                        case '##ratings:sum##':
                            $str = '-'; break;
                        default:
                            $str = '';
                    }
                    $replacements[$tag] = $str ? array('html', $str) : '';
                }

                return $replacements;
                
            } else {
                return null;
            }
        }        

        require_once("$CFG->dirroot/mod/dataform/field/_rating/lib.php");
        $rm = new dataform_rating_manager();
        // Get entry rating objects
        if ($entry->id > 0) {
            $options = new object;
            $options->context = $field->df()->context;
            $options->component = 'mod_dataform';
            $options->ratingarea = 'entry';
            // ugly hack to work around the exception in generate_settings
            $options->aggregate = RATING_AGGREGATE_COUNT;  
            // TODO check when scaleid is empty   
            $options->scaleid = !empty($entry->scaleid) ? $entry->scaleid : $field->df()->data->rating;

            $rec = new object;
            $rec->itemid = $entry->id;
            $rec->context = $field->df()->context;
            $rec->component = 'mod_dataform';
            $rec->ratingarea = 'entry';
            $rec->settings = $rm->get_rating_settings_object($options);
            $rec->aggregate = array_keys($rm->get_aggregate_types());     
            $rec->scaleid = $entry->scaleid;
            $rec->userid = $entry->ratinguserid;
            $rec->id = $entry->ratingid;
            $rec->usersrating = $entry->usersrating;
            $rec->numratings = $entry->numratings;
            $rec->avgratings = $entry->avgratings;
            $rec->sumratings = $entry->sumratings;
            $rec->maxratings = $entry->maxratings;
            $rec->minratings = $entry->minratings;

            $entry->rating = $rm->get_rating_object($entry, $rec);

            $aggravg = round($entry->rating->aggregate[dataformfield__rating::AGGREGATE_AVG], 2);
            $aggrmax = round($entry->rating->aggregate[dataformfield__rating::AGGREGATE_MAX], 2);
            $aggrmin = round($entry->rating->aggregate[dataformfield__rating::AGGREGATE_MIN], 2);
            $aggrsum = round($entry->rating->aggregate[dataformfield__rating::AGGREGATE_SUM], 2);

            // Get all ratings for inline view
            if (in_array('##ratings:viewinline##', $tags)) {
                static $allratings = false;
                static $ratingrecords = null;
                if (!$allratings) {
                    $allratings = true;
                    list($sql, $params) = $rm->get_sql_all($options, false);        
                    $ratingrecords = $DB->get_records_sql($sql, $params);
                }
                if ($ratingrecords) {
                    foreach ($ratingrecords as $recordid => $raterecord) {
                        if ($raterecord->itemid < $entry->id) {
                            continue;
                        }
                        // Break if we already found the respective records
                        if ($raterecord->itemid > $entry->id) {
                            continue;
                        }
                        // Attach the rating record to the entry
                        if (!isset($entry->rating->records)) {
                            $entry->rating->records = array();
                        }
                        $entry->rating->records[$recordid] = $raterecord;
                    }   
                }   
            }
        }                

        // no edit mode for this field so just return html
        $replacements = array();
        foreach ($tags as $tag) {
            if ($entry->id > 0 and !empty($entry->rating)) {            
                switch($tag) {
                    case '##ratings:count##':
                        $str = !empty($entry->rating->count) ? $entry->rating->count : '-';
                        break;                            
                    case '##ratings:avg##': 
                        $str = !empty($aggravg) ? $aggravg : '-';
                        break;                            
                    case '##ratings:max##':
                        $str = !empty($aggrmax) ? $aggrmax : '-';
                        break;                           
                    case '##ratings:min##':
                        $str = !empty($aggrmin) ? $aggrmin : '-';
                        break;                            
                    case '##ratings:sum##':
                        $str = !empty($aggrsum) ? $aggrsum : '-';
                        break;                            
                    case '##ratings:view##':
                    case '##ratings:viewurl##':
                        $str = $this->display_view($entry, $tag);
                        break;
                    case '##ratings:viewinline##':
                        $str = $this->display_view_inline($entry);
                        break;
                    case '##ratings:rate##':
                        $str = $this->render_rating($entry);
                        break;
                    case '##ratings:avg:bar##':
                        $str = $this->display_bar($entry, $aggravg);
                        break;
                    case '##ratings:avg:star##':
                        $str = $this->display_star($entry, $aggravg);
                        break;
                    default:
                        $str = '';
                }
                $replacements[$tag] = array('html', $str);
            }            
        }                    
        return $replacements;
    }

    /**
     * 
     */
    public function get_aggregations($patterns) {

        $aggr = array(
            dataformfield__rating::AGGREGATE_AVG => '##ratings:avg##',
            dataformfield__rating::AGGREGATE_MAX => '##ratings:max##',
            dataformfield__rating::AGGREGATE_MIN => '##ratings:min##',
            dataformfield__rating::AGGREGATE_SUM => '##ratings:sum##'
        );
        if ($aggregations = array_intersect($aggr, $patterns)) {
            return array_keys($aggregations);
        } else {
            return null;
        }
    }

    /**
     * 
     */
    protected function display_view($entry, $tag) {
        global $OUTPUT;
        
       if (isset($entry->rating)) {
            $rating = $entry->rating;
            if ($rating->settings->permissions->viewall
                and $rating->settings->pluginpermissions->viewall) {

                $nonpopuplink = $rating->get_view_ratings_url();
                $popuplink = $rating->get_view_ratings_url(true);
                $popupaction = new popup_action('click', $popuplink, 'ratings', array('height' => 400, 'width' => 600));
                
                if ($tag == '##ratings:view##') {
                    return $OUTPUT->action_link($nonpopuplink, 'view all', $popupaction);
                } else {
                    return $popuplink;
                }
            }
        }
        return '';
    }
    
    /**
     * 
     */
    protected function display_view_inline($entry) {
        global $OUTPUT;
        
        if (isset($entry->rating)) {
            $rating = $entry->rating;
            if ($rating->settings->permissions->viewall
                        and $rating->settings->pluginpermissions->viewall
                        and !empty($rating->records)) {
                $scalemenu = make_grades_menu($rating->settings->scale->id);
                
                $table = new html_table;
                $table->cellpadding = 3;
                $table->cellspacing = 3;
                $table->attributes['class'] = 'generalbox ratingtable';
                $table->colclasses = array('', 'firstname', 'rating', 'time');
                $table->data = array();

                // If the scale was changed after ratings were submitted some ratings may have a value above the current maximum
                // We can't just do count($scalemenu) - 1 as custom scales start at index 1, not 0
                $maxrating = $rating->settings->scale->max;

                foreach ($rating->records as $raterecord) {
                    //Undo the aliasing of the user id column from user_picture::fields()
                    //we could clone the rating object or preserve the rating id if we needed it again
                    //but we don't
                    $raterecord->id = $raterecord->userid;

                    $row = new html_table_row();
                    $row->attributes['class'] = 'ratingitemheader';
                    $row->cells[] = $OUTPUT->user_picture($raterecord, array('courseid' => $this->_field->df()->course->id));
                    $row->cells[] = fullname($raterecord);
                    if ($raterecord->rating > $maxrating) {
                        $raterecord->rating = $maxrating;
                    }
                    $row->cells[] = $scalemenu[$raterecord->rating];
                    $row->cells[] = userdate($raterecord->timemodified, get_string('strftimedate', 'langconfig'));
                    $table->data[] = $row;
                }
                return html_writer::table($table);
            }
        }
        return '';
    }
    
    /**
     * 
     */
    protected function display_bar($entry, $value) {
       if (isset($entry->rating) and $value) {
            $rating = $entry->rating;

            $width = round($value/$rating->settings->scale->max*100);
            $displayvalue = round($value, 2);
            $bar = html_writer::tag('div', '.',array('style' => "width:$width%;height:100%;background:gold;color:gold"));
            return $bar;
        }
        return '';
    }
    
    /**
     * 
     */
    protected function display_star($entry, $value) {
        global $OUTPUT;
        
        if (isset($entry->rating)) {
            $rating = $entry->rating;
            $numstars = $rating->settings->scale->max;
            $width = $numstars*20;

            $innerstyle = 'width:100%;height:19px;position:absolute;top:0;left:0;';
            $bgdiv = html_writer::tag('div', '.', array('style' => "background:#ccc;color:#ccc;$innerstyle"));
            $bar = html_writer::tag('div', $this->display_bar($entry, $value), array('style' => "z-index:5;$innerstyle"));
            $stars = implode('', array_fill(0, $numstars, $OUTPUT->pix_icon('star_grey', '', 'dataformfield__rating', array('style' => 'float:left;'))));
            $starsdiv = html_writer::tag('div', $stars, array('style' => "z-index:10;$innerstyle"));
            $wrapper = html_writer::tag('div', "$bgdiv $bar $starsdiv", array('style' => "width:{$width}px;position:relative;"));
            return $wrapper;
        }
        return '';
    }
    
    /**
     * 
     */
    public function render_rating($entry) {
        global $CFG, $USER, $PAGE;

        $ratinghtml = '';
        
        if (isset($entry->rating)) {
            $rating = $entry->rating;

    /*
            if ($rating->settings->aggregationmethod == RATING_AGGREGATE_NONE) {
                return null;//ratings are turned off
            }
    */
            $rm = new dataform_rating_manager();
            // Initialise the JavaScript so ratings can be done by AJAX.
            $rm->initialise_rating_javascript($PAGE);

            $strrate = get_string("rate", "rating");
            $ratinghtml = ''; //the string we'll return

            // hack to work around the js updating imposed text
            $ratinghtml .= html_writer::tag('span', '', array('id' => "ratingaggregate{$rating->itemid}",
                                                                'style' => 'display:none;'));
            $ratinghtml .= html_writer::tag('span', '', array('id' => "ratingcount{$rating->itemid}",
                                                                'style' => 'display:none;'));
            
            $formstart = null;
            // if the item doesn't belong to the current user, the user has permission to rate
            // and we're within the assessable period
            if ($rating->user_can_rate() or has_capability('mod/dataform:manageratings', $this->_field->df()->context)) {

                $rateurl = $rating->get_rate_url();
                $inputs = $rateurl->params();

                //start the rating form
                $formattrs = array(
                    'id'     => "postrating{$rating->itemid}",
                    'class'  => 'postratingform',
                    'method' => 'post',
                    'action' => $rateurl->out_omit_querystring()
                );
                $formstart  = html_writer::start_tag('form', $formattrs);
                $formstart .= html_writer::start_tag('div', array('class' => 'ratingform'));

                // add the hidden inputs
                foreach ($inputs as $name => $value) {
                    $attributes = array('type' => 'hidden', 'class' => 'ratinginput', 'name' => $name, 'value' => $value);
                    $formstart .= html_writer::empty_tag('input', $attributes);
                }


                $ratinghtml = $formstart.$ratinghtml;

                $scalearray = array(RATING_UNSET_RATING => $strrate.'...') + $rating->settings->scale->scaleitems;
                $scaleattrs = array('class'=>'postratingmenu ratinginput','id'=>'menurating'.$rating->itemid);
                $ratinghtml .= html_writer::select($scalearray, 'rating', $rating->rating, false, $scaleattrs);

                //output submit button
                $ratinghtml .= html_writer::start_tag('span', array('class'=>"ratingsubmit"));

                $attributes = array('type' => 'submit', 'class' => 'postratingmenusubmit', 'id' => 'postratingsubmit'.$rating->itemid, 'value' => s(get_string('rate', 'rating')));
                $ratinghtml .= html_writer::empty_tag('input', $attributes);

                if (!$rating->settings->scale->isnumeric) {
                    $ratinghtml .= $this->help_icon_scale($rating->settings->scale->courseid, $rating->settings->scale);
                }
                $ratinghtml .= html_writer::end_tag('span');
                $ratinghtml .= html_writer::end_tag('div');
                $ratinghtml .= html_writer::end_tag('form');
            }
        }

        return $ratinghtml;
    }


    /**
     * Array of patterns this field supports 
     */
    protected function patterns() {
        $fieldinternalname = $this->_field->get('internalname');
        $cat = get_string('ratings', 'dataform');

        $patterns = array();
        switch($fieldinternalname) {
            case 'ratings':
                $patterns['##ratings:rate##'] = array(true, $cat);
                $patterns['##ratings:view##'] = array(true, $cat);
                $patterns['##ratings:viewurl##'] = array(false);
                $patterns['##ratings:viewinline##'] = array(true, $cat);
                break;
            case 'avgratings':
                $patterns['##ratings:avg##'] = array(true, $cat);
                $patterns['##ratings:avg:bar##'] = array(false);
                $patterns['##ratings:avg:star##'] = array(false);
                break;
            case 'countratings':
                $patterns['##ratings:count##'] = array(true, $cat);
                break;
            case 'maxratings':
                $patterns['##ratings:max##'] = array(true, $cat);
                break;
            case 'minratings':
                $patterns['##ratings:min##'] = array(true, $cat);
                break;
            case 'sumratings':
                $patterns['##ratings:sum##'] = array(true, $cat);
                break;
        }

        return $patterns; 
    }
}
