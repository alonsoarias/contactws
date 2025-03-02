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

require_once($CFG->libdir . '/authlib.php');
require_once($CFG->dirroot . '/user/profile/lib.php');
require_once($CFG->dirroot . '/user/lib.php');

/**
 * ContactWS authentication plugin.
 *
 * @package    auth_contactws
 * @copyright  2025 Pedro Arias <soporte@ingeweb.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU Public License
 */
class auth extends \auth_plugin_base
{

    /**
     * @var stdClass $userinfo The set of user info returned from the SARH API
     */
    private static $userinfo;

    /**
     * Constructor.
     */
    public function __construct()
    {
        debugging('[auth_contactws][auth] Inicializando plugin de autenticación ContactWS', DEBUG_DEVELOPER);
        $this->authtype = 'contactws';
        $this->config = get_config('auth_contactws');
        debugging('[auth_contactws][auth] Configuración cargada: ' . print_r($this->config, true), DEBUG_DEVELOPER);
    }

    /**
     * Get token from SARH API.
     *
     * @return string|null The token if successful, null otherwise
     */
    private function get_sarh_token()
    {
        debugging('[auth_contactws][auth] Obteniendo token de SARH API', DEBUG_DEVELOPER);

        $curl = curl_init();
        $url = rtrim($this->config->baseurl, '/') . '/login';
        debugging('[auth_contactws][auth] URL de autenticación: ' . $url, DEBUG_DEVELOPER);

        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
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

        if (curl_errno($curl)) {
            debugging('[auth_contactws][auth] Error CURL: ' . curl_error($curl), DEBUG_DEVELOPER);
        }

        curl_close($curl);

        if ($httpcode === 200) {
            $data = json_decode($response, true);
            $token = $data['Token'] ?? null;
            debugging('[auth_contactws][auth] Token ' . ($token ? 'obtenido' : 'no encontrado'), DEBUG_DEVELOPER);
            return $token;
        }

        debugging('[auth_contactws][auth] No se pudo obtener token', DEBUG_DEVELOPER);
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
    public function user_login($username, $password)
    {
        global $DB;

        // Forzar username a minúsculas
        $username = core_text::strtolower($username);

        debugging('[auth_contactws][auth] Iniciando proceso de login para usuario: ' . $username, DEBUG_DEVELOPER);

        // Forzar siempre consulta al API para obtener datos actualizados
        $token = $this->get_sarh_token();
        if (!$token) {
            debugging('[auth_contactws][auth] Error: No se pudo obtener token', DEBUG_DEVELOPER);
            return false;
        }

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => rtrim($this->config->baseurl, '/') . '/usuario',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode([
                'Username' => $username, // Ya está en minúsculas
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
                $userdata = $data['Datos'][0];

                // Forzar transformaciones de texto en los datos recibidos
                $userdata['Usuario'] = core_text::strtolower($userdata['Usuario']);
                $userdata['Email'] = core_text::strtolower($userdata['Email']);
                $userdata['NombreCompleto'] = core_text::strtoupper($userdata['NombreCompleto']);
                $userdata['ApellidoCompleto'] = core_text::strtoupper($userdata['ApellidoCompleto']);

                debugging('[auth_contactws][auth] Autenticación exitosa, guardando datos en caché', DEBUG_DEVELOPER);
                $this->set_static_user_info($userdata);

                // Actualizar datos del usuario si ya existe
                $user = $DB->get_record('user', ['username' => $username, 'deleted' => 0]);
                if ($user) {
                    debugging('[auth_contactws][auth] Usuario existente, actualizando datos', DEBUG_DEVELOPER);
                    $this->update_existing_user($user, $userdata);
                }

                return true;
            }
        }
        debugging('[auth_contactws][auth] Fallo en autenticación', DEBUG_DEVELOPER);
        return false;
    }

    /**
     * Update existing user data
     *
     * @param stdClass $user Current user record
     * @param array $userdata New user data from API
     */
    private function update_existing_user($user, $userdata)
    {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/user/profile/lib.php');
        require_once($CFG->dirroot . '/user/lib.php');

        debugging('[auth_contactws][auth] Iniciando actualización de usuario existente', DEBUG_DEVELOPER);

        try {
            // Mapear campos
            $mappings = new user_field_mapping();
            $mapped_data = $mappings->map_fields($userdata);
            debugging('[auth_contactws][auth] Datos mapeados: ' . print_r($mapped_data, true), DEBUG_DEVELOPER);

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
            $updateuser->id = $user->id;
            $needs_update = false;

            foreach ($standard_fields as $field => $value) {
                if (property_exists($user, $field) && $user->$field !== $value) {
                    $updateuser->$field = $value;
                    $needs_update = true;
                    debugging("[auth_contactws][auth] Campo estándar '$field' requiere actualización: De '{$user->$field}' a '$value'", DEBUG_DEVELOPER);
                }
            }

            if ($needs_update) {
                debugging('[auth_contactws][auth] Actualizando campos estándar', DEBUG_DEVELOPER);
                user_update_user($updateuser, false, true);
            }

            // Actualizar campos personalizados
            if (!empty($profile_fields)) {
                // Cargar datos actuales del perfil
                profile_load_data($user);

                $profile_user = new stdClass();
                $profile_user->id = $user->id;
                foreach ($profile_fields as $field => $value) {
                    $profile_user->$field = $value;
                    debugging("[auth_contactws][auth] Campo personalizado preparado - $field: $value", DEBUG_DEVELOPER);
                }

                if (profile_save_data($profile_user)) {
                    debugging('[auth_contactws][auth] Campos personalizados actualizados exitosamente', DEBUG_DEVELOPER);
                } else {
                    debugging('[auth_contactws][auth] Error al actualizar campos personalizados', DEBUG_DEVELOPER);
                }
            }
        } catch (\Exception $e) {
            debugging('[auth_contactws][auth] Error actualizando usuario: ' . $e->getMessage(), DEBUG_DEVELOPER);
        }
    }

    /**
     * Complete the login process after verification.
     *
     * @param string $username The username
     * @param string $password The password
     * @param string $redirecturl Redirect URL after login
     */
    public function complete_login($username, $password, $redirecturl)
    {
        global $CFG, $SESSION, $DB;

        // Forzar username a minúsculas
        $username = core_text::strtolower($username);

        debugging('[auth_contactws][auth] Iniciando proceso de login completo para: ' . $username, DEBUG_DEVELOPER);

        // Obtener información del usuario (ya está en caché o del API)
        $userinfo = $this->get_static_user_info();
        if (empty($userinfo)) {
            debugging('[auth_contactws][auth] No hay información en caché, consultando API', DEBUG_DEVELOPER);

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
                    'Username' => $username, // Ya está en minúsculas
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

            // Forzar transformaciones de texto en los datos recibidos
            $userinfo['Usuario'] = core_text::strtolower($userinfo['Usuario']);
            $userinfo['Email'] = core_text::strtolower($userinfo['Email']);
            $userinfo['NombreCompleto'] = core_text::strtoupper($userinfo['NombreCompleto']);
            $userinfo['ApellidoCompleto'] = core_text::strtoupper($userinfo['ApellidoCompleto']);

            $this->set_static_user_info($userinfo);
        }

        debugging('[auth_contactws][auth] Información de usuario disponible: ' . print_r($userinfo, true), DEBUG_DEVELOPER);

        // Mapear campos
        $mappings = new user_field_mapping();
        $mapped_data = $mappings->map_fields($userinfo);
        debugging('[auth_contactws][auth] Datos mapeados: ' . print_r($mapped_data, true), DEBUG_DEVELOPER);

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

        // Verificar si el usuario existe
        $user = $DB->get_record('user', ['username' => $username]);

        if (!$user) {
            debugging('[auth_contactws][auth] Usuario no existe, creando nuevo usuario', DEBUG_DEVELOPER);
            if (!empty($CFG->authpreventaccountcreation)) {
                throw new moodle_exception('errorauthcreateaccount', 'auth_contactws');
            }

            // Crear usuario base con campos estándar
            $user = (object)$standard_fields;
            $user->auth = 'contactws';
            $user->mnethostid = $CFG->mnet_localhost_id;
            $user->confirmed = 1;
            $user->timecreated = time();
            $user->timemodified = time();

            $user->id = user_create_user($user, false, true);
            debugging('[auth_contactws][auth] Usuario base creado con ID: ' . $user->id, DEBUG_DEVELOPER);

            // Procesar campos personalizados para nuevo usuario
            if (!empty($profile_fields)) {
                $profile_user = new stdClass();
                $profile_user->id = $user->id;
                foreach ($profile_fields as $field => $value) {
                    $profile_user->$field = $value;
                    debugging("[auth_contactws][auth] Campo personalizado preparado - $field: $value", DEBUG_DEVELOPER);
                }

                if (profile_save_data($profile_user)) {
                    debugging('[auth_contactws][auth] Campos personalizados guardados exitosamente', DEBUG_DEVELOPER);
                } else {
                    debugging('[auth_contactws][auth] Error al guardar campos personalizados', DEBUG_DEVELOPER);
                }
            }
        } else {
            debugging('[auth_contactws][auth] Usuario existe, actualizando información', DEBUG_DEVELOPER);

            // Actualizar campos estándar
            $updateuser = new stdClass();
            $updateuser->id = $user->id;
            $needs_update = false;

            foreach ($standard_fields as $field => $value) {
                if (property_exists($user, $field) && $user->$field !== $value) {
                    $updateuser->$field = $value;
                    $needs_update = true;
                    debugging("[auth_contactws][auth] Campo estándar '$field' requiere actualización: '$value'", DEBUG_DEVELOPER);
                }
            }

            if ($needs_update) {
                user_update_user($updateuser, false, true);
                debugging('[auth_contactws][auth] Campos estándar actualizados', DEBUG_DEVELOPER);
            }

            // Actualizar campos personalizados
            if (!empty($profile_fields)) {
                // Cargar datos actuales del perfil
                profile_load_data($user);

                $profile_user = new stdClass();
                $profile_user->id = $user->id;
                foreach ($profile_fields as $field => $value) {
                    $profile_user->$field = $value;
                    debugging("[auth_contactws][auth] Campo personalizado preparado - $field: $value", DEBUG_DEVELOPER);
                }

                if (profile_save_data($profile_user)) {
                    debugging('[auth_contactws][auth] Campos personalizados actualizados exitosamente', DEBUG_DEVELOPER);
                } else {
                    debugging('[auth_contactws][auth] Error al actualizar campos personalizados', DEBUG_DEVELOPER);
                }
            }
        }

        // Recargar usuario completo
        $user = get_complete_user_data('id', $user->id);

        debugging('[auth_contactws][auth] Login completado exitosamente', DEBUG_DEVELOPER);
        complete_user_login($user);
        redirect($redirecturl);
    }

    /**
     * Returns the user information for 'external' users.
     *
     * @param string $username username
     * @return mixed array with no magic quotes or false on error
     */
    public function get_userinfo($username)
    {
        debugging('[auth_contactws][auth] Obteniendo información de usuario para: ' . $username, DEBUG_DEVELOPER);

        $cached = $this->get_static_user_info();
        if (!empty($cached) && $cached['Usuario'] == $username) {
            debugging('[auth_contactws][auth] Información encontrada en caché', DEBUG_DEVELOPER);
            $mappings = new user_field_mapping();
            return $mappings->map_fields($cached);
        }

        debugging('[auth_contactws][auth] No se encontró información del usuario', DEBUG_DEVELOPER);
        return false;
    }

    /**
     * We don't want to allow users setting an internal password.
     *
     * @return bool
     */
    public function prevent_local_passwords()
    {
        return true;
    }

    /**
     * Returns true if this authentication plugin is 'internal'.
     *
     * @return bool
     */
    public function is_internal()
    {
        return false;
    }

    /**
     * Indicates if moodle should automatically update internal user
     * records with data from external sources using the information
     * from auth_plugin_base::get_userinfo().
     *
     * @return bool true means automatically copy data from ext to user table
     */
    public function is_synchronised_with_external()
    {
        return true;
    }

    /**
     * Returns true if this authentication plugin can change the user's
     * password.
     *
     * @return bool
     */
    public function can_change_password()
    {
        return false;
    }

    /**
     * Returns true if plugin allows resetting of internal password.
     *
     * @return bool
     */
    public function can_reset_password()
    {
        return false;
    }

    /**
     * Returns true if plugin can be manually set.
     *
     * @return bool
     */
    public function can_be_manually_set()
    {
        return true;
    }

    /**
     * Statically cache the user info
     * @param stdClass $userinfo
     */
    private function set_static_user_info($userinfo)
    {
        debugging('[auth_contactws][auth] Guardando información de usuario en caché: ' . print_r($userinfo, true), DEBUG_DEVELOPER);
        self::$userinfo = $userinfo;
    }

    /**
     * Get the static cached user info
     * @return stdClass
     */
    private function get_static_user_info()
    {
        $info = self::$userinfo;
        debugging('[auth_contactws][auth] Recuperando información de usuario desde caché: ' .
            ($info ? print_r($info, true) : 'no hay datos'), DEBUG_DEVELOPER);
        return $info;
    }

    /**
     * Validates if all required profile fields exist
     * @return array ['success' => bool, 'missing' => array]
     */
    private function validate_profile_fields()
    {
        global $DB;

        debugging('[auth_contactws][auth] Validando campos de perfil requeridos', DEBUG_DEVELOPER);

        $required_fields = [
            'NombreCampana',
            'NombreCentro',
            'Cargo',
            'JefeInmediato',
            'FechaContrato'
        ];

        $missing_fields = [];
        foreach ($required_fields as $fieldname) {
            $exists = $DB->record_exists('user_info_field', ['shortname' => $fieldname]);
            if (!$exists) {
                debugging("[auth_contactws][auth] Campo de perfil no encontrado: $fieldname", DEBUG_DEVELOPER);
                $missing_fields[] = $fieldname;
            }
        }

        $success = empty($missing_fields);
        debugging('[auth_contactws][auth] Validación de campos completada. Éxito: ' .
            ($success ? 'true' : 'false'), DEBUG_DEVELOPER);

        return [
            'success' => $success,
            'missing' => $missing_fields
        ];
    }

    /**
     * Process profile fields for a user
     * @param stdClass $user User object
     * @param array $profile_fields Profile fields data
     * @return bool Success status
     */
    private function process_profile_fields($user, $profile_fields)
    {
        if (empty($profile_fields)) {
            return true;
        }

        debugging('[auth_contactws][auth] Procesando campos de perfil para usuario ' . $user->id, DEBUG_DEVELOPER);

        // Validar que los campos existan
        $validation = $this->validate_profile_fields();
        if (!$validation['success']) {
            debugging('[auth_contactws][auth] Algunos campos de perfil requeridos no existen: ' .
                implode(', ', $validation['missing']), DEBUG_DEVELOPER);
            return false;
        }

        // Crear objeto para campos de perfil
        $profile_user = new stdClass();
        $profile_user->id = $user->id;

        foreach ($profile_fields as $field => $value) {
            $profile_user->$field = $value;
            debugging("[auth_contactws][auth] Campo de perfil preparado - $field: $value", DEBUG_DEVELOPER);
        }

        // Guardar datos del perfil
        if (profile_save_data($profile_user)) {
            debugging('[auth_contactws][auth] Campos de perfil guardados exitosamente', DEBUG_DEVELOPER);
            return true;
        } else {
            debugging('[auth_contactws][auth] Error al guardar campos de perfil', DEBUG_DEVELOPER);
            return false;
        }
    }

    /**
     * Process standard fields for a user
     * @param stdClass $user User object
     * @param array $standard_fields Standard fields data
     * @return bool Success status
     */
    private function process_standard_fields($user, $standard_fields)
    {
        $updateuser = new stdClass();
        $updateuser->id = $user->id;
        $needs_update = false;

        foreach ($standard_fields as $field => $value) {
            if (property_exists($user, $field) && $user->$field !== $value) {
                $updateuser->$field = $value;
                $needs_update = true;
                debugging("[auth_contactws][auth] Campo estándar requiere actualización - $field: $value", DEBUG_DEVELOPER);
            }
        }

        if ($needs_update) {
            return user_update_user($updateuser, false, true);
        }

        return true;
    }
}
