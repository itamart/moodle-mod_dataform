<?php
/**
 * @package mod_dataform
 * @category admin
 * @copyright  2013 Itamar Tzadok
 */

require_once('../../../config.php');
require_once("$CFG->libdir/adminlib.php");

$tool = required_param('tool', PARAM_ALPHA);

admin_externalpage_setup("moddataform_$tool");

$toolclass = "mod_dataform\admin\\$tool";

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('pluginname', 'dataform'));
echo html_writer::tag('h3', $toolclass::get_visible_name());
    
echo $toolclass::run();
    
echo $OUTPUT->footer();