### TCP4P (Thrift Client Pool For PHP)

整合Thrift官方提供的包,融合workerman客户端连接并对起进行优化。

## 包加载说明
修改composer.json
```
"require": {
	"tonydeng/tcp4p"	: "0.9.*"
}
```

根据Thrift idl生成客户端代码，生成命令示例：
```
thrift --gen php message.thrift
```

然后加在客户端代码，可以通过composer加载；修改composer.json：
```
"autoload" : {
		"classmap" : ["客户端代码目录"]
}
```

如果对composer自动加载不熟悉可以参考[composer手册](http://docs.phpcomposer.com/)


## 使用示例
```
use Thrift\Clients\ThriftClient,
	 example\Message;
ThriftClient::config(array(
                        'MessageService' => array(
                            'addresses' => array(
                               '127.0.0.1:9001'
                            ),
                            'thrift_protocol' => 'TCompactProtocol',//不配置默认是TBinaryProtocol，对应服务端Message.conf配置中的thrift_protocol
                            'thrift_transport' => 'TFramedTransport',//不配置默认是TBufferedTransport，对应服务端Message.conf配置中的thrift_transport
                            "namespace_name" => "\\example\\MessageServiceClient",
                            "service_dir" => "yourpath/example" //如果不想使用命名空间可以填写路径自动加载
                        ),
                    )
                );
$client = ThriftClient::instance("MessageService");
```