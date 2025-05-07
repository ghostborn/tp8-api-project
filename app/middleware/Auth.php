<?php

namespace app\middleware;

use think\facade\Request;
use think\facade\Config;
use Firebase\JWT\JWT;
use Firebase\JWT\key;
use think\facade\Db;
use think\Response;

class Auth
{
    public function handle($request, \Closure $next)
    {
        $token = $request->header('Authorization');
        if (!$token) {
            return json(['code' => 401, 'message' => '未提供令牌']);
        }
        $token = str_replace('Bearer ', '', $token);
        try {
            $key = Config::get('jwt.key');
            $decoded = JWT::decode($token, new Key($key, 'HS256'));

            $user = Db::name('users')->where('id', $decoded->data->user_id)->find();
            if (!$user || $user['last_token'] !== $token) {
                return json(['code' => 401, 'message' => '无效的令牌']);
            }

            $request->user = (array)$decoded->data;
            return $next($request);
        } catch (\Exception $e) {
            return json(['code' => 401, 'message' => '无效的令牌']);
        }
    }
}
