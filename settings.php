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
 * Plugin administration pages are defined here.
 *
 * @package     auth_contactws
 * @category    admin
 * @copyright   2024 Soporte IngeWeb <soporte@ingeweb.co>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
if ($ADMIN->fulltree) {
    if ($hassiteconfig) {
        $settings = new admin_settingpage('auth_contactws_settings', new lang_string('pluginname', 'auth_contactws'));

        if ($ADMIN->fulltree) {

            // Service URL setting
            $settings->add(new admin_setting_configtext(
                'auth_contactws/service',
                get_string('auth_contactws_service', 'auth_contactws'),
                get_string('auth_contactws_service_desc', 'auth_contactws'),
                '127.0.0.1',
                PARAM_URL
            ));

            // User parameter setting
            $settings->add(new admin_setting_configtext(
                'auth_contactws/userparam',
                get_string('auth_contactws_userparam', 'auth_contactws'),
                get_string('auth_contactws_userparam_desc', 'auth_contactws'),
                'Username',
                PARAM_TEXT
            ));

            // Password parameter setting
            $settings->add(new admin_setting_configtext(
                'auth_contactws/pswdparam',
                get_string('auth_contactws_pswdparam', 'auth_contactws'),
                get_string('auth_contactws_pswdparam_desc', 'auth_contactws'),
                'Contrasena',
                PARAM_TEXT
            ));

            // Additional parameter setting
            $settings->add(new admin_setting_configtext(
                'auth_contactws/addparam',
                get_string('auth_contactws_addparam', 'auth_contactws'),
                get_string('auth_contactws_addparam_desc', 'auth_contactws'),
                'true',
                PARAM_TEXT
            ));

            // Change password URL setting
            $settings->add(new admin_setting_configtext(
                'auth_contactws/changepasswordurl',
                get_string('auth_contactws_changepasswordurl', 'auth_contactws'),
                get_string('auth_contactws_changepasswordurl_desc', 'auth_contactws'),
                'http://url.com',
                PARAM_URL
            ));

            // Lock/Unlock settings for user fields
            $settings->add(new admin_setting_heading(
                'auth_contactws/fieldlocks',
                get_string('auth_fieldlocks', 'auth'),
                get_string('auth_fieldlocks_help', 'auth')
            ));

            // Display locking / mapping of profile fields.
            $authplugin = get_auth_plugin('contactws');
            display_auth_lock_options(
                $settings,
                $authplugin->authtype,
                $authplugin->userfields,
                get_string('auth_fieldlocks_help', 'auth'),
                false,
                false
            );
        }
    }
}
