<?php
namespace Services;

class PasswordService {
    public function hash($password) {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    public function verify($password, $hash) {
        if (password_verify($password, $hash)) {
            return true;
        }
        if (strlen($hash) === 32 && ctype_xdigit($hash)) {
            return md5($password) === $hash;
        }
        return false;
    }
}
