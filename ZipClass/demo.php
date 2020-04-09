<?php 

    include('./zip_helper.php');
    $zipObj = new Zip();
    $sendZipRes = $zipObj->sendZip('./file', '打包后的文件名.zip');
    if ($sendZipRes) {
        echo '打包成功！';
    } else {
        echo '打包失败！';
    } 

    echo '<hr>';
    
    $unzipRes = $zipObj->unzip('./打包后的文件名.zip', './解压后的文件目录');
    echo "解压成功！解压后目录含有{$unzipRes}个文件。";

    die();