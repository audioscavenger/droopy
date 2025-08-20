<?php
error_reporting(E_ALL);

//////////////////////////////////////////
////////// custom variables //////////////
$customSubFolder = "";
$customFiles = array(
  "nQ" => array(
                "filename" => array('get-nQ.cmd','nQ.cmd'),
                ),
  "DCIM" => array(
                "extension" => array('NEF'),
                )
  );
//////////////////////////////////////////

////////// common stuff //////////////
$targetPath = join(DIRECTORY_SEPARATOR, array(__DIR__, "uploads"));
$logName = pathinfo(__FILE__, PATHINFO_FILENAME);
$logfile = $logName . '.log';
// we can't tell if behind a proxy or if any of these are set
$keys = ['REMOTE_ADDR','HTTP_X_FORWARDED_FOR','HTTP_CF_CONNECTING_IP','HTTP_X_REAL_IP'];
foreach ($keys as $key) { $remote_addr = isset($_SERVER[$key]) ? $_SERVER[$key] : $remote_addr;}
$remote_country = isset($_SERVER["HTTP_CF_IPCOUNTRY"]) ? $_SERVER["HTTP_CF_IPCOUNTRY"] : '??';

function logger($message='') {
  global $logfile, $remote_country, $remote_addr, $logName;
  file_put_contents($logfile, sprintf("%s %s [%-15s] %s: %s".PHP_EOL, date("Y-m-d H:i:s"), $remote_country, $remote_addr, $logName, $message), FILE_APPEND);
}
/////////////////////////////////////


if (!isset($_GET)) {
  logger("error: _GET=EMPTY");
  die();
}


// get variables
// _GET=
  // [dzuuid] => 9b165d63-d6c3-4b9b-8819-91841b0d11c1
  // [dztotalchunkcount] => 1
  // [fileName] => fileName
  // [fileExt] => fileExt

// Remove anything which isn't a word, number, or -
$dzuuid     = mb_ereg_replace("([^\w\d\-])", '', $_GET['dzuuid']);
// Remove anything which isn't a number
$dztotalchunkcount = mb_ereg_replace("([^\d])", '', $_GET['dztotalchunkcount']);
// If you don't need to handle multi-byte characters, you can use preg_replace rather than mb_ereg_replace
// Remove anything which isn't a word, whitespace, number, or any of the following caracters: "-_~[]()."
$fileName = mb_ereg_replace("([^\w\s\d\-_~\,\;\[\]\(\)\.])", '', $_GET['fileName']);
// also Remove any runs of periods
$fileName = mb_ereg_replace("([\.]{2,})", '', $fileName);
// Remove anything which isn't a word, whitespace, number, or any of the following caracters: "-_~[]()"
$fileExt = mb_ereg_replace("([^\w\s\d\-_~\,\;\[\]\(\)])", '', $_GET['fileExt']);

// logger("debug _GET=".print_r($_GET, true);
$targetBasename = ($fileExt) ? "{$fileName}.{$fileExt}" : $fileName;  // is there an extension?

function getTargetPath($customFiles, $targetBasename, $targetPath) {
  $fileExt = pathinfo($targetBasename, PATHINFO_EXTENSION);
  // Custom special case just for you: next step is to load the dict off a json file and have various subdirectories+fileLists
  foreach($customFiles as $customSubFolder => $arrCutomTypes) {
    if (array_key_exists("filename", $arrCutomTypes)) {
      if (in_array($targetBasename, $arrCutomTypes["filename"])) {
        $subFolder = $customSubFolder;
        $targetPath = join(DIRECTORY_SEPARATOR, array($targetPath , $customSubFolder) );
      }
    }
    if (array_key_exists("extension", $arrCutomTypes)) {
      if (in_array($fileExt, $arrCutomTypes["extension"])) {
        $subFolder = join(DIRECTORY_SEPARATOR, array($customSubFolder, date("Y-m-d")));
        $targetPath = join(DIRECTORY_SEPARATOR, array($targetPath , $subFolder));
        mkdir($targetPath, 0644);
      }
    }
  }
  return array("subFolder" => $subFolder, "targetPath" => $targetPath);
}
$targetPaths  = getTargetPath($customFiles, $targetBasename, $targetPath);
$targetFile   = join(DIRECTORY_SEPARATOR, array($targetPaths['targetPath'], $targetBasename));

  logger("target={$targetFile}");

// unlink if exist, or file will be appended!!
@unlink("{$targetFile}");
  logger("unlink {$targetFile}");
// ========================================
//  DEPENDENCY FUNCTIONS
// ========================================

// YOU CANNOT ADD file_put_contents($logfile) inside $returnResponse or uploads will fail completely
$returnResponse = function ($info = null, $filelink = null, $status = "ERROR") {
  // file_put_contents($logfile, sprintf("%s %s [%-15s] %s: %s".PHP_EOL, date("Y-m-d H:i:s"), $remote_country, $remote_addr, $logName, "returnResponse: $status");
  
  if ($status == "ERROR") die (json_encode( array(
    "status" => $status,
    "info" => $info,
    "file_link" => $filelink
  )));
  logger(json_encode( array(
    "status" => $status,
    "info" => $info,
    "file_link" => $filelink
  )));
  echo json_encode( array(
    "status" => $status,
    "info" => $info,
    "file_link" => $filelink
  ));
};

// ========================================
//  CONCATENATE UPLOADED FILES
// ========================================

// loop through temp files and grab the content
for ($chunkIndex = 0; $chunkIndex < $dztotalchunkcount; $chunkIndex++) {

  // target temp file = 184e03c9-3b7b-4083-9185-647b87ba8872-1.ext
  $temp_file_path = realpath("/tmp/chunk-{$dzuuid}-{$chunkIndex}") or $returnResponse("Your chunk was lost mid-upload.");
  logger("temp_file_path={$temp_file_path}");

  // copy chunk
  $chunk = file_get_contents($temp_file_path);
  if ( empty($chunk) ) {
    logger("error: Chunks are uploading as empty strings.");
    $returnResponse("Chunks are uploading as empty strings.");
  }

  // add chunk to main file
  file_put_contents($targetFile, $chunk, FILE_APPEND | LOCK_EX);
  logger("APPEND {$chunkIndex}/{$dztotalchunkcount} chunk-{$dzuuid}-{$chunkIndex} to {$targetFile}");

  // delete chunk
  unlink($temp_file_path);
  if ( file_exists($temp_file_path) ) $returnResponse("temp file could not be deleted", $temp_file_path);

}
$returnResponse("", join(DIRECTORY_SEPARATOR, array('uploads', $targetPaths['subFolder'], $targetBasename)), 'DONE');
  logger("concat: the end");

