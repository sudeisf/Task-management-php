<?php

require_once __DIR__ . "/Session.php";

class Auth
{
    public static function login($user)
    {
        Session::start();
        Session::set("user_id", $user['id']);
        Session::set("user_name", $user['name']);
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
            "name" => Session::get("user_name")
        ];
    }
}
