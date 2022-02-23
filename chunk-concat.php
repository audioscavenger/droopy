<?php

// get variables
$fileId = $_GET['dzuuid'];
$chunkTotal = $_GET['dztotalchunkcount'];

// file path variables
$targetPath = dirname( __FILE__ ) . DIRECTORY_SEPARATOR . "uploads" . DIRECTORY_SEPARATOR;

// all the protections and cleanup below should also be done by the js client
$fileName = basename($_GET['fileName']);
// Remove anything which isn't a word, whitespace, number
// or any of the following caracters -_~,;[]().
// If you don't need to handle multi-byte characters
// you can use preg_replace rather than mb_ereg_replace
// Thanks @Łukasz Rysiak!
$fileName = mb_ereg_replace("([^\w\s\d\-_~,;\[\]\(\).])", '', $fileName);
// Remove any runs of periods (thanks falstro!)
$fileName = mb_ereg_replace("([\.]{2,})", '', $fileName);

$fileExt = '.'.$_GET['fileExt'];

/* ========================================
  DEPENDENCY FUNCTIONS
======================================== */

$returnResponse = function ($info = null, $filelink = null, $status = "ERROR") {
  file_put_contents('upload.log', date("Y-m-d H:i:s") .' '. $info .' '. $filelink .' '. $status .PHP_EOL, FILE_APPEND);
  if ($status == "ERROR") die (json_encode( array(
    "status" => $status,
    "info" => $info,
    "file_link" => $filelink
  )));
};

/* ========================================
  CONCATENATE UPLOADED FILES
======================================== */

// loop through temp files and grab the content
for ($i = 1; $i <= $chunkTotal; $i++) {

  // target temp file = 184e03c9-3b7b-4083-9185-647b87ba8872-1.ext
  $temp_file_path = realpath("{$targetPath}{$fileId}-{$i}{$fileExt}") or $returnResponse("Your chunk was lost mid-upload.");

  // copy chunk
  $chunk = file_get_contents($temp_file_path);
  if ( empty($chunk) ) $returnResponse("Chunks are uploading as empty strings.");

  // add chunk to main file
  file_put_contents("{$targetPath}{$fileName}{$fileExt}", $chunk, FILE_APPEND | LOCK_EX);

  // delete chunk
  unlink($temp_file_path);
  if ( file_exists($temp_file_path) ) $returnResponse("temp file could not be deleted", $temp_file_path, 'success');

}

/* ========== a bunch of steps I removed below here because they're irrelevant, but I described them anyway ========== */
// create FileMaker record
// run FileMaker script to populate container field with newly-created file
// unlink newly created file
// return success

