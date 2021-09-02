<?php
/**
 * 多协程读大文件，统计单词出现次数
 */

namespace app\controllers;


use co;
use Co\Channel;
use Swoole\Coroutine\Barrier;

class file
{
    public function index()
    {
        set_time_limit(0);
        echo date("Y-m-d H:i:s") . "\n";
        $path = STATIC_PATH . '/uploads/test.txt';
        $file_length = $this->file_length($path);
        $co_num = 1000;
        $pre_length = ceil($file_length / $co_num);
        $barrier = Barrier::make();
        $channel = new Channel(1000);
        for ($i = 0; $i < $co_num; $i++) {
            $begin = $i * $pre_length;
            $end = $begin + $pre_length - 1;
            go(function () use ($path, $begin, $end, $channel) {
                $file = fopen($path, 'r');
                fseek($file, $begin);
                while (fseek($file, -1, SEEK_CUR) === 0) { //退格到本行行首
                    if (fread($file, 1) == PHP_EOL || ftell($file) <= 0) {
                        break;
                    }
                    fseek($file, -1, SEEK_CUR);     //因为fread会向前移一格光标，所以要再退一格
                }
//                echo '协程:' . Co::getCid() . '，移动完毕， 当前光标在'. ftell($file) . PHP_EOL;
                while (!feof($file) && ftell($file) <= $end) {
                    $row = fgets($file);
                    if (ftell($file) > $end) {    //本行结尾大于结束位置，放弃本行
                        break;
                    }
                    $row_arr = explode(' ', $row);
                    foreach ($row_arr as $word) {
                        $word = trim($word, "\.\r\n\t;,()");
                        $word_arr = explode('	', $word);
                        foreach ($word_arr as $item) {
                            $item = strtolower($item);
                            $channel->push($item);
                        }
                    }
                }
                fclose($file);
            });
        }
        $count = [];
        for ($i = 0; $i < 10; $i++) {
            go(function () use (&$count, $channel, $barrier) {
                $cid = Co::getCid();
                while (true) {
                    $word = $channel->pop(2.0);
                    if ($word) {
                        $count[$cid][$word] = isset($count[$cid][$word]) ? $count[$cid][$word] + 1 : 1;
                    } elseif ($channel->errCode == SWOOLE_CHANNEL_TIMEOUT) {
                        break;
                    }
                }
            });
        }
        Barrier::wait($barrier);
        $result = [];
        foreach ($count as $item) {
            foreach ($item as $word => $num) {
                $result[$word] = isset($result[$word]) ? $result[$word] + $num : $num;
            }
        }
        arsort($result, 1);
        echo date("Y-m-d H:i:s") . "\n";
        response('<pre>' . print_r($result, true) . '</pre>');
    }

    public function file_length($file_path)
    {
        $file = fopen($file_path, 'r');
        fseek($file, 0, SEEK_END);
        $length = ftell($file);
        fclose($file);
        return $length;
    }
}