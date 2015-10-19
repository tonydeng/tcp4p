<?php
ini_set('display_errors', true);
error_reporting(E_ALL &~E_NOTICE);
date_default_timezone_set('Asia/Shanghai');
include __DIR__ . '/vendor/autoload.php';
use Thrift\Transport\TSocket,
	Thrift\Transport\THttpClient,
	Thrift\Exception\TException,

	Thrift\Transport\TBufferedTransport,
	Thrift\Transport\TFramedTransport,
	Thrift\Transport\TMemoryBuffer,
	//Thrift\Transport\TFramedTransport,

	Thrift\Protocol\TBinaryProtocol,
	Thrift\Protocol\TBinaryProtocolAccelerated,
	Thrift\Protocol\TCompactProtocol,
	Thrift\Protocol\TJSONProtocol; //传输协议

try {

//$socket = new TSocket('localhost', 9090);
$socket = new THttpClient('c.cc', 80, 'start.php');
/**
 * 数据传输方法
 */
$transport = new TBufferedTransport($socket, 1024, 1024);
$transport = new TFramedTransport($socket);

/**
 * 数据传输协议
 */
$protocol = new TBinaryProtocolAccelerated($transport);
$protocol = new TCompactProtocol($transport);
$protocol = new TJSONProtocol($transport);


$transport->open();
$client = new \Services\HelloWorld\HelloWorldClient($protocol);

$a = $client->sayHello('word');
var_dump($a);exit;

$transport->close();

	
} catch (TException $e) {
	var_dump($e->getMessage());
}