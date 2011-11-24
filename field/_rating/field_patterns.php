<?php

/**
 * This file is part of the Dataform module for Moodle - http://moodle.org/.
 *
 * @package mod-dataform
 * @subpackage field-_rating
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

require_once("$CFG->dirroot/mod/dataform/field/field_patterns.php");

/**
 *
 */
class mod_dataform_field__rating_patterns extends mod_dataform_field_patterns {

    /**
     * 
     */
    public function get_replacements($tags = null, $entry = null, $edit = false, $editable = false) {
        $field = $this->_field;

        // no edit mode
        $replacements = array();

        $ratingenabled = $this->_field->df()->data->rating;
        // no edit mode for this field so just return html
        foreach ($tags as $tag) {
            if ($entry->id > 0 and $ratingenabled) {            
                switch($tag) {
                    case '##ratings:count##': $str = $entry->rating->count; break;                            
                    case '##ratings:avg##':  $str = $entry->rating->aggregate[dataform_field__rating::AGGREGATE_AVG]; break;                            
                    case '##ratings:max##':  $str = $entry->rating->aggregate[dataform_field__rating::AGGREGATE_MAX]; break;                           
                    case '##ratings:min##':  $str = $entry->rating->aggregate[dataform_field__rating::AGGREGATE_MIN]; break;                            
                    case '##ratings:sum##':  $str = $entry->rating->aggregate[dataform_field__rating::AGGREGATE_SUM]; break;                            
                    case '##ratings:view##':
                    case '##ratings:viewurl##': $str = $this->display_view($entry); break;
                    case '##ratings:rate##': $str = $this->render_rating($entry); break;
                    default: $str = '';
                }
                $replacements[$tag] = array('html', $str);

            } else {
                $replacements[$tag] = '';                    
            }            
        }                    

        return $replacements;
    }

    /**
     * 
     */
    public function get_aggregations($patterns) {
        if ($aggregations = array_intersect($patterns, array(
                        dataform_field__rating::AGGREGATE_AVG => '##ratings:avg##',
                        dataform_field__rating::AGGREGATE_MAX => '##ratings:max##',
                        dataform_field__rating::AGGREGATE_MIN => '##ratings:min##',
                        dataform_field__rating::AGGREGATE_SUM => '##ratings:sum##'))) {
            return array_keys($aggregations);
        } else {
            return null;
        }
    }

    /**
     * 
     */
    protected function display_view($entry) {
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
            } else {
                return '';
            }
        } else {
            return '';
        }
    }
    
    /**
     * 
     */
    protected function render_rating($entry) {
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
        $cat = get_string('ratings', 'dataform');

        $patterns = array();
        $patterns['##ratings:count##'] = array(true, $cat);
        $patterns['##ratings:avg##'] = array(true, $cat);
        $patterns['##ratings:max##'] = array(true, $cat);
        $patterns['##ratings:min##'] = array(true, $cat);
        $patterns['##ratings:sum##'] = array(true, $cat);
        $patterns['##ratings:rate##'] = array(true, $cat);
        $patterns['##ratings:view##'] = array(true, $cat);
        $patterns['##ratings:viewurl##'] = array(true, $cat);

        return $patterns; 
    }
}
