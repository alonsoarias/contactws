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
 * Plugin strings are defined here.
 *
 * @package     auth_contactws
 * @category    string
 * @copyright   2024 Soporte IngeWeb <soporte@ingeweb.co>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
$string['pluginname'] = 'Contact Web Service Authentication';
$string['auth_contactws_service'] = 'Service URL';
$string['auth_contactws_service_desc'] = 'The URL of the web service used for authentication.';
$string['auth_contactws_userparam'] = 'User Parameter';
$string['auth_contactws_userparam_desc'] = 'The parameter name for the user in the web service request.';
$string['auth_contactws_pswdparam'] = 'Password Parameter';
$string['auth_contactws_pswdparam_desc'] = 'The parameter name for the password in the web service request.';
$string['auth_contactws_addparam'] = 'Additional Parameter';
$string['auth_contactws_addparam_desc'] = 'An additional parameter for the web service request.';
$string['auth_contactws_changepasswordurl'] = 'Change Password URL';
$string['auth_contactws_changepasswordurl_desc'] = 'The URL where users can change their password.';
$string['auth_contactws_description'] = 'This plugin allows users to authenticate using a web service.';
$string['auth_fieldlocks'] = 'Field Locks';
$string['auth_fieldlocks_help'] = 'Configuration of field locks for this authentication.';
