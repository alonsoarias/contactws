<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Authentication class for contactws is defined here.
 *
 * @package     auth_contactws
 * @copyright   2024 Soporte IngeWeb <soporte@ingeweb.co>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/authlib.php');
require_once($CFG->dirroot . '/user/lib.php');

/**
 * Authentication class for contactws.
 */
class auth_plugin_contactws extends auth_plugin_base {

    /**
     * Set the properties of the instance.
     */
    public function __construct() {
        $this->authtype = 'contactws';
        $this->config = get_config('auth/contactws');
        
        // Campos del perfil del usuario que se desean actualizar
        $fields_to_update = array('firstname', 'lastname', 'idnumber', 'phone1', 'address');
        foreach ($fields_to_update as $field) {
            $this->config->{'field_updatelocal_' . $field} = 'onlogin';
        }
        
        // Campos del perfil del usuario que se desean bloquear
        $fields_to_lock = array('firstname', 'lastname', 'idnumber', 'phone1', 'address');
        foreach ($fields_to_lock as $field) {
            $this->config->{'field_lock_' . $field} = 'onlogin';
        }
    }

    /**
     * Returns true if the username and password work and false if they are
     * wrong or don't exist.
     *
     * @param string $username The username.
     * @param string $password The password.
     * @return bool Authentication success or failure.
     */
    public function user_login($username, $password) {
        $service = $this->config->service;
        $userparam = $this->config->userparam;
        $pswdparam = $this->config->pswdparam;
        $addparam = $this->config->addparam;

        if ($res = curl_post($service, $userparam . "=" . urlencode($username) . "&" . $pswdparam . "=" . urlencode($password) . "&" . $addparam . "=true")) {
            $result_xml = simplexml_load_string($res);
            $is_valid_user = $result_xml[0];
        }

        return ($is_valid_user == "true");
    }

    /**
     * Prevent local passwords.
     *
     * @return bool
     */
    public function prevent_local_passwords() {
        return true;
    }

    /**
     * Retrieve user information from an external source.
     *
     * @param string $username The username.
     * @return array User information.
     */
    public function get_userinfo($username) {
        global $DB;
        $userinfo = array();

        $servicio = "http://educcamvirtual.com/pwsservice/servicioeduccam.php";
        if ($res = curl_post($servicio, "Usuario=" . mb_convert_encoding($username, 'ISO-8859-1', 'UTF-8') . "&Contrasena=edusarh*$2&op=gud")) {
            $user = simplexml_load_string($res, 'SimpleXMLElement', LIBXML_NOCDATA);
            $user = json_decode(json_encode($user), TRUE);

            if (!empty($user)) {
                $userinfo['firstname'] = $user['usuario']['nombres'];
                $userinfo['lastname'] = $user['usuario']['apellidos'];
                //$userinfo['email'] = $user['usuario']['email'];
                $userinfo['idnumber'] = $user['usuario']['numero_cedula'];
                $userinfo['phone1'] = $user['usuario']['phone1'];
                $userinfo['address'] = $user['usuario']['adress'];
            }

            // Quitar valores vacíos
            foreach ($userinfo as $key => $value) {
                if (!$value) {
                    unset($userinfo[$key]);
                }
            }

            $estado = (array_key_exists('usuario', $user) && array_key_exists('estado', $user['usuario'])) ? (int)$user['usuario']['estado'] : -1;
            $suspendido = ($estado == 0) ? 1 : 0;

            if ($estado == -1) {
                $usuario_actual = array();
            } else {
                $sql = "SELECT u.*
                        FROM {user} u
                        WHERE u.auth = :auth
                        AND lower(u.username) = lower(:username)";
                $usuario_actual = $DB->get_records_sql($sql, array('auth' => $this->authtype, 'username' => $username));
            }

            foreach ($usuario_actual as $usr) {
                if ($usr->suspended == 0 && $suspendido == 1) {
                    $updateuser = new stdClass();
                    $updateuser->id = $usr->id;
                    $updateuser->suspended = $suspendido;
                    //user_update_user($updateuser, false);
                    $suspendido = 0;
                }

                if ($suspendido == 1) {
                    \core\session\manager::kill_user_sessions($usr->id);
                }
            }
        }
        return $userinfo;
    }

    /**
     * Indicates if the plugin is internal.
     *
     * @return bool
     */
    public function is_internal() {
        return false;
    }

    /**
     * Indicates if the plugin can change the password.
     *
     * @return bool
     */
    public function can_change_password() {
        return !empty($this->config->changepasswordurl);
    }

    /**
     * Returns the URL for changing the password.
     *
     * @return moodle_url
     */
    public function change_password_url() {
        return new moodle_url($this->config->changepasswordurl);
    }

    /**
     * Prints a form for configuring this authentication plugin.
     *
     * @param object $config
     * @param object $err
     * @param array $userfields
     */
    public function config_form($config, $err, $userfields) {
        include "config.html";
    }

    /**
     * Processes and stores configuration data for the plugin.
     *
     * @param stdClass $config Object with submitted configuration settings (without system magic quotes).
     * @return bool True if the configuration was processed successfully.
     */
    public function process_config($config) {
        if (!isset($config->service)) {
            $config->service = '127.0.0.1';
        }
        if (!isset($config->userparam)) {
            $config->userparam = 'Usuario';
        }
        if (!isset($config->pswdparam)) {
            $config->pswdparam = 'Contrasena';
        }
        if (!isset($config->addparam)) {
            $config->addparam = 'true';
        }
        if (!isset($config->changepasswordurl)) {
            $config->changepasswordurl = '#';
        }

        set_config('service', $config->service, 'auth/contactws');
        set_config('userparam', $config->userparam, 'auth/contactws');
        set_config('pswdparam', $config->pswdparam, 'auth/contactws');
        set_config('addparam', $config->addparam, 'auth/contactws');
        set_config('changepasswordurl', $config->changepasswordurl, 'auth/contactws');

        return true;
    }
}

/**
 * Function to perform a cURL POST request.
 *
 * @param string $url The URL to post to.
 * @param string $post The post data.
 * @param array $options Additional cURL options.
 * @return string The result of the POST request.
 */
function curl_post($url, $post = NULL, array $options = array()) {
    $defaults = array(
        CURLOPT_POST => 1,
        CURLOPT_HEADER => 0,
        CURLOPT_URL => $url,
        CURLOPT_FRESH_CONNECT => 1,
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_FORBID_REUSE => 1,
        CURLOPT_TIMEOUT => 14,
        CURLOPT_POSTFIELDS => $post
    );

    $ch = curl_init();
    curl_setopt_array($ch, ($options + $defaults));
    if (!$result = curl_exec($ch)) {
        echo curl_error($ch);
        trigger_error(curl_error($ch));
    }
    curl_close($ch);
    return $result;
}
