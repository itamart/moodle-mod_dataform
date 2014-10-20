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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

defined('MOODLE_INTERNAL') || die();

/**
 * Events testcase
 *
 * @package    mod_dataform
 * @category   phpunit
 * @group      mod_dataform
 * @copyright  2014 Itamar Tzadok {@link http://substantialmethods.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_dataform_events_testcase extends advanced_testcase {
    /**
     * Test set up.
     *
     * This is executed before running any test in this file.
     */
    public function setUp() {
        $this->resetAfterTest();
    }

    /**
     * Test view events for standard types.
     */
    public function test_view_events() {
        $this->setAdminUser();
        $df = $this->get_a_dataform();

        $viewtypes = array_keys(core_component::get_plugin_list('dataformview'));
        foreach ($viewtypes as $type) {
            $this->try_crud_view($type, $df);
        }
    }

    /**
     * Test field events for standard types.
     */
    public function test_field_events() {
        $this->setAdminUser();
        $df = $this->get_a_dataform();

        $fieldtypes = array_keys(core_component::get_plugin_list('dataformfield'));
        foreach ($fieldtypes as $type) {
            $this->try_crud_field($type, $df);
        }
    }

    /**
     * Test filter events.
     */
    public function test_filter_events() {
        global $DB;

        $this->setAdminUser();
        $df = $this->get_a_dataform();
        $filter = $df->filter_manager->get_filter_blank();

        // CREATE
        // Trigger and capture the event.
        $sink = $this->redirectEvents();
        $filter->update();
        $events = $sink->get_events();
        $event = reset($events);

        // Check that the event data is valid.
        $this->assertInstanceOf('\mod_dataform\event\filter_created', $event);
        $this->assertEquals(context_module::instance($df->cm->id), $event->get_context());
        $expected = array($df->course->id, 'dataform', 'filters add', "filter/index.php?d=$df->id&amp;fid=$filter->id", $filter->id, $df->cm->id);
        $this->assertEventLegacyLogData($expected, $event);

        // UPDATE
        // Trigger and capture the event.
        $sink = $this->redirectEvents();
        $filter->update();
        $events = $sink->get_events();
        $event = reset($events);

        // Check that the event data is valid.
        $this->assertInstanceOf('\mod_dataform\event\filter_updated', $event);
        $this->assertEquals(context_module::instance($df->cm->id), $event->get_context());
        $expected = array($df->course->id, 'dataform', 'filters update', "filter/index.php?d=$df->id&amp;fid=$filter->id", $filter->id, $df->cm->id);
        $this->assertEventLegacyLogData($expected, $event);

        // DELETE
        // Trigger and capture the event.
        $sink = $this->redirectEvents();
        $filter->delete();
        $events = $sink->get_events();
        $event = reset($events);

        // Check that the event data is valid.
        $this->assertInstanceOf('\mod_dataform\event\filter_deleted', $event);
        $this->assertEquals(context_module::instance($df->cm->id), $event->get_context());
        $expected = array($df->course->id, 'dataform', 'filters delete', "filter/index.php?d=$df->id&amp;delete=$filter->id", $filter->id, $df->cm->id);
        $this->assertEventLegacyLogData($expected, $event);
    }

    /**
     * Test entry events.
     */
    public function test_entry_events() {
        global $DB;

        $this->setAdminUser();
        $df = $this->get_a_dataform();

        // SETUP
        // Add a field
        $field = $df->field_manager->add_field('text');
        // Add a view
        $view = $df->view_manager->add_view('aligned');

        $entryman = $view->entry_manager;

        $data = (object) array('submitbutton_save' => 'Save');

        // CREATE
        // Trigger and capture the event.
        $sink = $this->redirectEvents();
        list(, $eids) = $entryman->process_entries('update', array(-1), $data, true);
        $events = $sink->get_events();
        $event = reset($events);

        $entryid = reset($eids);
        $filter = $view->filter;
        $filter->eids = $entryid;
        $entryman->set_content(array('filter' => $filter));

        // Check that the event data is valid.
        $this->assertInstanceOf('\mod_dataform\event\entry_created', $event);
        $this->assertEquals(context_module::instance($df->cm->id), $event->get_context());
        $expected = array($df->course->id, 'dataform', 'entries add', "view.php?d=$df->id&amp;view=$view->id&amp;eids=$entryid", $entryid, $df->cm->id);
        $this->assertEventLegacyLogData($expected, $event);

        // UPDATE
        // Trigger and capture the event.
        $sink = $this->redirectEvents();
        $entryman->process_entries('update', array($entryid), $data, true);
        $events = $sink->get_events();
        $event = reset($events);

        // Check that the event data is valid.
        $this->assertInstanceOf('\mod_dataform\event\entry_updated', $event);
        $this->assertEquals(context_module::instance($df->cm->id), $event->get_context());
        $expected = array($df->course->id, 'dataform', 'entries update', "view.php?d=$df->id&amp;view=$view->id&amp;eids=$entryid", $entryid, $df->cm->id);
        $this->assertEventLegacyLogData($expected, $event);

        // UPDATE FIELD CONTENT
        $entry = (object) array('id' => $entryid);
        // Trigger and capture the event.
        $sink = $this->redirectEvents();
        $field->update_content($entry, array('Hello world'));
        $events = $sink->get_events();
        $event = reset($events);

        // Check that the event data is valid.
        $this->assertInstanceOf('\mod_dataform\event\field_content_updated', $event);
        $this->assertEquals(context_module::instance($df->cm->id), $event->get_context());

        // DELETE
        // Trigger and capture the event.
        $sink = $this->redirectEvents();
        $entryman->process_entries('delete', array($entryid), null, true);
        $events = $sink->get_events();
        $event = reset($events);

        // Check that the event data is valid.
        $this->assertInstanceOf('\mod_dataform\event\entry_deleted', $event);
        $this->assertEquals(context_module::instance($df->cm->id), $event->get_context());
        $expected = array($df->course->id, 'dataform', 'entries delete', "view.php?d=$df->id&amp;view=$view->id", $entryid, $df->cm->id);
        $this->assertEventLegacyLogData($expected, $event);
    }

    /**
     * Sets up a dataform activity in a course.
     *
     * @return mod_dataform_dataform
     */
    protected function get_a_dataform($course = null) {
        $course = !$course ? $this->getDataGenerator()->create_course() : $course;

        // Create a dataform instance.
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_dataform');
        $dataform = $generator->create_instance(array('course' => $course));
        $dataformid = $dataform->id;

        return \mod_dataform_dataform::instance($dataformid);
    }

    /**
     * View events.
     */
    protected function try_crud_view($type, $df) {
        $view = $df->view_manager->get_view($type);

        // CREATE
        // Trigger and capture the event.
        $sink = $this->redirectEvents();
        $view->add($view->data);
        $events = $sink->get_events();
        $event = reset($events);

        // Check that the event data is valid.
        $this->assertInstanceOf('\mod_dataform\event\view_created', $event);
        $this->assertEquals(context_module::instance($df->cm->id), $event->get_context());
        $expected = array($df->course->id, 'dataform', 'views add', "view/index.php?d=$df->id&amp;vedit=$view->id", $view->id, $df->cm->id);
        $this->assertEventLegacyLogData($expected, $event);

        // READ (view)
        // Trigger and capture the event.
        $sink = $this->redirectEvents();
        $view->display();
        $events = $sink->get_events();
        $event = reset($events);

        // Check that the event data is valid.
        $this->assertInstanceOf('\mod_dataform\event\view_viewed', $event);
        $this->assertEquals(context_module::instance($df->cm->id), $event->get_context());
        $expected = array($df->course->id, 'dataform', 'views view', "view.php?d=$df->id&amp;view=$view->id", $view->id, $df->cm->id);
        $this->assertEventLegacyLogData($expected, $event);

        // UPDATE
        // Trigger and capture the event.
        $sink = $this->redirectEvents();
        $view->update($view->data);
        $events = $sink->get_events();
        $event = reset($events);

        // Check that the event data is valid.
        $this->assertInstanceOf('\mod_dataform\event\view_updated', $event);
        $this->assertEquals(context_module::instance($df->cm->id), $event->get_context());
        $expected = array($df->course->id, 'dataform', 'views update', "view/index.php?d=$df->id&amp;vedit=$view->id", $view->id, $df->cm->id);
        $this->assertEventLegacyLogData($expected, $event);

        // DELETE
        // Trigger and capture the event.
        $sink = $this->redirectEvents();
        $view->delete();
        $events = $sink->get_events();
        $event = reset($events);

        // Check that the event data is valid.
        $this->assertInstanceOf('\mod_dataform\event\view_deleted', $event);
        $this->assertEquals(context_module::instance($df->cm->id), $event->get_context());
        $expected = array($df->course->id, 'dataform', 'views delete', "view/index.php?d=$df->id&amp;delete=$view->id", $view->id, $df->cm->id);
        $this->assertEventLegacyLogData($expected, $event);
    }

    /**
     * Field events.
     */
    protected function try_crud_field($type, $df) {
        $field = $df->field_manager->get_field($type);

        // CREATE
        // Trigger and capture the event.
        $sink = $this->redirectEvents();
        $field->create($field->data);
        $events = $sink->get_events();
        $event = reset($events);

        // Check that the event data is valid.
        $this->assertInstanceOf('\mod_dataform\event\field_created', $event);
        $this->assertEquals(context_module::instance($df->cm->id), $event->get_context());
        $expected = array($df->course->id, 'dataform', 'fields add', "field/index.php?d=$df->id&amp;fid=$field->id", $field->id, $df->cm->id);
        $this->assertEventLegacyLogData($expected, $event);

        // UPDATE
        // Trigger and capture the event for creating a field.
        $sink = $this->redirectEvents();
        $field->update($field->data);
        $events = $sink->get_events();
        $event = reset($events);

        // Check that the event data is valid.
        $this->assertInstanceOf('\mod_dataform\event\field_updated', $event);
        $this->assertEquals(context_module::instance($df->cm->id), $event->get_context());
        $expected = array($df->course->id, 'dataform', 'fields update', "field/index.php?d=$df->id&amp;fid=$field->id", $field->id, $df->cm->id);
        $this->assertEventLegacyLogData($expected, $event);

        // DELETE
        // Trigger and capture the event for creating a field.
        $sink = $this->redirectEvents();
        $field->delete();
        $events = $sink->get_events();
        $event = reset($events);

        // Check that the event data is valid.
        $this->assertInstanceOf('\mod_dataform\event\field_deleted', $event);
        $this->assertEquals(context_module::instance($df->cm->id), $event->get_context());
        $expected = array($df->course->id, 'dataform', 'fields delete', "field/index.php?d=$df->id&amp;delete=$field->id", $field->id, $df->cm->id);
        $this->assertEventLegacyLogData($expected, $event);
    }

}
