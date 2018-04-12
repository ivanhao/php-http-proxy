<?php 
/**
 * This file is part of php-http-proxy.
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the MIT-LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @author walkor<walkor@workerman.net>
 * @copyright walkor<walkor@workerman.net>
 * @link http://www.workerman.net/
 * @license http://www.opensource.org/licenses/mit-license.php MIT License
 */
use \Workerman\Worker;
use \Workerman\Connection\AsyncTcpConnection;

$user="USERNAME";
$pass="PASSWORD";
$ports=9001;
// Autoload.
require_once __DIR__ . '/Workerman/Autoloader.php';

// Create a TCP worker.
$worker = new Worker('tcp://0.0.0.0:'.$ports);
// 6 processes
$worker->count = 6;
// Worker name.
$worker->name = 'php-http-proxy';

// Emitted when data received from client.
$worker->onMessage = function($connection, $buffer)
{
    $authkey=Base64_encode($GLOBALS['user'].":".$GLOBALS['pass']);
    echo $buffer;
    // Parse http header.
    list($http_header, $http_body) = explode("\r\n\r\n", $buffer,2);
    $header_data = explode("\r\n", $http_header);
    list($method, $addr, $http_version) = explode(' ', $header_data[0]);
    $url_data = parse_url($addr);
    unset($header_data[0]);
    foreach($header_data as $content){
        if(empty($content))
        {
            continue;
        }
        list($key, $value) = explode(':', $content, 2);
        $key = strtolower($key);
        $value = trim($value);
        //switch($key)
        while(true)
        {
            $authList = array('authorization','www-authorization','proxy-authorization');
            if ( in_array($key,$authList) )
            {
                $authcode=$key;
                list($k, $v) = explode(' ',$value,2);
                if ($k == 'Basic' && $v == $authkey )
                {
                    $authed=1;
                }
                break;
            }
            else
            {
                break;
            }
        }
    }
    $addr = !isset($url_data['port']) ? "{$url_data['host']}:80" : "{$url_data['host']}:{$url_data['port']}";
    // Async TCP connection.
    $remote_connection = new AsyncTcpConnection("tcp://$addr");
    // CONNECT.
    if ( isset($authed) && $authed == 1)
    {
        if ($method !== 'CONNECT')
        {
           //echo "authed:".$authed;
           $remote_connection->send($buffer);
        }
        else
        {
            $connection->send("HTTP/1.1 200 Connection Established\r\n\r\n");
        }
    }
    else
    {
        $connection->send("HTTP/1.1 407 Proxy Authorization Required\r\nProxy-Authenticate: Basic realm=' ".$addr."'\r\n");
        //$connection->send("HTTP/1.1 401 Proxy Authorization Required\r\nWWW-Authenticate: Basic realm=' ".$addr."'\r\n");
        $connection->close();
    }
    // Pipe.
    $remote_connection ->pipe($connection);
    $connection->pipe($remote_connection);
    $remote_connection->connect();
};

// Run.
Worker::runAll();
