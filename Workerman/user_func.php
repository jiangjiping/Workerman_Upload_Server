<?php
define('STDOUT_FILE_FORMAT', __DIR__ . '/stdout_%d.txt');


function php_processor($request, $response)
{
    chdir(SWOOLE_WWW_ROOT);
    ob_start();
    //set_cgi_params($request, $response);
    try {
       // register_once_exit_callback($response);
        include SWOOLE_WWW_ROOT . '/index.php';
        $content = '';
        while (ob_get_level() > 0) {
            $content .= ob_get_clean();
        }
        cgi_params_destory();
        $response->send($content);
    } catch (\Exception $ex) {
        $response->send($ex->getMessage());
    }
}

function set_cgi_params($request, $response)
{
    define("SWOOLE_CGI", 1);
    $_SERVER['HTTP_HOST'] = 'yqds.dev';
    $index = (basename(__FILE__));
    if (isset($request->server) && !empty($request->server)) {
        foreach ($request->server as $key => $val) {
            $_SERVER[strtoupper($key)] = $val;
        }
    }
    foreach ($_SERVER as $key => &$val) {
        $val = str_replace("{$index}", 'index.php', $val);
    }
    if (isset($request->post) && !empty($request->post)) {
        if (isset($request->header['x-requested-with']) && $request->header['x-requested-with'] == 'XMLHttpRequest')
            $response->header('Content-Type', 'text/html; charset=UTF-8');
        else
            $response->header('Content-Type', 'application/json');
        foreach ($request->post as $key => $val) {
            $_POST[$key] = $val;
        }
    }
    if (isset($request->get) && !empty($request->get)) {
        foreach ($request->get as $key => $val) {
            $_GET[$key] = $val;
        }
    }
}


function reset_std($worker_pid)
{
    global $STDOUT, $STDERR;
    is_resource(STDOUT) && fclose(STDOUT);
    is_resource(STDERR) && fclose(STDERR);
    $STDOUT = fopen(sprintf(STDOUT_FILE_FORMAT, $worker_pid), "a");
    $STDERR = fopen(sprintf(STDOUT_FILE_FORMAT, $worker_pid), "a");
}

function cgi_params_destory()
{
    if (!extension_loaded("runkit")) {
        exit('Please install the runkit php extension!' . PHP_EOL);
    }
    foreach (Unset_OnRequest_Done_Constant_Names as $name) {
        @runkit_constant_remove($name);
    }
}

function register_once_exit_callback($response)
{
    if (defined("REGISTER_EXIT_FUNC")) {
        return;
    }
    define("REGISTER_EXIT_FUNC", 1);
    register_shutdown_function(function () use ($response) {
        $filename = sprintf(STDOUT_FILE_FORMAT, posix_getpid());
        $find = '}[20';
        //下面这行代码file_get_contents($filename) 无法获取内容, 原生php可以获取
        $data = file_get_contents($filename);
        file_put_contents(__DIR__ . '/debug.txt', 'getfrom:' . $filename . '|' . $data . "\n", FILE_APPEND);
        $json = substr($data, 0, strrpos($data, $find) + 1);
        $data = json_decode($json, true);
        if (!empty($data) && isset($data['code']) && isset($data['data'])) {
            $response->end($json);
        } else {
            $response->end(json_encode([
                'code' => 1,
                'msg'  => '参数错误!'
            ]));
        }
        @unlink($filename);
    });
}