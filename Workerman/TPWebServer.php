<?php
namespace Workerman;

class TPWebServer extends WebServer
{
    public function onMessage($connection)
    {
        parent::onMessage($connection);
        $pathinfo = pathinfo($_SERVER['REQUEST_URI']);
        isset($pathinfo['extension']) || $pathinfo['extension'] = 'php';
        if ($pathinfo['extension'] == 'php') {
            if (!extension_loaded("runkit")) {
                exit('Please install the runkit php extension!' . PHP_EOL);
            }
            foreach (Unset_OnRequest_Done_Constant_Names as $name) {
                @runkit_constant_remove($name);
            }
        }
        //var_dump(self::getEventLoopName());
    }
}