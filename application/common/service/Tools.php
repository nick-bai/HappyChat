<?php
/**
 * Created by PhpStorm.
 * User: nickbai
 * Date: 19-6-21
 * Time: 下午3:36
 */
namespace app\common\service;

class Tools
{
    public static function casData($key, $data, $expire = 0)
    {
        $semId = ftok(__FILE__, 's');
        $signal = sem_get($semId);

        // 获取信号量
        sem_acquire($signal);

        $data = cache($key);
        cache($key, $data, $expire);

        // 释放信号量
        sem_release($signal);
    }

    public static function delData($key, $dataKey, $expire = 0)
    {
        $semId = ftok(__FILE__, 's');
        $signal = sem_get($semId);

        // 获取信号量
        sem_acquire($signal);

        $data = cache($key);
        if (isset($data[$dataKey])) {
            unset($data[$dataKey]);
        }

        cache($key, $data, $expire);

        // 释放信号量
        sem_release($signal);
    }
}