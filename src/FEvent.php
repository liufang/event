<?php
namespace Fang;

class FEvent
{
    /**
     * @var \EventBase
     */
    static $baseEvent = null;

    /**
     * @var array
     */
    static $evs = [];

    /**
     * 添加ev
     *
     * @param $name
     * @param $ev
     */
    private static function addEv($name, $ev)
    {
        if(isset(self::$evs[$name])) {
            self::removeEv($name);
        }
        static::$evs[$name] = $ev;
    }

    /**
     * 获取event
     *
     * @param string $name
     * @return \Event
     */
    private static function getEv($name)
    {
        if(isset(self::$evs[$name])) {
            return self::$evs[$name];
        }
        return null;
    }

    /**
     * 删除ev
     *
     * @param $name
     * @param $ev
     * @return void
     */
    public static function removeEv($name)
    {
        if(isset(self::$evs[$name])) {
            if(method_exists(self::$evs[$name], 'close')) {
                self::$evs[$name]->close();
            }
            if(method_exists(self::$evs[$name], 'del')) {
                self::$evs[$name]->del();
            }
        }
        unset(self::$evs[$name]);
    }

    /**
     * init
     */
    public static function init()
    {
        self::$baseEvent = new \EventBase();
    }

    /**
     * 添加时间事件
     *
     * @param $ts
     * @param $callback
     * @return void
     */
    public static function addTime($ts, $callback, $payload = null)
    {
        $std = new \stdClass();
        $std->id = uniqid();
        $std->payload = $payload;
        $e = \Event::timer(self::$baseEvent, $callback, $std);
        $e->addTimer($ts);
        $std->ev = $e;
        self::addEv($std->id, $e);
        return $std;
    }

    /**
     * 添加Http监听服务
     *
     * @param $name
     * @param $address
     * @param array $callbacks
     * @param null $defaultCallback
     * @return \EventHttp
     */
    public static function addHttpListen($address, $defaultCallback = null, $callbacks = [], $options = [])
    {
        $std = new \stdClass();
        $std->id = uniqid();
        $std->payload = null;
        $http = new \EventHttp(self::$baseEvent);
        foreach($callbacks as $key => $callback) {
            $http->setCallback($key, $callback);
        }
        $http->setDefaultCallback($defaultCallback);
        foreach($address as $port => $host) {
            if(!$http->bind($host, $port)) {
                exit('http listen fail, at:' . $host . '::' . $port . "\r\n");
            }

            // $fd = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
            // if (!socket_set_option($fd, SOL_SOCKET, SO_REUSEADDR, 1000)) {
            //     echo 'Unable to set option on socket: '. socket_strerror(socket_last_error()) . PHP_EOL;
            // }
            
            // if (!\socket_bind($fd, $host, $port)) {
            //     exit("socket_bind failed\n");
            // }
            // \socket_listen($fd, 0);
            // \socket_set_nonblock($fd);

            // $std->fd = $fd;

            // if(!$http->accept($fd)) {
            //     echo "Accept failed\n";
            //     exit(1);
            // }

            // if(!$http->bind($host, $port)) {
            //      exit('http listen fail, at:' . $host . '::' . $port . "\r\n");
            // }
        }

        if(isset($options['max_body_size'])) {
            $http->setMaxBodySize($options['max_body_size']);
        }
        if(isset($options['max_header_size'])) {
            $http->setMaxHeaderSize($options['max_header_size']);
        }
        if(isset($options['time_out'])) {
            $http->setTimeout($options['time_out']);
        }

        self::addEv($std->id, $http);
        $std->ev = $http;
        return $std;
    }

    /**
     * add buffer event
     *
     * @param $name
     * @param $readcb
     * @param null $writecb
     * @param null $eventcb
     * @return EventBufferEvent
     */
    public static function addBufferEvent($readcb, $writecb = null, $eventcb = null)
    {

        $std = new \stdClass();
        $std->id = uniqid();
        $std->payload = null;

        $bufEv = new \EventBufferEvent(self::$baseEvent, NULL,
            \EventBufferEvent::OPT_CLOSE_ON_FREE | \EventBufferEvent::OPT_DEFER_CALLBACKS,
            $readcb, $writecb, $eventcb, $std
        );
        $bufEv->enable(Event::READ | Event::WRITE);
        $std->ev = $bufEv;
        self::addEv($std->id, $bufEv);
        return $std;
    }

    /**
     * start
     */
    public static function start()
    {
        self::$baseEvent->dispatch();
    }

    /**
     * stop events
     */
    public static function stop()
    {
        self::$baseEvent->stop();
    }

    /**
     * 获取内容使用量
     */
    public static function getMemoryUsage()
    {
        $size = memory_get_usage();
        $unit=array('b','kb','mb','gb','tb','pb');
        return @round($size/pow(1024,($i=floor(log($size,1024)))),2).' '.$unit[$i];
    }
}