<?php

namespace app\controller;

use think\facade\Db;
use think\facade\Request;
use think\facade\Config;
use Firebase\JWT\JWT;
use Firebase\JWT\key;

class User
{
    public function register()
    {
        $data = Request::post();
        if (empty($data['username']) || empty($data['password'])) {
            return json(['code' => 400, 'message' => '用户名和密码不能为空']);
        }
        $userExists = Db::name('users')->where('username', $data['username'])->find();
        if ($userExists) {
            return json(['code' => 400, 'message' => '用户名已存在']);
        }
        $userId = Db::name('users')->insertGetId([
            'username' => $data['username'],
            'password' => password_hash($data['password'], PASSWORD_DEFAULT),
            'last_token' => null
        ]);
        if ($userId) {
            return json(['code' => 200, 'message' => '注册成功', 'data' => ['user_id' => $userId]]);
        } else {
            return json(['code' => 500, 'message' => '注册失败']);
        }
    }

    public function login()
    {
        $data = Request::post();
        $user = Db::name('users')->where('username', $data['username'])->find();
        if (!$user || !password_verify($data['password'], $user['password'])) {
            return json(['code' => 401, 'message' => '用户名或密码错误']);
        }
        $key = Config::get('jwt.key');
        $payload = [
            'iss' => 'http://example.org',
            'aud' => 'http://example.com',
            'iat' => time(),
            'nbf' => time(),
            'exp' => time() + 3600,
            'data' => [
                'user_id' => $user['id'],
                'username' => $user['username']
            ]
        ];
        $token = JWT::encode($payload, $key, 'HS256');

        // 检查 $user 是否包含 'id' 字段
        if (isset($user['id'])) {
            try {
                // 更新数据库中的 last_token
                Db::name('users')->where('id', $user['id'])->update(['last_token' => $token]);
            } catch (\Exception $e) {
                return json(['code' => 500, 'message' => '更新 token 时发生错误: ' . $e->getMessage()]);
            }
        } else {
            return json(['code' => 500, 'message' => '用户 ID 未找到，无法更新 token']);
        }

        return json(['code' => 200, 'message' => '登录成功', 'data' => ['token' => $token]]);

    }

    public function getUserInfo()
    {
        $user = request()->user;
        if (!$user) {
            return json(['cose' => 401, 'message' => '未授权的访问']);
        }
        return json(['code' => 200, 'message' => '获取成功', 'data' => $user]);
    }
}