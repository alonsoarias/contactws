<?php
/**
 * Installation script for auth_contactws.
 *
 * This file is part of Moodle - http://moodle.org/
 *
 * Moodle is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Moodle is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Moodle.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @package    auth_contactws
 * @category   auth
 * @copyright  2024 Soporte IngeWeb <soporte@ingeweb.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Custom installation function for the auth_contactws plugin.
 *
 * This function is called during the plugin installation process.
 * It performs custom actions like cleaning up password fields in the user table
 * for users where 'auth' is set to 'contactws'.
 *
 * @global stdClass $CFG Global configuration object.
 * @global moodle_database $DB Global database object.
 */
function xmldb_auth_contactws_install() {
    global $CFG, $DB;

    // Remove cached passwords, as they are not needed for this plugin.
    // It's a security practice to not store passwords in a cache.
    $DB->set_field('user', 'password', 'not cached', ['auth' => 'contactws']);
}
