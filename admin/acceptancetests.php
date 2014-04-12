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
 * @package mod_dataform
 * @category admin
 * @copyright 2013 Itamar Tzadok
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../config.php');
require_once("$CFG->libdir/adminlib.php");

admin_externalpage_setup('moddataform_acceptancetests');

// Acceptance tests form
class dataform_acceptance_tests_form extends moodleform {
    function definition() {
        $mform = &$this->_form;

        // Features
        $options = array('' => get_string('choosedots')) + $this->get_features_list();
        $select = $mform->addElement('select', 'features', 'Feature', $options);

        // Tags
        $options = $this->get_tags_list();
        $select = $mform->addElement('select', 'tags', 'Tags', $options);
        $select->setMultiple(true);

        // buttons
        //-------------------------------------------------------------------------------
        $this->add_action_buttons(false, 'Run');
    }

    /**
     *
     */
    public function add_action_buttons($cancel = true, $submit = null){
        $mform = &$this->_form;

        $buttonarray=array();
        // Run
        $buttonarray[] = &$mform->createElement('submit', 'submitbutton_run', 'Run tests');
        // Enable test environment
        $buttonarray[] = &$mform->createElement('submit', 'submitbutton_enable', 'Enable test environment');
        // Disable test enviroment
        $buttonarray[] = &$mform->createElement('submit', 'submitbutton_disable', 'Disable test environment');
        $mform->addGroup($buttonarray, 'buttonar', '', array('<br />', ' '), false);
        $mform->closeHeaderBefore('buttonar');
    }
    
    /**
     *
     */
    public function enable_test_environment() {
        global $CFG;
            
        exec("php $CFG->dirroot/admin/tool/behat/cli/util.php --enable", $result);
        return $result;        
    }
    
    /**
     *
     */
    public function disable_test_environment() {
        global $CFG;
            
        exec("php $CFG->dirroot/admin/tool/behat/cli/util.php --disable", $result);
        return $result;        
    }
    
    /**
     *
     */
    public function start_selenium() {
        global $CFG;
        
        $result = array();

        // Selenium server check
        $seleniumserverport = !empty($CFG->behat_seleniumserverport) ? $CFG->behat_seleniumserverport : 4444;
        $fp = @fsockopen('localhost', $seleniumserverport);
        
        // Run in the background
        if ($fp === false) {
            // Not running. Try once to start
            //$this->exec_async('java', "-jar C:\selenium-server-standalone-2.39.0.jar");


            //$cmd = 'java -jar C:\selenium-server-standalone-2.39.0.jar';

            //if (substr(php_uname(), 0, 7) == "Windows"){ 
            //    pclose(popen("start /B " . $cmd, "r"));  
            //} 
            //else { 
            //    exec($cmd . " > /dev/null &");   
            //} 
            
            // Try the socket again
            //$fp = @fsockopen('localhost', $seleniumserverport);
        }
             
        if ($fp === false) {
            $result['running'] = false;
            $result['message'] = "
            Selenium server is not running on port $seleniumserverport
            <br />You can start your selenium server by running a shell command such as: java -jar C:\selenium-server-standalone-2.39.0.jar";            
        } else {
            fclose($fp);
            $result['running'] = true;
            $shutdownurl = 'http://localhost:4444/selenium-server/driver/?cmd=shutDownSeleniumServer';
            $shutdownlink = html_writer::link($shutdownurl, 'Shut down', array('target' => '_blank'));
            $result['message'] = "Selenium server is running on port $seleniumserverport ($shutdownlink)<br />";
        }

        return $result;
    }
    
    /**
     *
     */
    function run_tests($data) {
        global $CFG;
        
        $result = array();

        if (empty($data->features) and empty($data->tags)) {
            return array('Please select features or tags.');
        }
        
        // Features
        $features = '';
        if (!empty($data->features)) {
            $features = "$CFG->dirroot/mod/dataform/$data->features.feature";
            $result[] = "Testing feature: $data->features";
        }
            
        // Tags
        $tags = '';
        if (!empty($data->tags)) {
            $tags = '--tags '. implode(',', array_map(function($item) {return "@$item";}, $data->tags));
            $taglist = implode(', ', $data->tags);
            $result[] = "Testing features with tags: $taglist";            
        }
            
        $result[] = '<hr />';            

        if (substr(php_uname(), 0, 7) == "Windows"){
            exec("$CFG->dirroot/vendor/bin/behat $features $tags --config $CFG->behat_dataroot/behat/behat.yml", $result);
        }
        else {
            //exec($data->selenium . " > /dev/null &");  
            //exec($data->behatrun . " > /dev/null &");  
        }
        return $result;
    }

    /**
     *
     */
    public function get_cli_info() {
        global $CFG;

        $shutdownurl = 'http://localhost:4444/selenium-server/driver/?cmd=shutDownSeleniumServer';
        
        $dirroot = str_replace('\\', '/', $CFG->dirroot);  
        
        $info = array();
        $info['Init Behat'] = "php $CFG->dirroot/admin/tool/behat/cli/init.php";
        $info['Run Selenium'] = 'java -jar C:\selenium-server-standalone-2.39.0.jar';
        $info['Run tests'] = "$dirroot/vendor/bin/behat [$dirroot/mod/dataform/tests/behat/featurename.feature] --tags @mod_dataform --config $CFG->behat_dataroot/behat/behat.yml";
        $info['Shutdown Selenium'] = html_writer::link($shutdownurl, $shutdownurl, array('target' => '_blank'));
        
        $content = '';
        foreach ($info as $label => $inf) {
            
            $content .= html_writer::tag('div', "<b>$label:</b> $inf");
        }
        $content .= html_writer::empty_tag('hr');
        
        return $content;
    }

    /**
     *
     */
    public function get_tests_info() {
        $featurescount = count($this->get_features_list());
        $tagscount = count($this->get_tags_list());
        
        $featurescountstr = html_writer::tag('div', "<b>Features:</b> $featurescount");
        $tagscountstr = html_writer::tag('div', "<b>Tags:</b> $tagscount");
        $hr = html_writer::empty_tag('hr');
        
        return html_writer::tag('div', $featurescountstr. $tagscountstr. $hr);
    }

    /**
     *
     */
    protected function get_features_list() {
        global $CFG;
        
        $options = array();
        
        // Dataform
        foreach (get_directory_list("$CFG->dirroot/mod/dataform/tests/behat") as $filename) {
            if (strpos($filename, '.feature') !== false) {
                $featurename = basename($filename, '.feature');
                $options["tests/behat/$featurename"] = $featurename;
            }
        }
        
        // Fields
        
        
        // Views
        

        return $options;
    }
    
    /**
     *
     */
    protected function get_tags_list() {
        $tags = array(
            'mod_dataform',
            'dataformfield',
            'dataformview',
            'dataformaccess',
            'dataformnotification'
        );
        $options = array_combine($tags, $tags);
        return $options;
    }

    /**
     *
     */
    protected function exec_async($path, $arguments) {
        $WshShell = new COM("WScript.Shell");
        $oShellLink = $WshShell->CreateShortcut("temp.lnk");
        $oShellLink->TargetPath = $path;
        $oShellLink->Arguments = $arguments;
        $oShellLink->WorkingDirectory = dirname($path);
        $oShellLink->WindowStyle = 1;
        $oShellLink->Save();
        $oExec = $WshShell->Run("temp.lnk", 7, false);
        unset($WshShell,$oShellLink,$oExec);
        unlink("temp.lnk");
    }   
}

$mform = new dataform_acceptance_tests_form();

echo $OUTPUT->header();

echo $OUTPUT->heading(get_string('pluginname', 'dataform'). ' - '. get_string('pluginname', 'tool_behat'));

// Print info
echo $mform->get_cli_info();

// Print info
echo $mform->get_tests_info();

// Print the preset form
$mform->display();

ob_flush();
flush();

// Run tests   
if ($data = $mform->get_data()) {
    // Enable test environment
    if (!empty($data->submitbutton_enable)) {
        echo html_writer::tag('p', 'Enabling test environment ...');        
        ob_flush();
        flush();
    
        $result = $mform->enable_test_environment();
        echo implode('<br />', $result);
    }    
        
    // Disable test environment
    if (!empty($data->submitbutton_disable)) {
        echo html_writer::tag('p', 'Disabling test environment ...');        
        ob_flush();
        flush();

        $result = $mform->disable_test_environment();
        echo implode('<br />', $result);
    }    

    // Run tests
    if (!empty($data->submitbutton_run)) {
        echo 'Starting selenium ... <br />';
        $seleniumcheck = $mform->start_selenium();
        echo $seleniumcheck['message'];
        echo '<hr />';
        
        ob_flush();
        flush();

        if ($seleniumcheck['running']) {
            $testresult = $mform->run_tests($data);
            echo implode('<br />', $testresult);
        }
    }
}

echo $OUTPUT->footer();
ob_end_flush();




