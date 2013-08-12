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
 * @subpackage datadformview
 * @copyright 2013 Itamar Tzadok
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') or die();

require_once("$CFG->dirroot/mod/dataform/filter/filter_class.php");
require_once("$CFG->dirroot/mod/dataform/field/renderer.php");
require_once("$CFG->dirroot/mod/dataform/field/_user/field_class.php");
require_once("$CFG->dirroot/mod/dataform/field/_group/field_class.php");

/**
 *
 */
class dataformfield_dataformview_renderer extends dataformfield_renderer {

    /**
     * 
     */
    protected function replacements(array $tags = null, $entry = null, array $options = null) {
        $field = $this->_field;
        $fieldname = $field->name();

        $replacements = array();

        foreach ($tags as $tag) {
            // No edit mode
            $parts = explode(':', trim($tag, '[]'));
            if (!empty($parts[1])) {
                $type = $parts[1];
            } else {
                $type = '';
            }
            $replacements[$tag] = array('html', $this->display_browse($entry, $type));
        }
        return $replacements;
    }

    /**
     *
     */
    protected function display_browse($entry, $type = null) {
        global $PAGE;
        
        $field = $this->_field;

        if (empty($field->refdataform) or empty($field->refview)) {
            return '';
        }

        // Inline
        if (empty($type)) {
            // TODO Including controls seems to mess up the hosting view controls
            $voptions = array('controls' => false);
            return $this->get_view_display_content($entry, $voptions);
        }

        // Overlay
        if ($type == 'overlay') {
            $this->add_overlay_support();
            
            $voptions = array('controls' => false);
            $widgetbody = html_writer::tag('div', $this->get_view_display_content($entry, $voptions), array('class' => "yui3-widget-bd"));
            $panel = html_writer::tag('div', $widgetbody, array('class' => 'panelContent hide'));
            $button = html_writer::tag('button', get_string('viewbutton', 'dataformfield_dataformview')); 
            $wrapper = html_writer::tag('div', $button. $panel, array('class' => 'dataformfield-dataformview overlay'));
            return $wrapper;
        }
        
        // Embedded
        if ($type == 'embedded') {
            return $this->get_view_display_embedded($entry);
        }

        // Embedded Overlay
        if ($type == 'embeddedoverlay') {
            $this->add_overlay_support();

            $widgetbody = html_writer::tag('div', $this->get_view_display_embedded($entry), array('class' => "yui3-widget-bd"));
            $panel = html_writer::tag('div', $widgetbody, array('class' => 'panelContent hide'));
            $button = html_writer::tag('button', get_string('viewbutton', 'dataformfield_dataformview')); 
            $wrapper = html_writer::tag('div', $button. $panel, array('class' => 'dataformfield-dataformview embedded overlay'));
            return $wrapper;
        }
        
        return '';
    }

    /**
     *
     */
    protected function get_view_display_content($entry, array $options = array()) {
        $field = $this->_field;

        $refdataform = $field->refdataform;
        $refview = $field->refview;
        $localview = $field->localview;

        // Options for setting the filter
        $foptions = array();
        // Filter id
        if ($field->reffilterid) {
            $foptions['filterid'] = $field->field->param3;
        }
        
        // Search filter by entry author or group
        $foptions = $this->get_filter_by_options($foptions, $entry);
        
        // Custom sort
        if ($soptions = $this->get_sort_options()) {
            $foptions['csort'] = $soptions;
        }
        // Custom search
        if ($soptions = $this->get_search_options($entry)) {
            $foptions['csearch'] = $soptions;
        }

        $refview->set_filter($foptions);

        // Set the ref dataform
        $params = array(
                'js' => true,
                'css' => true,
                'modjs' => true,
                'completion' => true,
                'comments' => true,
                'nologin' => true,
        );
        
        // Ref dataform page type defaults to external
        $refpagetype = !empty($options['pagetype']) ? $options['pagetype'] : 'external';        
        $pageoutput = $refdataform->set_page('external', $params);
        
        $refview->set_content();
        // Set to return html
        $options['tohtml'] = true;
        return $refview->display($options);
    }
    
    /**
     *
     */
    protected function get_view_display_embedded($entry) {
        $field = $this->_field;
        $fieldname = str_replace(' ', '_', $field->name());
        
        // Construct the src url
        $params = array(
            'd' => $field->refdataform->id(),
            'view' => $field->refview->id()
        );
        if ($field->reffilterid) {
            $params['filter'] = $field->reffilterid;
        }
        // Search filter by entry author or group
        $params = $this->get_filter_by_options($params, $entry, true);
    
        // Custom sort
        if ($soptions = $this->get_sort_options()) {
            $fm = $this->_df->get_filter_manager();
            $usort = $fm::get_sort_url_query($soptions);
            $params['usort'] = $usort;
        }
        // Custom search
        if ($soptions = $this->get_search_options($entry)) {
            $fm = $this->_df->get_filter_manager();
            $usearch = $fm::get_search_url_query($soptions);
            $params['usearch'] = $usearch;
        }

        $srcurl = new moodle_url('/mod/dataform/embed.php', $params);
        
        // Frame
        $froptions = array(
            'src' => $srcurl,
            'width' => '100%',
            'height' => '100%',
            'style' => 'border:0;',
        );
        $iframe = html_writer::tag('iframe', null, $froptions);
        return html_writer::tag('div', $iframe, array('class' => "dataformfield-dataformview-$fieldname embedded"));
    }

    /**
     *
     */
    protected function add_overlay_support() {
        global $PAGE;
        
        static $added = false;
        
        if (!$added) {
            $module = array(
                'name' => 'M.dataformfield_dataformview_overlay',
                'fullpath' => '/mod/dataform/field/dataformview/dataformview.js',
                'requires' => array('base','node')
            );
            
            $PAGE->requires->js_init_call('M.dataformfield_dataformview_overlay.init', null, false, $module);
        }
    }

    /**
     *
     */
    protected function get_filter_by_options(array $options, $entry, $urlquery = false) {
        $field = $this->_field;

        if (!empty($field->field->param6)) {
            list($filterauthor, $filtergroup) = explode(',', $field->field->param6);
            // Entry author
            if ($filterauthor) {
                $users = $urlquery ? $entry->userid : array($entry->userid);
                $options['users'] = $users;
            }
            // Entry group
            if ($filtergroup) {
                $groups = $urlquery ? $entry->groupid : array($entry->groupid);
                $options['groups'] = $groups;            
            }
        }
        return $options;
    }
        
    /**
     *
     */
    protected function get_sort_options() {
        $field = $this->_field;

        $refdataform = $field->refdataform;
        $refview = $field->refview;

        $soptions = array();
        // Custom sort (ref-field-patten,ASC/DESC)
        if (!empty($field->field->param4)) {
            foreach (explode("\n", $field->field->param4) as $key => $sorty) {
                list($pattern, $dir) = explode(',', $sorty);
                // Get the field id from pattern
                if (!$rfieldid = $refview->get_pattern_fieldid($pattern)) {
                    continue;
                }
                // Convert direction to 0/1
                $dir = $dir == 'DESC' ? 1 : 0;
                $soptions[$rfieldid] = $dir;
            }
        }
        return $soptions;
    }

    /**
     *
     */
    protected function get_search_options($entry) {
        $field = $this->_field;
        $soptions = array();

        // Custom search (AND/OR,ref-field-patten,[NOT],OPT,local-field-pattern/value
        if (empty($field->field->param5)) {
            return $soptions;
        }

        if (!$refdataform = $field->refdataform or !$refview = $field->refview or !$localview = $field->localview) {
            return $soptions;
        }

        foreach (explode("\n", $field->field->param5) as $key => $searchy) {
            list($andor, $refpattern, $not, $operator, $localpattern) = explode(',', $searchy);
            // And/or
            if (empty($andor) or !in_array($andor, array('AND', 'OR'))) {
                continue;
            }
            // Get the ref field id from pattern
            if (!$rfieldid = $refview->get_pattern_fieldid($refpattern)) {
                continue;
            }
            // Get value for local pattern or use as value
            $value = '';
            if (!$localfieldid = $localview->get_pattern_fieldid($localpattern)) {
                $value = $localpattern;
            } else if ($localfield = $field->df->get_field_from_id($localfieldid)) {
                // Get the array of values for the patterns
                if ($replacements = $localfield->renderer()->get_replacements(array($localpattern), $entry)) {
                    // Take the first: array('html', value)
                    $first = reset($replacements);
                    // extract the value part
                    $value = $first[1];
                    // Make sure this is the search value
                    // (select fields search by key)
                    $value = $localfield->get_search_value($value);
                }
            }
            
            // Add to the search options
            if (empty($soptions[$rfieldid])) {
                $soptions[$rfieldid] = array('AND' => array(), 'OR' => array());
            }
            $soptions[$rfieldid][$andor][] = array($not, $operator, $value);
        }

        return $soptions;
    }
    
    /**
     * Array of patterns this field supports 
     */
    protected function patterns() {
        $fieldname = $this->_field->name();

        $patterns = parent::patterns();
        $patterns["[[$fieldname]]"] = array(true);
        $patterns["[[$fieldname:overlay]]"] = array(true);
        $patterns["[[$fieldname:embedded]]"] = array(false);
        $patterns["[[$fieldname:embeddedoverlay]]"] = array(false);

        return $patterns; 
    }
}
