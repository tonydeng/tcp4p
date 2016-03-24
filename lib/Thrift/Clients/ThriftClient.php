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
 * 通用客户端,支持故障ip自动踢出及探测节点是否已经存活
 */
class ThriftClient
{
    /**
     * 客户端实例
     * @var array
     */
    private static $instance = array();

    /**
     * 配置
     * @var array
     */
    private static $config = null;

    /**
     * 故障节点共享内存fd
     * @var resource
     */
    private static $badAddressShmFd = null;

    /**
     * 故障的节点列表
     * @var array
     */
    private static $badAddressList = null;

    /**
     * 设置/获取 配置
     *  array(
     *      'HelloWorld' => array(
     *          'addresses' => array(
     *              '127.0.0.1:9090',
     *              '127.0.0.2:9090',
     *              '127.0.0.3:9090',
     *          ),
     *      ),
     *      'UserInfo' => array(
     *          'addresses' => array(
     *              '127.0.0.1:9090'
     *          ),
     *      ),
     *  )
     * @param array $config
     * @return array
     */
    public static function config(array $config = array())
    {
        if(!empty($config))
        {
            // 赋值
            self::$config = $config;
            // 注册address到AddressManager
            $address_map = array();
            foreach(self::$config as $key => $item)
            {
                $address_map[$key] = $item['addresses'];
            }
            AddressManager::config($address_map);
        }
        return self::$config;
    }

    /**
     * 获取实例
     * @param string $serviceName 服务名称
     * @param bool $newOne 是否强制获取一个新的实例
     * @return object/Exception
     */
    public static function instance($serviceName, $newOne = false)
    {
        if (empty($serviceName))
        {
            throw new \Exception('ServiceName can not be empty');
        }

        if($newOne)
        {
            unset(self::$instance[$serviceName]);
        }

        if(!isset(self::$instance[$serviceName]))
        {
            self::$instance[$serviceName] = new ThriftInstance($serviceName);
        }

        return self::$instance[$serviceName];
    }

    /**
     * getProtocol
     * @param string $service_name
     * @return string
     */
    public static function getProtocol($service_name)
    {
        $config = self::config();
        $protocol = 'TBinaryProtocol';
        if(!empty($config[$service_name]['thrift_protocol']))
        {
            $protocol = $config[$service_name]['thrift_protocol'];
        }
        return "\\Thrift\\Protocol\\".$protocol;
    }

    /**
     * getTransport
     * @param string $service_name
     * @return string
     */
    public static function getTransport($service_name)
    {
        $config = self::config();
        $transport= 'TBufferedTransport';
        if(!empty($config[$service_name]['thrift_transport']))
        {
            $transport = $config[$service_name]['thrift_transport'];
        }
        return "\\Thrift\\Transport\\".$transport;
    }

    /**
     * 获得服务目录，用来查找thrift生成的客户端文件
     * @param string $service_name
     * @return string
     */
    public static function getServiceDir($service_name)
    {
        $config = self::config();
        if(!empty($config[$service_name]['service_dir']))
        {
            $service_dir = $config[$service_name]['service_dir']."/$service_name";
            self::includeFile($service_dir);
            $class_name = $service_name . "Client";
        }
        else if (!empty($config[$service_name]['namespace_name'])) {
            $class_name = $config[$service_name]['namespace_name'];
        }
        else
        {
            throw new \Exception("service_dir not found");
        }
        return $class_name;
    }

    /**
     * 载入thrift生成的客户端文件
     * @throws \Exception
     * @return void
     */
    protected static function includeFile($service_dir)
    {
        if (!is_dir($service_dir)) {
            throw new Exception("$service_dir is not a valid directory");
            
        }
        foreach(glob($service_dir.'/*.php') as $php_file)
        {
            require_once $php_file;
        }
    }
}