<?php

include '../vendor/autoload.php';

use MindEdge\Subtitles\Subtitles;


Subtitles::convert('subtitles.ttml', 'subtitles_from_ttml.vtt');

$str = file_get_contents('subtitles.xml');
$subtitles = Subtitles::load($str, 'ttml');
$subtitles->save('subtitles_from_xml.vtt');
