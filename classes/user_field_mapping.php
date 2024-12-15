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

/**
 * User field mapping for SARH API.
 */
class user_field_mapping {

    /**
     * Return the mapping between SARH fields and Moodle fields
     *
     * @return array
     */
    public function get_field_mappings() {
        return [
            'username' => 'Usuario',
            'firstname' => 'NombreCompleto',
            'lastname' => 'ApellidoCompleto',
            'email' => 'Email',
            'idnumber' => 'NumeroDocumento',
            'profile_field_nombrecampana' => 'NombreCampana',
            'profile_field_nombrecentro' => 'NombreCentro',
            'profile_field_cargo' => 'Cargo',
            'profile_field_jefeinmediato' => 'JefeInmediato',
            'profile_field_fechacontrato' => 'FechaContrato'
        ];
    }

    /**
     * Map SARH fields to Moodle fields
     *
     * @param array $userinfo SARH user data
     * @return array Mapped Moodle user data
     */
    public function map_fields($userinfo) {
        $mappings = $this->get_field_mappings();
        $result = [];
        
        foreach ($mappings as $moodleField => $sarhField) {
            if (isset($userinfo[$sarhField])) {
                // Manejo especial para campos nulos
                if ($userinfo[$sarhField] === null) {
                    $result[$moodleField] = '';
                } else {
                    $result[$moodleField] = $userinfo[$sarhField];
                }
            }
        }
        
        return $result;
    }
}