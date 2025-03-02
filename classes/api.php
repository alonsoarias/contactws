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
 * @copyright  2025 Pedro Arias <soporte@ingeweb.co>
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
        debugging('[auth_contactws][api] Iniciando limpieza de logins vinculados para usuario: ' . ($userid ? $userid : 'todos'), DEBUG_DEVELOPER);
        $result = linked_login::delete_user_logins($userid);
        debugging('[auth_contactws][api] Limpieza de logins completada. Resultado: ' . ($result ? 'éxito' : 'fallo'), DEBUG_DEVELOPER);
        return $result;
    }

    /**
     * List linked logins
     *
     * @param int $userid (defaults to $USER->id)
     * @return boolean
     */
    public static function get_linked_logins($userid = false) {
        global $USER;
        debugging('[auth_contactws][api] Obteniendo logins vinculados para usuario: ' . ($userid ? $userid : $USER->id), DEBUG_DEVELOPER);

        if ($userid === false) {
            $userid = $USER->id;
        }

        if (\core\session\manager::is_loggedinas()) {
            debugging('[auth_contactws][api] Error: Intento de obtener logins mientras está logueado como otro usuario', DEBUG_DEVELOPER);
            throw new moodle_exception('notwhileloggedinas', 'auth_contactws');
        }

        $context = context_user::instance($userid);
        require_capability('auth/contactws:managelinkedlogins', $context);

        $result = linked_login::get_records(['userid' => $userid, 'confirmtoken' => '']);
        debugging('[auth_contactws][api] Logins vinculados encontrados: ' . count($result), DEBUG_DEVELOPER);
        return $result;
    }

    /**
     * Match username to user.
     *
     * @param string $username The username
     * @return stdClass|false User record if found
     */
    public static function match_username_to_user($username) {
        debugging('[auth_contactws][api] Buscando coincidencia para username: ' . $username, DEBUG_DEVELOPER);
        
        $params = ['username' => $username];
        $result = linked_login::get_record($params);

        if ($result) {
            $user = \core_user::get_user($result->get('userid'));
            if (!empty($user) && !$user->deleted) {
                debugging('[auth_contactws][api] Usuario encontrado con ID: ' . $user->id, DEBUG_DEVELOPER);
                return $result;
            }
        }
        
        debugging('[auth_contactws][api] No se encontró coincidencia para username: ' . $username, DEBUG_DEVELOPER);
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
        debugging('[auth_contactws][api] Iniciando vinculación de login para: ' . $userinfo['Usuario'], DEBUG_DEVELOPER);

        if ($userid === false) {
            $userid = $USER->id;
        }

        if (linked_login::has_existing_match($userinfo['Usuario'])) {
            debugging('[auth_contactws][api] Error: Login ya vinculado para username: ' . $userinfo['Usuario'], DEBUG_DEVELOPER);
            throw new moodle_exception('alreadylinked', 'auth_contactws');
        }

        if (\core\session\manager::is_loggedinas()) {
            debugging('[auth_contactws][api] Error: Intento de vincular mientras está logueado como otro usuario', DEBUG_DEVELOPER);
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

        debugging('[auth_contactws][api] Creando registro de login vinculado', DEBUG_DEVELOPER);
        $linkedlogin = new linked_login(0, $record);
        $result = $linkedlogin->create();

        debugging('[auth_contactws][api] Vinculación completada. Resultado: ' . ($result ? 'éxito' : 'fallo'), DEBUG_DEVELOPER);
        return $result;
    }

    /**
     * Create a new confirmed account with all fields populated.
     *
     * @param array $userinfo User info from SARH
     * @return stdClass The created user
     */
    public static function create_new_confirmed_account($userinfo) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/user/profile/lib.php');
        require_once($CFG->dirroot.'/user/lib.php');

        debugging('[auth_contactws][api] Iniciando creación de cuenta para: ' . $userinfo['Usuario'], DEBUG_DEVELOPER);

        // Mapear campos
        $mappings = new user_field_mapping();
        $mapped_data = $mappings->map_fields($userinfo);
        debugging('[auth_contactws][api] Datos mapeados: ' . print_r($mapped_data, true), DEBUG_DEVELOPER);

        // Separar campos estándar y personalizados
        $standard_fields = [];
        $profile_fields = [];
        foreach ($mapped_data as $field => $value) {
            if (strpos($field, 'profile_field_') === 0) {
                $profile_fields[$field] = $value;
            } else {
                $standard_fields[$field] = $value;
            }
        }

        // Crear el usuario base
        $user = (object)$standard_fields;
        $user->auth = 'contactws';
        $user->mnethostid = $CFG->mnet_localhost_id;
        $user->confirmed = 1;
        $user->timecreated = time();
        $user->timemodified = time();

        debugging('[auth_contactws][api] Creando registro de usuario', DEBUG_DEVELOPER);
        $user->id = user_create_user($user, false, true);

        if (!$user->id) {
            debugging('[auth_contactws][api] Error al crear usuario', DEBUG_DEVELOPER);
            throw new moodle_exception('errorcreatinguserrecord', 'auth_contactws');
        }

        // Crear objeto para campos personalizados
        if (!empty($profile_fields)) {
            $profile_user = new stdClass();
            $profile_user->id = $user->id;
            foreach ($profile_fields as $field => $value) {
                $profile_user->$field = $value;
                debugging("[auth_contactws][api] Campo personalizado preparado - $field: $value", DEBUG_DEVELOPER);
            }

            // Guardar campos personalizados
            if (profile_save_data($profile_user)) {
                debugging('[auth_contactws][api] Campos personalizados guardados exitosamente', DEBUG_DEVELOPER);
            } else {
                debugging('[auth_contactws][api] Error al guardar campos personalizados', DEBUG_DEVELOPER);
            }
        }

        // Vincular login
        self::link_login($userinfo, $user->id, true);

        debugging('[auth_contactws][api] Usuario creado exitosamente con ID: ' . $user->id, DEBUG_DEVELOPER);
        return get_complete_user_data('id', $user->id);
    }

    /**
     * Update user fields including both standard and custom fields
     *
     * @param int $userid User ID
     * @param array $userinfo User information
     * @return bool Success status
     */
    public static function update_user_fields($userid, $userinfo) {
        global $DB, $CFG;
        require_once($CFG->dirroot.'/user/profile/lib.php');
        require_once($CFG->dirroot.'/user/lib.php');

        debugging('[auth_contactws][api] Iniciando actualización de campos para usuario ID: ' . $userid, DEBUG_DEVELOPER);

        // Obtener usuario actual
        $user = $DB->get_record('user', ['id' => $userid]);
        if (!$user) {
            debugging('[auth_contactws][api] Error: Usuario no encontrado', DEBUG_DEVELOPER);
            return false;
        }

        // Mapear campos
        $mappings = new user_field_mapping();
        $mapped_data = $mappings->map_fields($userinfo);
        debugging('[auth_contactws][api] Datos mapeados: ' . print_r($mapped_data, true), DEBUG_DEVELOPER);

        // Separar campos estándar y personalizados
        $standard_fields = [];
        $profile_fields = [];
        foreach ($mapped_data as $field => $value) {
            if (strpos($field, 'profile_field_') === 0) {
                $profile_fields[$field] = $value;
            } else {
                $standard_fields[$field] = $value;
            }
        }

        // Actualizar campos estándar
        $updateuser = new stdClass();
        $updateuser->id = $userid;
        $needs_update = false;

        foreach ($standard_fields as $field => $value) {
            if (property_exists($user, $field) && $user->$field !== $value) {
                $updateuser->$field = $value;
                $needs_update = true;
                debugging("[auth_contactws][api] Campo estándar '$field' requiere actualización de '{$user->$field}' a '$value'", DEBUG_DEVELOPER);
            }
        }

        if ($needs_update) {
            debugging('[auth_contactws][api] Actualizando campos estándar', DEBUG_DEVELOPER);
            user_update_user($updateuser, false, true);
        }

        // Actualizar campos personalizados
        if (!empty($profile_fields)) {
            // Cargar datos actuales del perfil
            profile_load_data($user);

            $profile_user = new stdClass();
            $profile_user->id = $userid;
            foreach ($profile_fields as $field => $value) {
                $profile_user->$field = $value;
                debugging("[auth_contactws][api] Campo personalizado preparado - $field: $value", DEBUG_DEVELOPER);
            }

            // Guardar campos personalizados
            if (profile_save_data($profile_user)) {
                debugging('[auth_contactws][api] Campos personalizados actualizados exitosamente', DEBUG_DEVELOPER);
            } else {
                debugging('[auth_contactws][api] Error al actualizar campos personalizados', DEBUG_DEVELOPER);
                return false;
            }
        }

        debugging('[auth_contactws][api] Actualización de campos completada', DEBUG_DEVELOPER);
        return true;
    }

    /**
     * Check if auth_contactws is enabled.
     *
     * @return bool
     */
    public static function is_enabled() {
        debugging('[auth_contactws][api] Verificando si auth_contactws está habilitado', DEBUG_DEVELOPER);
        $result = is_enabled_auth('contactws');
        debugging('[auth_contactws][api] auth_contactws está ' . ($result ? 'habilitado' : 'deshabilitado'), DEBUG_DEVELOPER);
        return $result;
    }
}