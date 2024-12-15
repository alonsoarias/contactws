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

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {
    // Base URL setting
    $settings->add(new admin_setting_configtext(
        'auth_contactws/baseurl',
        get_string('baseurl', 'auth_contactws'),
        get_string('baseurl_desc', 'auth_contactws'),
        'https://webdes.americasbps.com/ApiSarh/api',
        PARAM_URL
    ));

    // API Username setting
    $settings->add(new admin_setting_configtext(
        'auth_contactws/apiusername',
        get_string('apiusername', 'auth_contactws'),
        get_string('apiusername_desc', 'auth_contactws'),
        '',
        PARAM_TEXT
    ));

    // API Password setting
    $settings->add(new admin_setting_configpasswordunmask(
        'auth_contactws/apipassword',
        get_string('apipassword', 'auth_contactws'),
        get_string('apipassword_desc', 'auth_contactws'),
        ''
    ));
}