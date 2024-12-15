<?php
// This file is part of Moodle - http://moodle.org/
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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace auth_contactws;

defined('MOODLE_INTERNAL') || die();

use pix_icon;
use moodle_url;
use core_text;
use context_system;
use stdClass;
use moodle_exception;

require_once($CFG->libdir.'/authlib.php');

/**
 * ContactWS authentication plugin.
 *
 * @package    auth_contactws
 * @copyright  2024 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU Public License
 */
class auth extends \auth_plugin_base {

    /**
     * @var stdClass $userinfo The set of user info returned from the SARH API
     */
    private static $userinfo;

    /**
     * Constructor.
     */
    public function __construct() {
        $this->authtype = 'contactws';
        $this->config = get_config('auth_contactws');
    }

    /**
     * Get token from SARH API.
     *
     * @return string|null The token if successful, null otherwise
     */
    private function get_sarh_token() {
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => rtrim($this->config->baseurl, '/') . '/login',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode([
                'Username' => $this->config->apiusername,
                'Password' => $this->config->apipassword
            ]),
            CURLOPT_HTTPHEADER => ['Content-Type: application/json']
        ]);

        $response = curl_exec($curl);
        $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if ($httpcode === 200) {
            $data = json_decode($response, true);
            return $data['Token'] ?? null;
        }
        return null;
    }

    /**
     * Returns true if the username and password work or don't exist and false
     * if the user exists and the password is wrong.
     *
     * @param string $username The username
     * @param string $password The password
     * @return bool Authentication success or failure
     */
    public function user_login($username, $password) {
        $cached = $this->get_static_user_info();
        if (empty($cached)) {
            $token = $this->get_sarh_token();
            if (!$token) {
                return false;
            }

            $curl = curl_init();
            curl_setopt_array($curl, [
                CURLOPT_URL => rtrim($this->config->baseurl, '/') . '/usuario',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode([
                    'Username' => $username,
                    'Password' => $password
                ]),
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $token
                ]
            ]);

            $response = curl_exec($curl);
            $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            curl_close($curl);

            if ($httpcode === 200) {
                $data = json_decode($response, true);
                if (isset($data['RespuestaSolicitud']) && $data['RespuestaSolicitud'] === true) {
                    $this->set_static_user_info($data['Datos'][0]);
                    return true;
                }
            }
            return false;
        }
        return ($cached['Usuario'] === $username);
    }

    /**
     * Complete the login process after SARH verification.
     *
     * @param string $username The username
     * @param string $password The password
     * @param string $redirecturl Redirect URL after login
     */
    public function complete_login($username, $password, $redirecturl) {
        global $CFG, $SESSION, $DB;

        $token = $this->get_sarh_token();
        if (!$token) {
            throw new moodle_exception('errorauthtoken', 'auth_contactws');
        }

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => rtrim($this->config->baseurl, '/') . '/usuario',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode([
                'Username' => $username,
                'Password' => $password
            ]),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $token
            ]
        ]);

        $response = curl_exec($curl);
        $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if ($httpcode !== 200) {
            throw new moodle_exception('errorauthapi', 'auth_contactws');
        }

        $data = json_decode($response, true);
        if (!isset($data['RespuestaSolicitud']) || !$data['RespuestaSolicitud']) {
            throw new moodle_exception('errorauthresponse', 'auth_contactws');
        }

        $userinfo = $data['Datos'][0];
        $this->set_static_user_info($userinfo);

        // Verificar/crear usuario
        $user = $DB->get_record('user', ['username' => $username]);
        if (!$user) {
            if (!empty($CFG->authpreventaccountcreation)) {
                throw new moodle_exception('errorauthcreateaccount', 'auth_contactws');
            }
            $user = api::create_new_confirmed_account($userinfo);
        } else {
            // Actualizar información del usuario
            $mappings = new user_field_mapping();
            $mappedFields = $mappings->get_field_mappings();
            $updateuser = new stdClass();
            $updateuser->id = $user->id;

            foreach ($mappedFields as $moodleField => $sarhField) {
                if (isset($userinfo[$sarhField]) && (!isset($user->$moodleField) || $user->$moodleField !== $userinfo[$sarhField])) {
                    $updateuser->$moodleField = $userinfo[$sarhField];
                }
            }

            if (count((array)$updateuser) > 1) {
                user_update_user($updateuser, false, true);
                $user = get_complete_user_data('id', $user->id);
            }
        }

        complete_user_login($user);
        redirect($redirecturl);
    }

    // Continúa con el resto de los métodos requeridos...
/**
     * Returns the user information for 'external' users.
     *
     * @param string $username username
     * @return mixed array with no magic quotes or false on error
     */
    public function get_userinfo($username) {
        $cached = $this->get_static_user_info();
        if (!empty($cached) && $cached['Usuario'] == $username) {
            $mappings = new user_field_mapping();
            return $mappings->map_fields($cached);
        }
        return false;
    }

    /**
     * We don't want to allow users setting an internal password.
     *
     * @return bool
     */
    public function prevent_local_passwords() {
        return true;
    }

    /**
     * Returns true if this authentication plugin is 'internal'.
     *
     * @return bool
     */
    public function is_internal() {
        return false;
    }

    /**
     * Indicates if moodle should automatically update internal user
     * records with data from external sources using the information
     * from auth_plugin_base::get_userinfo().
     *
     * @return bool true means automatically copy data from ext to user table
     */
    public function is_synchronised_with_external() {
        return true;
    }

    /**
     * Returns true if this authentication plugin can change the user's
     * password.
     *
     * @return bool
     */
    public function can_change_password() {
        return false;
    }

    /**
     * Returns true if plugin allows resetting of internal password.
     *
     * @return bool
     */
    public function can_reset_password() {
        return false;
    }

    /**
     * Returns true if plugin can be manually set.
     *
     * @return bool
     */
    public function can_be_manually_set() {
        return true;
    }

    /**
     * Statically cache the user info
     * @param stdClass $userinfo
     */
    private function set_static_user_info($userinfo) {
        self::$userinfo = $userinfo;
    }

    /**
     * Get the static cached user info
     * @return stdClass
     */
    private function get_static_user_info() {
        return self::$userinfo;
    }
}