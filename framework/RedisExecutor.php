<?php

namespace framework;

use framework\exception\FileNotFoundException;
use framework\exception\LoadConfigException;
use framework\log\Log;
use framework\log\LogLevel;
use framework\util\FormatUtil;
use Redis;
use RedisException;

class RedisExecutor {
    private Redis $redis;
    public function __construct(int $db = 0){

        $config_file = AppEnv::$database_config_file;
        if (!file_exists($config_file)){
            throw new FileNotFoundException($config_file);
        }

        $json = file_get_contents($config_file);
        $redis_config = json_decode($json)->redis;
        if (!isset($redis_config)) {
            throw new LoadConfigException("无法解析配置文件 \"{$config_file}\"");
        }

        $err_msg = "数据库配置文件 \"{$config_file}\" 配置缺失";
        $host = $redis_config->host ?? throw new LoadConfigException($err_msg);
        $port = $redis_config->port ?? throw new LoadConfigException($err_msg);
        $password = $redis_config->password ?? throw new LoadConfigException($err_msg);

        $this->redis = new Redis();
        try {
            $this->redis->connect($host, $port);
            $this->redis->auth($password);
            $this->redis->select($db);
        } catch (RedisException $e) {
            $this->log_redis_exception($e);
        }
    }

    private function log_redis_exception($e): void {
        Log::log(LogLevel::ERROR ,$e->getMessage());
        Log::multiline($e->getTrace(), foreach_handler: function ($index, $item) {
            return FormatUtil::trace_line($index, $item);
        });
    }

    public function select(int $db): bool {
        try {
            return $this->redis->select($db);
        } catch (RedisException $e) {
            $this->log_redis_exception($e);
            return false;
        }
    }

    public function set($key, string $value, mixed $timeout = null): bool {
        try {
            return $this->redis->set($key, $value, $timeout);
        } catch (RedisException $e) {
            $this->log_redis_exception($e);
            return false;
        }
    }

    public function get($key) {
        try {
            return $this->redis->get($key);
        } catch (RedisException $e) {
            $this->log_redis_exception($e);
            return false;
        }
    }

    public function keys(string $partten): array|false {
        try {
            return $this->redis->keys($partten);
        } catch (RedisException $e) {
            $this->log_redis_exception($e);
            return false;
        }
    }

}

