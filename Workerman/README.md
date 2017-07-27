
## 本项目依赖workerman，核心的业务文件为start.php, upload_client.php

### start.php
```php
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
```

### 文件上传客户端
```php
<input type="file" id="file"/>

<button id="upload">上传</button>

<div id="output" style="font-size:16px;margin: 15px;color: green;width: 300px;background-color: #ccc;">
    <div id="percent" style="background-color: green;height: 25px; color: white;"></div>
</div>

<script src="http://libs.baidu.com/jquery/2.0.0/jquery.min.js"></script>
<script>
    var ws;
    var file;
    var size = 64; //每次发送的数据字节长度bytes
    var offset;
    $(document).ready(function () {
        ws = new WebSocket("ws://127.0.0.1:8000");

        ws.onopen = function () {
            $("#percent").html("ws连接成功!");
        };

        ws.onmessage = function (e) {
            console.log(e.data);
            if (e.data == 'OK') {
                $("#percent").html("上传成功!");
            } else {
                //分片上传
                if (window.file.size > window.offset) {
                    var end_pos = window.offset + size;
                    if (end_pos > window.file.size) {
                        end_pos = window.file.size;
                    }
                    var buffer = window.file.slice(window.offset, end_pos);
                    ws.send(buffer);
                    $("#percent").css("width", (300 * window.offset) / window.file.size + "px");
                    window.offset += size;
                }
            }
        };

        ws.onclose = function () {
            $("#output").html("连接已关闭");
        };
    });
    $("#upload").click(function () {
        window.file = $("#file")[0].files[0];
        window.offset = 0;
        var data = {
            "uid": 1558, //根据业务登录后获取
            "filename": window.file.name,
            "total": window.file.size,
            "file_pos": window.offset,
        };

        console.log(data.total);
        ws.send(JSON.stringify(data));
    });

</script>
```
