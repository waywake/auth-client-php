<?php

namespace PdAuth;

use PdAuth\Middleware\Authenticate;

class OAuth
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
     * 选择配置 应对同一项目引入多个pdauth
     * @param $config
     */
    public function choose($config)
    {
        $this->host = $config['host'];
        $this->id = $config['appid'];
        $this->secret = $config['secret'];
        return $this;
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

    public function getAccessToken($code)
    {
        $resp = $this->get("$this->host/api/access_token?id={$this->id}&secret={$this->secret}&code={$code}");
        if ($resp['code'] == 0) {
            return $resp['data'];
        }
        return null;
    }

    /**
     * 根据用户token获取用户信息
     * @param $token
     * @return null
     */
    public function getUserInfo($token)
    {
        $token = urlencode($token);
        $resp = $this->get("$this->host/api/user/info?access_token=$token");
        if ($resp['code'] == 0) {
            return $resp['data'];
        }
        return null;
    }

    /**
     * 获取用户组
     * @param null $token
     * @return null
     */
    public function getGroupUsers($token = null)
    {
        if ($token == null) {
            $token = $_COOKIE[Authenticate::CookieName];
        }
        $token = urlencode($token);
        $resp = $this->get("$this->host/api/group/users?access_token=$token");
        if ($resp['code'] == 0) {
            return $resp['data'];
        }
        return null;
    }


    /**
     * @绑定好物平台用户
     *
     * @param int $user_id
     * @param int $hwuser_id
     * @return null
     */
    public function bindHwUser(int $user_id, int $hwuser_id)
    {
        if ($user_id <= 0 || $hwuser_id <= 0) {
            return null;
        }
        $resp = $this->post("$this->host/api/bind/hwuser", [
            'id' => $user_id,
            'hwmc_id' => $hwuser_id,
        ]);
        return $resp;
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

    /**
     * 对 pd auth 系统发起post请求
     *
     * @param $url
     * @param array $params
     * @param array $headers
     * @return null
     */
    protected function post($url, $params = [], $headers = [])
    {
        $client = new \GuzzleHttp\Client();
        $res = $client->request('POST', $url, [
            'headers' => $headers,
            'form_params' => $params,
        ]);
        if ($res->getStatusCode() == 200) {
            return \GuzzleHttp\json_decode($res->getBody(), true);
        } else {
            return null;
        }
    }
}