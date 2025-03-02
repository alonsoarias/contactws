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

$string['pluginname'] = 'SARH Web Services Authentication';
$string['auth_contactwsdescription'] = 'Users can login using the SARH Web Services API';
$string['baseurl'] = 'API Base URL';
$string['baseurl_desc'] = 'Base URL for the SARH Web Services API';
$string['apiusername'] = 'API Username';
$string['apiusername_desc'] = 'Username for SARH API authentication';
$string['apipassword'] = 'API Password';
$string['apipassword_desc'] = 'Password for SARH API authentication';
$string['auth_contactwssettings'] = 'SARH Authentication Settings';
$string['errorauthtoken'] = 'Error getting authentication token';
$string['errorauthapi'] = 'Error connecting to SARH API';
$string['errorauthresponse'] = 'Invalid response from SARH API';
$string['errorauthcreateaccount'] = 'Cannot create new accounts';
$string['notwhileloggedinas'] = 'Cannot manage linked logins while logged in as another user';
$string['alreadylinked'] = 'This external account is already linked to a Moodle account';
$string['privacy:metadata'] = 'The SARH Web Services authentication plugin does not store any personal data';

// Task related strings
$string['task_sync_users'] = 'SARH User Synchronization';
$string['task_notify_admins'] = 'SARH Admin Notification';
$string['task_settings_heading'] = 'Notification Settings';
$string['task_settings_heading_desc'] = 'Configure email notifications to stay informed about user synchronization.';
$string['enable_admin_notifications'] = 'Enable admin notifications';
$string['enable_admin_notifications_desc'] = 'Send email notifications to selected administrators with user statistics.';
$string['notification_admin_ids'] = 'Notify administrators';
$string['notification_admin_ids_desc'] = 'Select which administrators should receive notifications.';
$string['sync_info_heading'] = 'Synchronization Status';
$string['last_sync_info'] = 'Last synchronization: {$a}';
$string['last_sync_never'] = 'No synchronization has been performed yet.';
$string['sync_execution_time'] = 'Last execution time: {$a} seconds';
$string['runsyncnow'] = 'Run Synchronization Now';
$string['runsyncfirst'] = 'Run First Synchronization';
$string['user_stats_heading'] = 'User Statistics';
$string['active_users_info'] = 'Active SARH users: {$a}';
$string['suspended_users_info'] = 'Suspended SARH users: {$a}';
$string['total_api_users_info'] = 'Total users from SARH API: {$a}';
$string['missing_users_info'] = 'Users in SARH API but not in Moodle: {$a}';
$string['processed_users_info'] = 'Processed users from API: {$a}';
$string['unprocessed_users_info'] = 'Unprocessed users from API (time limit reached): {$a}';

// Status statistics
$string['status_stats_heading'] = 'Users by Status';
$string['status_name'] = 'Status Name';
$string['status_count'] = 'Count';
$string['status_missing'] = 'Missing';
$string['status_percentage'] = 'Percentage';
$string['status_other'] = 'Other Statuses';

// Missing users
$string['missing_users_heading'] = 'Missing Users';
$string['missing_users_intro'] = 'The following users are active in SARH but not in Moodle ({$a} total):';
$string['missing_user_docnumber'] = 'Document Number';
$string['missing_user_status'] = 'Status';
$string['missing_user_count'] = 'Count';
$string['missing_users_more'] = 'And {$a} more...';

// Notification message strings
$string['notification_subject'] = 'SARH User Status Report';
$string['notification_smallmessage'] = 'SARH User Status Report is available';
$string['notification_title'] = 'SARH User Status Report for {$a}';
$string['notification_intro'] = 'This is an automated report about the current state of SARH users in your Moodle system.';
$string['notification_stats_heading'] = 'User Statistics';
$string['notification_active_users'] = 'Active SARH users in Moodle: {$a}';
$string['notification_suspended_users'] = 'Suspended SARH users in Moodle: {$a}';
$string['notification_last_sync'] = 'Last synchronization: {$a}';
$string['notification_execution_time'] = 'Execution time: {$a} seconds';
$string['notification_total_api_users'] = 'Total users from SARH API: {$a}';
$string['notification_total_missing_users'] = 'Users in SARH API but not in Moodle: {$a}';
$string['notification_processed_users'] = 'Processed users from API: {$a}';
$string['notification_unprocessed_users'] = 'Unprocessed users from API (time limit reached): {$a}';
$string['notification_unprocessed_warning'] = 'Warning: Time limit reached during synchronization!';

// Status statistics in notification
$string['notification_status_heading'] = 'Users by Status in SARH API';
$string['notification_status_name'] = 'Status Name';
$string['notification_status_count'] = 'Count';
$string['notification_status_missing'] = 'Missing in Moodle';

// Missing users in notification
$string['notification_missing_by_status_heading'] = 'Missing Users by Status';
$string['notification_missing_status'] = 'Status';
$string['notification_missing_count'] = 'Count';
$string['notification_missing_heading'] = 'Users missing in Moodle';
$string['notification_missing_intro'] = 'The following users are active in SARH but do not exist in Moodle:';
$string['notification_missing_docnumber'] = 'Document Number';
$string['notification_no_missing_heading'] = 'No Missing Users';
$string['notification_no_missing_text'] = 'All active SARH users exist in Moodle.';
$string['notification_footer'] = 'This is an automated message from {$a}. Please do not reply to this email.';
$string['never'] = 'Never';