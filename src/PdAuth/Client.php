<?php

namespace PdAuth;

class Client
{

    protected $host;
    protected $id;
    protected $secret;

    public function __construct($config)
    {
        $this->host = $config['host'];
        $this->id = $config['appid'];
        $this->secret = $config['secret'];
    }

    /**
     * 生成授权的链接
     * @param $redirect
     * @return string
     */
    public function connect($redirect)
    {
        $redirect = urlencode($redirect);
        return $this->host . "/connect?appid={$this->id}&redirect=$redirect";
    }

    /**
     * 根据用户token获取用户信息
     * @param $username
     * @param $token
     * @return array|null
     */
    public function getUserInfo($username, $token)
    {
        $token = urlencode($token);
        $resp = $this->get("$this->host/api/{$username}/info?access_token=$token");
        if( $resp['code'] == 0 ){
            return $resp['data'];
        }
        return null;
    }

    /**
     * 对 pd auth 系统发起请求
     * @param $url
     * @return mixed|null
     */
    protected function get($url)
    {
        $client = new \GuzzleHttp\Client();
        $res = $client->request('GET', $url);

        if ($res->getStatusCode() == 200) {
            return \GuzzleHttp\json_decode($res->getBody(), true);
        } else {
            return null;
        }
    }

}