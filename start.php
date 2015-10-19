<?php
ini_set('display_errors', true);
error_reporting(E_ALL &~E_NOTICE);
date_default_timezone_set('Asia/Shanghai');
ignore_user_abort();
set_time_limit(0);

include __DIR__ . '/vendor/autoload.php';

use Thrift\Server\TServerSocket,
	Thrift\Exception\TException,
	Thrift\Transport\TBufferedTransport,
	Thrift\Transport\TFramedTransport,
	Thrift\Transport\TMemoryBuffer, //数据内存间调用
	Thrift\Transport\TPhpStream as TPhpStream,

	Thrift\Protocol\TBinaryProtocol,
	Thrift\Protocol\TBinaryProtocolAccelerated,
	Thrift\Protocol\TCompactProtocol,
	Thrift\Protocol\TJSONProtocol; //传输协议

/**
 * Thrift 服务端
 */
class HelloWorldExample implements \Services\HelloWorld\HelloWorldIf
{
	
	public function sayHello($name)
	{
		return "Hello! " . $name;
	}
}

header('Content-Type', 'application/x-thrift');
try {
$handler   = new HelloWorldExample();

$processor = new \Services\HelloWorld\HelloWorldProcessor($handler);

/**
 * 服务端数据间读写
 * 1、内存I/O
 */

$IO = new TPhpStream((TPhpStream::MODE_R | TPhpStream::MODE_W));

/**
 * 数据传输方式
 */
$transport = new TBufferedTransport($IO);
$transport = new TFramedTransport($IO);
/**
 * 数据传输协议
 */
$protocol = new TBinaryProtocolAccelerated($transport, true, true);
$protocol = new TCompactProtocol($transport);
$protocol = new TJSONProtocol($transport);

$transport->open();
$processor->process($protocol, $protocol);

$transport->close();

} catch (TException $e) {
	var_dump($e->getMessage());
}