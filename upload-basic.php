<?php
//////////////////////////////////////////
////////// custom variables //////////////
$uploadPath = "elFinder" . DIRECTORY_SEPARATOR . "files";
//////////////////////////////////////////

// https://www.php.net/manual/en/features.file-upload.post-method.php
$targetPath = dirname( __FILE__ ) . DIRECTORY_SEPARATOR . $uploadPath . DIRECTORY_SEPARATOR;
$basename = basename($_FILES['userfile']['name']);
$uploadfile = $targetPath . $basename;
$logfile = 'upload-basic.log';
file_put_contents($logfile, date("Y-m-d H:i:s") .' , '. $_SERVER["HTTP_CF_IPCOUNTRY"] .' , '. $_SERVER["HTTP_X_REAL_IP"] .' , upload-basic: '. "uploadfile={$uploadfile}" .' '. $_FILES['userfile']['size'] . 'b' .PHP_EOL, FILE_APPEND);

// print_r($_FILES); // ddebug

// echo '<pre>';
if (move_uploaded_file($_FILES['userfile']['tmp_name'], $uploadfile)) {
    echo "{$basename} is valid, and was successfully uploaded.\n";
} else {
    echo "Possible file upload attack!\n";
}

// echo 'Here is some more debugging info:';
// print_r($_FILES);

// print "</pre>";
