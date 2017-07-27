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