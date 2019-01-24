<?php

namespace PdAuth;

use JsonRpc\Client;
use PdAuth\Middleware\Authenticate;

class Auth
{

    protected $config;
    protected $host;
    protected $id;
    protected $secret;

    /**
     * @var Client
     */
    protected $rpc;

    public function __construct($config)
    {
        $this->config = $config;
        $this->configure();
    }

    /**
     * @throws \Exception
     */
    protected function configure()
    {
        switch (env('APP_ENV')) {
            case 'local':
            case 'develop':
                $this->host = 'http://auth.lo.haowumc.com';
                break;
            case 'production':
                $this->host = 'https://auth.int.haowumc.com';
                break;
            default:
                throw new \Exception('"APP_ENV" is not defined or not allow');
        }

        //为了公司内部调用的统一，更换协议为 JSON RPC
        if (function_exists('app')) {
            $this->rpc = app('rpc.auth');
        } else {
            $this->rpc = new Client([
                'client' => [
                    'auth' => [
                        'local' => true,
                        'base_uri' => env('RPC_AUTH_URI'),
                    ],
                ],
            ]);
        }
        $this->choose();
    }

    public function choose($name = null)
    {
        if (!$name) {
            $name = env('APP_NAME');
        }
        switch ($name) {
            case 'erp':
            case 'erp_api':
                $this->id = $this->config['apps']['erp']['id'];
                $this->secret = $this->config['apps']['erp']['secret'];
                break;
        }
        return $this;
    }

    /**
     * 生成web授权的链接
     * @param $redirect
     * @return string
     */
    public function connect($redirect)
    {
        $id = $this->id;
        $redirect = urlencode($redirect);
        return "{$this->host}/connect?appid={$id}&redirect=$redirect";
    }

    /**
     * @param $code
     * @return array
     * @throws \JsonRpc\Exception\RpcServerException
     */
    public function getAccessToken($code)
    {
        $token = $this->rpc->call('oauth.access_token', [$this->id, $this->secret, $code]);
        return $token;
    }

    /**
     * 根据用户token获取用户信息
     * @param $token
     * @return array|null
     * @throws \JsonRpc\Exception\RpcServerException
     */
    public function getUserInfo($token)
    {
        $info = $this->rpc->call('oauth.user_info', [$this->id, $this->secret, $token]);
        return $info;
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
}