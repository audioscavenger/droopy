<?php
// TODO: mkdir subfolders
// TODO: use json config files

// get variables
$fileId = $_GET['dzuuid'];
$chunkTotal = $_GET['dztotalchunkcount'];

// file path variables
$logfile = 'chunk-upload.log';
$targetPath = dirname( __FILE__ ) . DIRECTORY_SEPARATOR . "uploads" . DIRECTORY_SEPARATOR;

// all the protections and cleanup below should also be done by the js client
$baseName = basename($_GET['fileName']);
// Remove anything which isn't a word, whitespace, number, or any of the following caracters: "-_~[]()."
// If you don't need to handle multi-byte characters
// you can use preg_replace rather than mb_ereg_replace
$baseName = mb_ereg_replace("([^\w\s\d\-_~,;\[\]\(\)\.])", '', $baseName);
// Remove any runs of periods
$baseName = mb_ereg_replace("([\.]{2,})", '.', $baseName);

$fileExt = '.'.$_GET['fileExt'];
$fileExt = ($fileExt == '.') ? '' : $fileExt;
$fileName = $baseName.$fileExt;

// Custom special case just for you: next step is to load the dict off a json file and have various subdirectories+fileLists
$customFiles = array(
  "nQ"  => array('get-nQ.cmd')
);
foreach($customFiles as $customSubFolder => $arrCutomFiles)
{
  if (in_array($fileName, $arrCutomFiles)) $targetPath = dirname( __FILE__ ) . DIRECTORY_SEPARATOR . $customSubFolder . DIRECTORY_SEPARATOR;
}

file_put_contents($logfile, date("Y-m-d H:i:s") .' , '. $_SERVER["HTTP_CF_IPCOUNTRY"] .' , '. $_SERVER["HTTP_X_REAL_IP"] .' , chunk-concat: '. "fileName={$fileName}" .PHP_EOL, FILE_APPEND);
file_put_contents($logfile, date("Y-m-d H:i:s") .' , '. $_SERVER["HTTP_CF_IPCOUNTRY"] .' , '. $_SERVER["HTTP_X_REAL_IP"] .' , chunk-concat: '. "targetPath={$targetPath}" .PHP_EOL, FILE_APPEND);

// unlink because for existing files, they will be appended!!
unlink("{$targetPath}{$fileName}");
file_put_contents($logfile, date("Y-m-d H:i:s") .' , '. $_SERVER["HTTP_CF_IPCOUNTRY"] .' , '. $_SERVER["HTTP_X_REAL_IP"] .' , chunk-concat: '. "unlink {$fileName}" .PHP_EOL, FILE_APPEND);
/* ========================================
  DEPENDENCY FUNCTIONS
======================================== */

// YOU CANNOT ADD file_put_contents($logfile) inside $returnResponse or uploads will fail completely
$returnResponse = function ($info = null, $filelink = null, $status = "ERROR") {
  file_put_contents('upload.log', date("Y-m-d H:i:s") .' concat: '. $info .' '. $filelink .' '. $status .PHP_EOL, FILE_APPEND);
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
for ($i = 0; $i < $chunkTotal; $i++) {

  // target temp file = 184e03c9-3b7b-4083-9185-647b87ba8872-1.ext
  $temp_file_path = realpath("{$targetPath}{$fileId}-{$i}{$fileExt}") or $returnResponse("Your chunk was lost mid-upload.");

  // copy chunk
  $chunk = file_get_contents($temp_file_path);
  if ( empty($chunk) ) $returnResponse("Chunks are uploading as empty strings.");

  // add chunk to main file
  file_put_contents($logfile, date("Y-m-d H:i:s") .' , '. $_SERVER["HTTP_CF_IPCOUNTRY"] .' , '. $_SERVER["HTTP_X_REAL_IP"] .' , chunk-concat: '. "FILE_APPEND {$fileId}-{$i}{$fileExt} to {$baseName}{$fileExt}" .PHP_EOL, FILE_APPEND);
  file_put_contents("{$targetPath}{$baseName}{$fileExt}", $chunk, FILE_APPEND | LOCK_EX);

  // delete chunk
  unlink($temp_file_path);
  if ( file_exists($temp_file_path) ) $returnResponse("temp file could not be deleted", $temp_file_path);

}
$returnResponse("", $fileName.$fileExt, 'DONE');

/* ========== a bunch of steps I removed below here because they're irrelevant, but I described them anyway ========== */
// create FileMaker record
// run FileMaker script to populate container field with newly-created file
// unlink newly created file
// return success

