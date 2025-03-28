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
 * Library of interface functions and constants.
 *
 * @package     mod_mokitul
 * @copyright   2024 Your Name <you@example.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('/bitnami/moodle/config.php');
require_once($CFG->libdir . '/filelib.php');

/**
 * Return if the plugin supports $feature.
 *
 * @param string $feature Constant representing the feature.
 * @return true | null True if the feature is supported, null otherwise.
 */
function mokitul_supports($feature) {
    switch ($feature) {
        case FEATURE_MOD_INTRO:
            return true;
        default:
            return null;
    }
}

/**
 * Saves a new instance of the mod_mokitul into the database.
 *
 * Given an object containing all the necessary data, (defined by the form
 * in mod_form.php) this function will create a new instance and return the id
 * number of the instance.
 *
 * @param object $moduleinstance An object from the form.
 * @param mod_mokitul_mod_form $mform The form.
 * @return int The id of the newly inserted record.
 */
function mokitul_add_instance($moduleinstance, $mform = null) {
    global $DB;

    $moduleinstance->timecreated = time();

    $context = context_module::instance($moduleinstance->coursemodule);

    if ($draftitemid = file_get_submitted_draft_itemid('attachments')) {
        file_save_draft_area_files($draftitemid, $context->id, 'mod_mokitul', 'attachments', 0);
    }

    $id = $DB->insert_record('mokitul', $moduleinstance);

    /*
    $curl = new \curl();

    $curl->setopt(array(
        'CURLOPT_HTTPHEADER' => array(
            'Content-Type: application/json',
        ),
    ));

    $fileId = $moduleinstance->coursemodule;

    $body = [
        "url" => "http://localhost/local/mokitul/download.php?id=$fileId"
    ];

    $body = json_encode($body);

    $serviceUrl = 'http://host.docker.internal:8000';

    $courseId = $moduleinstance->course;

    $url = "$serviceUrl/chat_with_files/file/$courseId/$fileId";
    $response = $curl->put($url, $body);
    */

    return $id;
}

/**
 * Updates an instance of the mod_mokitul in the database.
 *
 * Given an object containing all the necessary data (defined in mod_form.php),
 * this function will update an existing instance with new data.
 *
 * @param object $moduleinstance An object from the form in mod_form.php.
 * @param mod_mokitul_mod_form $mform The form.
 * @return bool True if successful, false otherwise.
 */
function mokitul_update_instance($moduleinstance, $mform = null) {
    global $DB;

    $moduleinstance->timemodified = time();
    $moduleinstance->id = $moduleinstance->instance;

    return $DB->update_record('mokitul', $moduleinstance);
}

/**
 * Removes an instance of the mod_mokitul from the database.
 *
 * @param int $id Id of the module instance.
 * @return bool True if successful, false on failure.
 */
function mokitul_delete_instance($id) {
    global $DB;

    $exists = $DB->get_record('mokitul', array('id' => $id));
    if (!$exists) {
        return false;
    }

    $DB->delete_records('mokitul', array('id' => $id));

    return true;
}

/**
 * Returns the lists of all browsable file areas within the given module context.
 *
 * The file area 'intro' for the activity introduction field is added automatically
 * by {@see file_browser::get_file_info_context_module()}.
 *
 * @package     mod_mokitul
 * @category    files
 *
 * @param stdClass $course
 * @param stdClass $cm
 * @param stdClass $context
 * @return string[].
 */
function mokitul_get_file_areas($course, $cm, $context) {
    return array(
        'uploadad' => 'mokitul'
    );
}

/**
 * File browsing support for mod_mokitul file areas.
 *
 * @package     mod_mokitul
 * @category    files
 *
 * @param file_browser $browser
 * @param array $areas
 * @param stdClass $course
 * @param stdClass $cm
 * @param stdClass $context
 * @param string $filearea
 * @param int $itemid
 * @param string $filepath
 * @param string $filename
 * @return file_info Instance or null if not found.
 */
function mokitul_get_file_info($browser, $areas, $course, $cm, $context, $filearea, $itemid, $filepath, $filename) {
    global $CFG;

    /*
    if ($context->contextlevel != CONTEXT_MODULE) {
        return null;
    }

    // Filearea must contain a real area.
    if (!isset($areas[$filearea])) {
        return null;
    }

    if (!has_capability('moodle/course:managefiles', $context)) {
        // Students can not peek here!
        return null;
    }
    */

    // get_file($contextid,$component,$filearea,$itemid,$filepath,$filename)
    $fs = get_file_storage();
    if ($filearea === 'attachments' ) {
        // $filepath = is_null($filepath) ? '/' : $filepath;
        // $filename = is_null($filename) ? '.' : $filename;

        if (!$storedfile = $fs->get_file($context->id,
                                         'mod_mokitul',
                                         'attachments',
                                         0,
                                         '/',
                                         'llama2.pdf')) {
            // Not found.
            return null;
        }

        $urlbase = $CFG->wwwroot . '/pluginfile.php';

        return new file_info_stored($browser,
                                    $context,
                                    $storedfile,
                                    $urlbase,
                                    $areas[$filearea],
                                    false,
                                    true,
                                    true,
                                    false);
    }

    // Not found.
    return null;
}

/**
 * Serves the files from the mod_mokitul file areas.
 *
 * @package     mod_mokitul
 * @category    files
 *
 * @param stdClass $course The course object.
 * @param stdClass $cm The course module object.
 * @param stdClass $context The mod_mokitul's context.
 * @param string $filearea The name of the file area.
 * @param array $args Extra arguments (itemid, path).
 * @param bool $forcedownload Whether or not force download.
 * @param array $options Additional options affecting the file serving.
 */
# function mokitul_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, $options = array()) {
#     global $DB, $CFG;
#
#     if ($context->contextlevel != CONTEXT_MODULE) {
#         send_file_not_found();
#     }
#
#     require_login($course, true, $cm);
#     send_file_not_found();
# }

function mokitul_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options=array()) {
    if ($context->contextlevel != CONTEXT_MODULE) {
        return false;
    }

    require_login($course, true, $cm);

    if ($filearea !== 'attachments') {
        return false;
    }

    $fs = get_file_storage();
    $filename = array_pop($args);
    $filepath = $args ? '/'.implode('/', $args).'/' : '/';
    if (!$file = $fs->get_file($context->id, 'mod_mokitul', 'attachments', 0, $filepath, $filename) or $file->is_directory()) {
        return false;
    }

    send_stored_file($file, 0, 0, $forcedownload, $options);
}

/*
 * Adds ours custom buttons to the course view of the activity.
 */
function mokitul_cm_info_view(cm_info $cm) {
    global $PAGE;

    $courseId = $_GET["id"];

    $modulecontext = context_module::instance($cm->id);
    $fs = get_file_storage();
    $files = $fs->get_area_files($modulecontext->id, 'mod_mokitul', 'attachments');

    $file = select_file_with_attachment($files);

    // $summarizeButtonIdentifier = "mokitul_summarize_pdf_$cm->id";
    // $summarizeButton = html_writer::tag('button', 'Zusammenfassung generieren', array("id" => $summarizeButtonIdentifier,"class" => "btn btn-primary"));

    $url = new moodle_url('/local/mokitul/api/download.php', array('file_id'=> $file->get_id()));

    $downloadButton = html_writer::link($url, 'Download', array('style'=>'margin: 5px', 'class' => 'btn btn-primary'));

    // $getRelatedPapersButtonIdentifier = "get_related_papers_$cm->id";
    // $getRelatedPapers = html_writer::tag('button', 'Get Related Papers', array("id" => $getRelatedPapersButtonIdentifier,"class" => "mokitul_activity_button"));

    // $cm->set_after_link($summarizeButton . $getRelatedPapers);
    $cm->set_after_link(/* $summarizeButton . */ $downloadButton);

    //$PAGE->requires->js_call_amd('mod_mokitul/lib', 'addSummarizeFunction', ["fileId" => $file->get_id(), "summarizeButtonIdentifier" => $summarizeButtonIdentifier]);
}


function select_file_with_attachment(array $files) {
    foreach ($files as $file) {
        if($file->get_source() != NULL) return $file;
    }
}