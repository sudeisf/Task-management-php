<?php

require_once __DIR__ . "/Session.php";

class Auth
{
    public static function login($user)
    {
        Session::start();
        Session::set("user_id", $user['id']);
        Session::set("user_name", $user['full_name']); 
        Session::set("user_role", $user['role_name'] ?? 'member');
        Session::set("user_avatar", $user['avatar'] ?? null);
    }

    public static function logout()
    {
        Session::start();
        Session::destroy();
    }

    public static function check()
    {
        Session::start();
        return Session::get("user_id") !== null;
    }

    public static function user()
    {
        Session::start();
        return [
            "id" => Session::get("user_id"),
            "name" => Session::get("user_name"),
            "role" => Session::get("user_role"),
            "avatar" => Session::get("user_avatar")
        ];
    }

    public static function getUserRole()
{
    if (!isset($_SESSION['user'])) {
        return null;
    }

    return $_SESSION['user']['role'] ?? null;
}
}
