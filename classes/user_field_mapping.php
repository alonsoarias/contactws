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
use core_text;
/**
 * User field mapping class.
 *
 * @package    auth_contactws
 * @copyright  2025 Pedro Arias <soporte@ingeweb.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class user_field_mapping {
    /** @var array Array of custom profile fields */
    private $profile_fields;

    /**
     * Constructor
     */
    public function __construct() {
        global $CFG;
        require_once($CFG->dirroot . '/user/profile/lib.php');
        // Obtener los campos de perfil disponibles
        $this->profile_fields = profile_get_user_fields_with_data(0);
        debugging('[auth_contactws][mapping] Campos de perfil cargados: ' . count($this->profile_fields), DEBUG_DEVELOPER);
    }

    /**
     * Get field mappings between Moodle and external system.
     *
     * @return array Field mapping array ['moodle_field' => 'external_field']
     */
    public function get_field_mappings() {
        debugging('[auth_contactws][mapping] Obteniendo mapeo de campos', DEBUG_DEVELOPER);
        
        // Mapeo base de campos estándar
        $mappings = [
            'username' => 'Usuario',
            'firstname' => 'NombreCompleto',
            'lastname' => 'ApellidoCompleto',
            'email' => 'Email',
            'idnumber' => 'NumeroDocumento'
        ];

        // Lista de campos personalizados requeridos
        $required_fields = [
            'NombreCampana',
            'NombreCentro',
            'Cargo',
            'JefeInmediato',
            'FechaContrato'
        ];

        // Verificar y agregar campos personalizados existentes
        foreach ($this->profile_fields as $field) {
            $shortname = $field->get_shortname();
            if (in_array($shortname, $required_fields)) {
                $mappings['profile_field_' . $shortname] = $shortname;
                debugging("[auth_contactws][mapping] Campo personalizado encontrado: $shortname", DEBUG_DEVELOPER);
            }
        }

        debugging('[auth_contactws][mapping] Mapeo final: ' . print_r($mappings, true), DEBUG_DEVELOPER);
        return $mappings;
    }

    /**
     * Map user fields based on mapping configuration.
     *
     * @param array $userinfo User data from external system
     * @return array Mapped field values
     */
    public function map_fields($userinfo) {
        debugging('[auth_contactws][mapping] Iniciando mapeo de campos', DEBUG_DEVELOPER);
        debugging('[auth_contactws][mapping] Datos de entrada: ' . print_r($userinfo, true), DEBUG_DEVELOPER);

        $mappings = $this->get_field_mappings();
        $result = [];

        foreach ($mappings as $moodleField => $externalField) {
            // Manejo de valores nulos o no existentes
            $value = isset($userinfo[$externalField]) ? $userinfo[$externalField] : '';
            
            // Si el valor es null, convertirlo a cadena vacía
            if ($value === null) {
                $value = '';
                debugging("[auth_contactws][mapping] Valor nulo encontrado para $moodleField, usando cadena vacía", DEBUG_DEVELOPER);
            }

            // Procesamiento para campos personalizados
            if (strpos($moodleField, 'profile_field_') === 0) {
                $shortname = str_replace('profile_field_', '', $moodleField);
                
                // Buscar el campo en los campos de perfil disponibles
                foreach ($this->profile_fields as $field) {
                    if ($field->get_shortname() === $shortname) {
                        // Procesamiento específico por tipo de campo
                        switch (get_class($field)) {
                            case 'profile_field_datetime':
                                if (!empty($value)) {
                                    try {
                                        $date = new \DateTime($value);
                                        $value = $date->format('Y-m-d');
                                        debugging("[auth_contactws][mapping] Fecha convertida para $moodleField: $value", DEBUG_DEVELOPER);
                                    } catch (\Exception $e) {
                                        debugging("[auth_contactws][mapping] Error al procesar fecha: " . $e->getMessage(), DEBUG_DEVELOPER);
                                        $value = '';
                                    }
                                }
                                break;
                            
                            case 'profile_field_text':
                            case 'profile_field_textarea':
                                // Asegurar que el valor es una cadena y limpiar
                                $value = clean_param((string)$value, PARAM_TEXT);
                                break;
                                
                            case 'profile_field_menu':
                            case 'profile_field_checkbox':
                                // Asegurar que el valor es válido para el campo
                                if (!empty($value)) {
                                    $value = clean_param((string)$value, PARAM_TEXT);
                                }
                                break;
                                
                            default:
                                // Para otros tipos de campos, asegurar que sea string
                                $value = (string)$value;
                        }
                        
                        debugging("[auth_contactws][mapping] Campo personalizado procesado - $moodleField: $value", DEBUG_DEVELOPER);
                        break;
                    }
                }
            } else {
                // Para campos estándar, aplicar transformaciones específicas
                $value = (string)$value;
                
                // Aplicar transformaciones según el campo
                switch ($moodleField) {
                    case 'username':
                        $value = core_text::strtolower($value);
                        break;
                    case 'email':
                        $value = core_text::strtolower($value);
                        break;
                    case 'firstname':
                    case 'lastname':
                        $value = core_text::strtoupper($value);
                        break;
                    // Para campos personalizados que requieran transformación
                    case 'profile_field_NombreCampana':
                    case 'profile_field_NombreCentro':
                    case 'profile_field_Cargo':
                    case 'profile_field_JefeInmediato':
                        $value = core_text::strtoupper($value);
                        break;
                }
            }

            $result[$moodleField] = $value;
            debugging("[auth_contactws][mapping] Campo mapeado - $moodleField: $value", DEBUG_DEVELOPER);
        }

        debugging('[auth_contactws][mapping] Resultado del mapeo: ' . print_r($result, true), DEBUG_DEVELOPER);
        return $result;
    }

    /**
     * Check if a specific profile field exists
     *
     * @param string $shortname The shortname of the field to check
     * @return bool Whether the field exists
     */
    public function field_exists($shortname) {
        foreach ($this->profile_fields as $field) {
            if ($field->get_shortname() === $shortname) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get the available profile fields
     *
     * @return array Array of profile field objects
     */
    public function get_available_profile_fields() {
        return $this->profile_fields;
    }
}