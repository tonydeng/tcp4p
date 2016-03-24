<?php
/**
 * This file is part of workerman.
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

namespace Thrift\Clients;
/**
 *
 * thrift异步客户端实例
 * @author liangl
 *
 */
class ThriftInstance
{
    /**
     * 异步发送前缀
     * @var string
     */
    const ASYNC_SEND_PREFIX = 'asend_';

    /**
     * 异步接收后缀
     * @var string
     */
    const ASYNC_RECV_PREFIX = 'arecv_';

    /**
     * 服务名
     * @var string
     */
    public $serviceName = '';

    /**
     * thrift实例
     * @var array
     */
    protected $thriftInstance = null;

    /**
     * thrift异步实例['asend_method1'=>thriftInstance1, 'asend_method2'=>thriftInstance2, ..]
     * @var array
     */
    protected $thriftAsyncInstances = array();

    /**
     * 初始化工作
     * @return void
     */
    public function __construct($serviceName)
    {
        if(empty($serviceName))
        {
            throw new \Exception('serviceName can not be empty', 500);
        }
        $this->serviceName = $serviceName;
    }

    /**
     * 方法调用
     * @param string $name
     * @param array $arguments
     * @return mix
     */
    public function __call($method_name, $arguments)
    {
        // 异步发送
        if(0 === strpos($method_name ,self::ASYNC_SEND_PREFIX))
        {
            $real_method_name = substr($method_name, strlen(self::ASYNC_SEND_PREFIX));
            $arguments_key = serialize($arguments);
            $method_name_key = $method_name . $arguments_key;
            // 判断是否已经有这个方法的异步发送请求
            if(isset($this->thriftAsyncInstances[$method_name_key]))
            {
                // 删除实例，避免在daemon环境下一直出错
                unset($this->thriftAsyncInstances[$method_name_key]);
                throw new \Exception($this->serviceName."->$method_name(".implode(',',$arguments).") already has been called, you can't call again before you call ".self::ASYNC_RECV_PREFIX.$real_method_name, 500);
            }

            // 创建实例发送请求
            $instance = $this->__instance();
            $callback = array($instance, 'send_'.$real_method_name);
            if(!is_callable($callback))
            {
                throw new \Exception($this->serviceName.'->'.$method_name. ' not callable', 400);
            }
            $ret = call_user_func_array($callback, $arguments);
            // 保存客户单实例
            $this->thriftAsyncInstances[$method_name_key] = $instance;
            return $ret;
        }
        // 异步接收
        if(0 === strpos($method_name, self::ASYNC_RECV_PREFIX))
        {
            $real_method_name = substr($method_name, strlen(self::ASYNC_RECV_PREFIX));
            $send_method_name = self::ASYNC_SEND_PREFIX.$real_method_name;
            $arguments_key = serialize($arguments);
            $method_name_key = $send_method_name . $arguments_key;
            // 判断是否有发送过这个方法的异步请求
            if(!isset($this->thriftAsyncInstances[$method_name_key]))
            {
                throw new \Exception($this->serviceName."->$send_method_name(".implode(',',$arguments).") have not previously been called", 500);
            }

            $instance = $this->thriftAsyncInstances[$method_name_key];
            // 先删除客户端实例
            unset($this->thriftAsyncInstances[$method_name_key]);
            $callback = array($instance, 'recv_'.$real_method_name);
            if(!is_callable($callback))
            {
                throw new \Exception($this->serviceName.'->'.$method_name. ' not callable', 400);
            }
            // 接收请求
            $ret = call_user_func_array($callback, array());

            return $ret;
        }

        // 同步调用
        $success = true;
        // 每次都重新创建一个实例
        $this->thriftInstance = $this->__instance();
        $callback = array($this->thriftInstance, $method_name);
        if(!is_callable($callback))
        {
            throw new \Exception($this->serviceName.'->'.$method_name. ' not callable', 1400);
        }
        // 调用客户端方法
        $ret = call_user_func_array($callback, $arguments);
        // 每次都销毁实例
        $this->thriftInstance = null;

        return $ret;
    }

    /**
     * 获取一个实例
     * @return instance
     */
    protected function __instance()
    {
        // 获取一个服务端节点地址
        $address = AddressManager::getOneAddress($this->serviceName);
        list($ip, $port) = explode(':', $address);

        // Transport
        $socket = new \Thrift\Transport\TSocket($ip, $port);
        $transport_name = ThriftClient::getTransport($this->serviceName);
        $transport = new $transport_name($socket);
        // Protocol
        $protocol_name = ThriftClient::getProtocol($this->serviceName);
        $protocol = new $protocol_name($transport);
        try 
        {
            $transport->open();
        }
        catch(\Exception $e)
        {
            // 无法连上，则踢掉这个地址
            AddressManager::kickAddress($address);
            throw $e;
        }

        // 客户端类名称
        $class_name = ThriftClient::getServiceDir($this->serviceName);

        // 类不存在则报出异常
        if(!class_exists($class_name))
        {
            throw new \Exception("Class $class_name not found in directory $service_dir");
        }
        // 初始化一个实例
        return new $class_name($protocol);
    }

}