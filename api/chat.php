<?php
    require_once('../../../config.php');
    require_once($CFG->libdir . '/filelib.php');

    // extract body parameters
    $_VALUES = json_decode(file_get_contents('php://input'), true);

    $courseId = $_VALUES['courseId'];
    $id = $_VALUES['id'];

    $curl = new \curl();

    $curl->setopt(array(
        'CURLOPT_HTTPHEADER' => array(
            'Content-Type: application/json',
        ),
    ));

    $serviceUrl = 'http://host.docker.internal:8000';
    
    $url = "$serviceUrl/moodle/summarize/$courseId/$id";
    $response = $curl->get($url);

    echo $response;
