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

use context_user;
use stdClass;
use moodle_exception;
use moodle_url;

defined('MOODLE_INTERNAL') || die();

/**
 * API class for auth contactws configuration.
 *
 * @package    auth_contactws
 * @copyright  2024 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class api {

    /**
     * Remove all linked logins.
     *
     * @param int $userid The user id
     * @return boolean
     */
    public static function clean_linked_logins($userid = false) {
        return linked_login::delete_user_logins($userid);
    }

    /**
     * List linked logins
     *
     * Requires auth/contactws:managelinkedlogins capability at the user context.
     *
     * @param int $userid (defaults to $USER->id)
     * @return boolean
     */
    public static function get_linked_logins($userid = false) {
        global $USER;

        if ($userid === false) {
            $userid = $USER->id;
        }

        if (\core\session\manager::is_loggedinas()) {
            throw new moodle_exception('notwhileloggedinas', 'auth_contactws');
        }

        $context = context_user::instance($userid);
        require_capability('auth/contactws:managelinkedlogins', $context);

        return linked_login::get_records(['userid' => $userid, 'confirmtoken' => '']);
    }

    /**
     * Match username to user.
     *
     * @param string $username The username
     * @return stdClass User record if found
     */
    public static function match_username_to_user($username) {
        $params = [
            'username' => $username
        ];
        $result = linked_login::get_record($params);

        if ($result) {
            $user = \core_user::get_user($result->get('userid'));
            if (!empty($user) && !$user->deleted) {
                return $result;
            }
        }
        return false;
    }

    /**
     * Link a login to this account.
     *
     * @param array $userinfo User info from SARH
     * @param int $userid User ID
     * @param bool $skippermissions Skip permission checks
     * @return bool
     */
    public static function link_login($userinfo, $userid = false, $skippermissions = false) {
        global $USER;

        if ($userid === false) {
            $userid = $USER->id;
        }

        if (linked_login::has_existing_match($userinfo['Usuario'])) {
            throw new moodle_exception('alreadylinked', 'auth_contactws');
        }

        if (\core\session\manager::is_loggedinas()) {
            throw new moodle_exception('notwhileloggedinas', 'auth_contactws');
        }

        $context = context_user::instance($userid);
        if (!$skippermissions) {
            require_capability('auth/contactws:managelinkedlogins', $context);
        }

        $record = new stdClass();
        $record->username = $userinfo['Usuario'];
        $record->userid = $userid;
        $record->email = $userinfo['Email'];
        $record->confirmtoken = '';
        $record->confirmtokenexpires = 0;
        $linkedlogin = new linked_login(0, $record);
        return $linkedlogin->create();
    }

    /**
     * Create a new confirmed account.
     *
     * @param array $userinfo User info from SARH
     * @return stdClass The created user
     */
    public static function create_new_confirmed_account($userinfo) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/user/profile/lib.php');
        require_once($CFG->dirroot.'/user/lib.php');

        // Crear el usuario base
        $user = new stdClass();
        $user->auth = 'contactws';
        $user->mnethostid = $CFG->mnet_localhost_id;
        $user->confirmed = 1;
        
        // Asignar campos estándar de Moodle
        $user->username = $userinfo['Usuario'];
        $user->firstname = $userinfo['NombreCompleto'];
        $user->lastname = $userinfo['ApellidoCompleto'];
        $user->email = $userinfo['Email'];
        $user->idnumber = $userinfo['NumeroDocumento'];

        // Crear el usuario
        $user->id = user_create_user($user, false, true);

        // Preparar datos para campos personalizados
        $customfields = new stdClass();
        $customfields->id = $user->id;
        $customfields->profile_field_nombrecampana = $userinfo['NombreCampana'];
        $customfields->profile_field_nombrecentro = $userinfo['NombreCentro'];
        $customfields->profile_field_cargo = $userinfo['Cargo'];
        $customfields->profile_field_jefeinmediato = $userinfo['JefeInmediato'];
        $customfields->profile_field_fechacontrato = $userinfo['FechaContrato'];

        // Guardar campos personalizados
        profile_save_data($customfields);

        // Link the login
        self::link_login($userinfo, $user->id, true);

        return get_complete_user_data('id', $user->id);
    }

    /**
     * Check if auth_contactws is enabled.
     *
     * @return bool
     */
    public static function is_enabled() {
        return is_enabled_auth('contactws');
    }
}