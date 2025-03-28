<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Prints an instance of mod_mokitul.
 *
 * @package     mod_mokitul
 * @copyright   2024 Your Name <you@example.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// reprensents the view of the module

// usually happens when the link is clicked

require __DIR__ . "/../../config.php";
require_once __DIR__ . "/lib.php";

require_once __DIR__ . "/event/course_module_viewed.php";

// Course module id.
$id = optional_param("id", 0, PARAM_INT);

// Activity instance id.
$m = optional_param("m", 0, PARAM_INT);

if ($id) {
    $cm = get_coursemodule_from_id("mokitul", $id, 0, false, MUST_EXIST);
    $course = $DB->get_record("course", ["id" => $cm->course], "*", MUST_EXIST);
    $moduleinstance = $DB->get_record(
        "mokitul",
        ["id" => $cm->instance],
        "*",
        MUST_EXIST
    );
} else {
    $moduleinstance = $DB->get_record("mokitul", ["id" => $m], "*", MUST_EXIST);
    $course = $DB->get_record(
        "course",
        ["id" => $moduleinstance->course],
        "*",
        MUST_EXIST
    );
    $cm = get_coursemodule_from_instance(
        "mokitul",
        $moduleinstance->id,
        $course->id,
        false,
        MUST_EXIST
    );
}

// This probably needs to be false, if we want to use our api to index crawled files
require_login($course, false, $cm);

$modulecontext = context_module::instance($cm->id);

$event = \mod_mokitul\event\course_module_viewed::create([
    "objectid" => $moduleinstance->id,
    "context" => $modulecontext,
]);

//$fs = get_file_storage();

//$file = $fs->get_file($modulecontext->id, 'mod_mokitul', 'attachments', 0, '/', 'llama2.pdf');

//$forcedownload = true;
//$options = array();

//send_stored_file($file, 0, 0, $forcedownload, $options);

$event->add_record_snapshot("course", $course);
$event->add_record_snapshot("mokitul", $moduleinstance);
$event->trigger();

$PAGE->set_url("/mod/mokitul/view.php", ["id" => $cm->id]);
$PAGE->set_title(format_string($moduleinstance->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($modulecontext);
$PAGE->requires->css(new moodle_url("/local/mokitul/styles.css"));

echo $OUTPUT->header();

$fs = get_file_storage();

$files = $fs->get_area_files($modulecontext->id, "mod_mokitul", "attachments");

$single_file = null;
foreach ($files as $file) {
    if (empty($file->get_filesize())) {
        continue;
    }

    $single_file = $file;
    break;
}

$chat_identifier = "mokitul_chat_" . \core\uuid::generate();

// represents our main container
$html = '<div id="' . $chat_identifier . '" ></div>';

$PAGE->requires->js_call_amd("local_mokitul/lib", "renderApp", [
    "element" => $chat_identifier,
    "courseId" => $course->id,
    "fileId" => $single_file->get_id(),
    "preferredLayout" => "standalone",
]);

echo $html;

echo $OUTPUT->footer();
