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

/**
 * Admin settings and defaults
 *
 * @package auth_manual
 * @copyright  2017 Stephen Bourget
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {
    if ($hassiteconfig) {
        $settings = new admin_settingpage('auth_contactws_settings', new lang_string('pluginname', 'auth_contactws'));

        $ADMIN->add('authsettings', $settings);

        $settings->add(new admin_setting_configtext(
            'auth_contactws/service',
            get_string('auth_contactws_service', 'auth_contactws'),
            '',
            '',
            PARAM_URL
        ));

        $settings->add(new admin_setting_configtext(
            'auth_contactws/userparam',
            get_string('auth_contactws_userparam', 'auth_contactws'),
            '',
            '',
            PARAM_TEXT
        ));

        $settings->add(new admin_setting_configtext(
            'auth_contactws/pswdparam',
            get_string('auth_contactws_pswdparam', 'auth_contactws'),
            '',
            '',
            PARAM_TEXT
        ));

        $settings->add(new admin_setting_configtext(
            'auth_contactws/addparam',
            get_string('auth_contactws_addparam', 'auth_contactws'),
            '',
            '',
            PARAM_TEXT
        ));

        $settings->add(new admin_setting_configtext(
            'auth_contactws/changepasswordurl',
            get_string('auth_contactws_changepasswordurl', 'auth_contactws'),
            '',
            '',
            PARAM_URL
        ));
    }
}
