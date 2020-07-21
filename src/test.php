<?php
include __DIR__ . '/FEvent.php';
include __DIR__ . '/FQueue.php';

use Fang\FEvent;
use Fang\FQueue;

$buf = new FQueue();

FEvent::init();

//添加定时器
FEvent::addTime(0.5, function($data) use($buf){
    var_dump(time());
    $d = $buf->pop();
    var_dump('---------------------' . $d);
    var_dump($data);
    $data->ev->addTimer(1);
    if(isset($data->c)) {
        $data->c++;
    } else {
        $data->c = 0;
    }
}, 'test by fang');

//添加一个监听
FEvent::addHttpListen(['29999'=>'127.0.0.1'], function($req){
        echo __METHOD__, PHP_EOL;
        echo "URI: ", $req->getUri(), PHP_EOL;
        echo "\n >> Sending reply ...";
        $buf = new \EventBuffer();
        $buf->add("test by fang, data.......");
        $req->sendReply(200, "OK", $buf);
        echo "OK\n";
    },

     ['/test' => function($req) use ($buf){
    $buf->push('abc-' . time());
    echo __METHOD__, PHP_EOL;
    echo "URI: ", $req->getUri(), PHP_EOL;
    echo "\n >> Sending reply ...";
    $ebuf = new \EventBuffer();
    $ebuf->add('buf len:' . $buf->count() . "</br>");
    $ebuf->add("memory userage: " . FEvent::getMemoryUsage() . "</br>");
    $ebuf->add("test by fang, data.......");
    $req->sendReply(200, "OK", $ebuf);
    echo "OK\n";

    }]);



//发起一个buffer request
$data = FEvent::addBufferEvent(function($bev, $name){
    var_dump($name);
    $input = $bev->getInput();
    $buf = '';
    while (($buf = $input->read(1024))) {
        var_dump($buf);
    }
},
NULL,
function($bev, $events, $std) {
    if ($events & \EventBufferEvent::CONNECTED) {
        echo "Connected.\n";
    } elseif ($events & (\EventBufferEvent::ERROR | \EventBufferEvent::EOF)) {
        if ($events & \EventBufferEvent::ERROR) {
            echo "DNS error: ", $bev->getDnsErrorString(), PHP_EOL;
        }

        echo "Closing, id:{$std->id}\n";
        \FEvent::removeEv($std->id);
    }
}
);

$output = $data->ev->getOutput();
if (!$output->add(
    "GET /test HTTP/1.0\r\n".
    "Connection: Close\r\n\r\n"
)) {
    exit("Failed adding request to output buffer\n");
}

/* Connect to the host syncronously.
 * We know the IP, and don't need to resolve DNS. */
if (!$data->ev->connect("127.0.0.1:9999")) {
    exit("Can't connect to host\n");
}


FEvent::start();