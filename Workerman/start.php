<?php

/**
 * 使用websocket作为文件上传服务端
 */

use Workerman\Worker;

require_once __DIR__ . '/Autoloader.php';

$ws_server = new Worker("websocket://127.0.0.1:8000");

$ws_server->count = 4;

/**
 * data格式为json
 * 　{
 *      "type":"upload|break_upload",
 *      "uid":1558,
 *      "filename":"sss.png",
 *      "total":1555,
 *      "file_pos":5,
 *      "body":"file data"
 *   }
 * @param $conn
 * @param $data
 */
$ws_server->onMessage = function ($conn, $data) {
    $rawdata = $data;
    $data = json_decode($data, true);
    if (!empty($data) && is_array($data)) {
        $conn->fileInfo = $data;
        $conn->send('init');
        return;
    }
    $filename = __DIR__ . '/Upload/' . $conn->fileInfo['uid'] . '_' . $conn->fileInfo['filename'];
    
    file_put_contents($filename, $rawdata, FILE_APPEND);
    //清楚文件信息缓存,以便获取最准确的文件大小
    clearstatcache();
    $filesize = filesize($filename);
    if ($conn->fileInfo['total'] == $filesize) {
        //传输完成
        $conn->fileInfo = [];
        $conn->send("OK");
    } else {
        //下一个分片的传输起始位置
        $conn->send($filesize);
    }
};

$ws_server->runAll();