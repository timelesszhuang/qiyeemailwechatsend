<?php


/**
 * Memcache 操作类
 *
 * 在config文件中 添加
  相应配置(可扩展为多memcache server)
  define('MEMCACHE_HOST', '10.35.52.33');
  define('MEMCACHE_PORT', 11211);
  define('MEMCACHE_EXPIRATION', 0);
  define('MEMCACHE_PREFIX', '');
  define('MEMCACHE_COMPRESSION', FALSE);
  demo:
  $cacheObj -> set('keyName','this is value');
  $cacheObj -> get('keyName');
  exit;
 * @access  public
 * @return  object
 * @date   2012-07-02
 */
class Memcachemanage {

    private $local_cache = array();
    private $m;
    private $exists;
    protected $errors = array();
    protected $expire;
    protected $memcache_prefix;

    public function __construct($host, $port, $expire, $memcache_prefix) {
        $this->exists = class_exists('Memcache') ? true : false;
        if ($this->exists) {
            // 判断引入类型
            $this->m = new Memcache();
            $this->expire = $expire;
            $this->memcache_prefix = $memcache_prefix;
            $this->auto_connect($host, $port);
        } else {
            echo 'ERROR: Failed to load Memcached or Memcache Class (∩_∩)';
            exit;
        }
    }

    /**
     * @Name: auto_connect
     * @param $host
     * @param $port
     * @internal param $ :none
     * @todu 连接memcache server
     */
    private function auto_connect($host, $port) {
        $configServer = array(
            'host' => $host,
            'port' => $port,
            'weight' => 1,
        );
        if (!$this->add_server($configServer)) {
            echo 'ERROR: Could not connect to the server named ' . $host;
        }
    }

    /**
     * @Name: add_server
     * @param:none
     * @return bool : TRUE or FALSE
     * @todu 连接memcache server
     */
    public function add_server($server) {
        extract($server);
        return $this->m->addServer($host, $port, true, $weight);
    }

    /**
     * @Name: add_server
     * @todu 添加
     * @param null $key
     * @param null $value
     * @param int $expiration
     * @return array|bool : TRUE or FALSE
     * @internal param $ :$key key
     * @internal param $ :$value 值
     * @internal param $ :$expiration 过期时间可以单独设置
     */
    public function add($key = NULL, $value = NULL, $expiration = 0) {
        if (is_null($expiration)) {
            $expiration = $this->expire;
        }
        $this->local_cache[$this->key_name($key)] = $value;
        $add_status = $this->m->add($this->key_name($key), $value, MEMCACHE_COMPRESSED, $expiration);
        return $add_status;
    }

    /**
     * @Name   与add类似,但服务器有此键值时仍可写入替换
     * @param  $key key
     * @param  $value 值
     * @param  $expiration 过期时间
     * @return TRUE or FALSE
     * */
    public function set($key = NULL, $value = NULL, $expiration = NULL) {
        if (is_null($expiration)) {
            $expiration = $this->expire;
        }
        $this->local_cache[$this->key_name($key)] = $value;
        $set_status = $this->m->set($this->key_name($key), $value, MEMCACHE_COMPRESSED, $expiration);
        return $set_status;
    }

    /**
     * @Name   get 根据键名获取值
     * @param  $key key
     * @return array OR json object OR string
     * */
    public function get($key = NULL) {
        if ($this->m) {
            if (isset($this->local_cache[$this->key_name($key)])) {
                return $this->local_cache[$this->key_name($key)];
            }
            if (is_null($key)) {
                $this->errors[] = 'The key value cannot be NULL';
                return FALSE;
            }
            return $this->m->get($this->key_name($key));
        } else {
            return FALSE;
        }
    }

    /**
     * @Name   delete
     * @param  $key key
     * @param  $expiration 服务端等待删除该元素的总时间
     * @return true OR false
     * */
    public function delete($key, $expiration = NULL) {
        if (is_null($key)) {
            $this->errors[] = 'The key value cannot be NULL';
            return FALSE;
        }
        if (is_null($expiration)) {
            $expiration = $this->expire;
        }
        unset($this->local_cache[$this->key_name($key)]);
        return $this->m->delete($this->key_name($key), $expiration);
    }

    /**
     * @Name   replace
     * @param  $key 要替换的key
     * @param  $value 要替换的value
     * @param  $expiration 到期时间
     * @return none
     * */
    public function replace($key = NULL, $value = NULL, $expiration = NULL) {
        if (is_null($expiration)) {
            $expiration = $this->expire;
        }
        $this->local_cache[$this->key_name($key)] = $value;
        $replace_status = $this->m->replace($this->key_name($key), $value, MEMCACHE_COMPRESSION, $expiration);
        return $replace_status;
    }

    /**
     * @Name   replace 清空所有缓存
     * @return none
     * add by cheng.yafei
     * */
    public function flush() {
        return $this->m->flush();
    }

    /**
     * @Name   获取服务器池中所有服务器的版本信息
     * */
    public function getversion() {
        return $this->m->getVersion();
    }

    /**
     * @Name   获取服务器池的统计信息
     * @param string $type
     * @return array|bool
     */
    public function getstats($type = "items") {
        $stats = $this->m->getStats($type);
        return $stats;
    }

    /**
     * @Name: 开启大值自动压缩
     * @param:$tresh 控制多大值进行自动压缩的阈值。
     * @param float $savings
     * @return bool : true OR false
     */
    public function setcompressthreshold($tresh, $savings = 0.2) {
        $setcompressthreshold_status = $this->m->setCompressThreshold($tresh, $savings = 0.2);
        return $setcompressthreshold_status;
    }

    /**
     * @Name: 生成md5加密后的唯一键值
     * @param:$key key
     * @return string : md5 string
     */
    private function key_name($key) {
        return md5(strtolower($this->memcache_prefix . $key));
    }

}

?>
