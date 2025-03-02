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

namespace auth_contactws\task;

defined('MOODLE_INTERNAL') || die();

use core\task\scheduled_task;
use html_writer;
use stdClass;

/**
 * Task to notify admins about SARH user statistics.
 *
 * @package    auth_contactws
 * @copyright  2025 Pedro Arias <soporte@ingeweb.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class notify_admins_task extends scheduled_task {

    /**
     * Get a descriptive name for this task.
     *
     * @return string
     */
    public function get_name() {
        return get_string('task_notify_admins', 'auth_contactws');
    }

    /**
     * Execute the task.
     */
    public function execute() {
        global $CFG, $DB, $SITE;
        
        mtrace('Starting SARH admin notification task...');
        
        // Check if auth_contactws is enabled
        if (!is_enabled_auth('contactws')) {
            mtrace('SARH authentication is disabled. Skipping task.');
            return;
        }
        
        // Get plugin configuration
        $config = get_config('auth_contactws');
        
        // Check if admin notifications are enabled
        if (empty($config->enable_admin_notifications)) {
            mtrace('Admin notifications are disabled. Skipping task.');
            return;
        }
        
        // Get selected admins to notify
        $adminids = [];
        if (!empty($config->notification_admin_ids)) {
            $adminids = explode(',', $config->notification_admin_ids);
        }
        
        if (empty($adminids)) {
            mtrace('No admins selected for notifications. Skipping task.');
            return;
        }
        
        // Collect data for notification
        $activeusers = $config->active_users_count ?? 0;
        $suspendedusers = $config->suspended_users_count ?? 0;
        $lastsynchtime = $config->last_sync_time ?? 0;
        $totalApiUsers = $config->total_api_users ?? 0;
        $totalMissingUsers = $config->total_missing_users ?? 0;
        $totalProcessedUsers = $config->total_processed_users ?? 0;
        $totalUnprocessedUsers = $config->total_unprocessed_users ?? 0;
        $executionTime = $config->sync_execution_time ?? 0;
        
        // Get status statistics
        $statusStats = [];
        if (!empty($config->status_statistics)) {
            $statusStats = json_decode($config->status_statistics, true);
        }
        
        // Prepare notification message
        $subject = get_string('notification_subject', 'auth_contactws');
        $messagehtml = $this->generate_notification_message(
            $activeusers, 
            $suspendedusers, 
            $lastsynchtime, 
            $statusStats,
            $totalApiUsers,
            $totalMissingUsers,
            $totalProcessedUsers,
            $totalUnprocessedUsers,
            $executionTime
        );
        $messagetext = html_to_text($messagehtml);
        
        // Create noreply user for sending emails
        $fromuser = $this->generate_email_user($CFG->noreplyaddress, format_string($CFG->supportname));
        
        // Temporary disable noemailever if it's enabled
        $previous_noemailever = $CFG->noemailever ?? false;
        $CFG->noemailever = false;
        
        // Send notifications to selected admins
        foreach ($adminids as $adminid) {
            $admin = $DB->get_record('user', ['id' => $adminid]);
            if ($admin) {
                mtrace("Sending notification to admin: {$admin->username}");
                
                // Send email directly
                email_to_user($admin, $fromuser, $subject, $messagetext, $messagehtml, '', '', true);
                mtrace("Email sent to {$admin->email}");
            } else {
                mtrace("Admin with ID $adminid not found");
            }
        }
        
        // Restore previous noemailever setting
        $CFG->noemailever = $previous_noemailever;
        
        mtrace('SARH admin notification task completed.');
    }
    
    /**
     * Generate a user info object based on provided parameters.
     *
     * @param string $email Plain text email address.
     * @param string $name Optional plain text real name.
     * @param int $id Optional user ID, default is -99.
     * @return object Returns a user object for email.
     */
    private function generate_email_user($email, $name = '', $id = -99) {
        $emailuser = new stdClass();
        $emailuser->email = trim(filter_var($email, FILTER_SANITIZE_EMAIL));
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $emailuser->email = '';
        }
        
        $name = format_text($name, FORMAT_HTML, array('trusted' => false, 'noclean' => false));
        $emailuser->firstname = trim(filter_var($name, FILTER_SANITIZE_STRING));
        $emailuser->lastname = '';
        $emailuser->maildisplay = true;
        $emailuser->mailformat = 1; // 1 for HTML emails
        $emailuser->id = $id;
        $emailuser->firstnamephonetic = '';
        $emailuser->lastnamephonetic = '';
        $emailuser->middlename = '';
        $emailuser->alternatename = '';
        
        return $emailuser;
    }
    
    /**
     * Generate the notification message with enhanced styling.
     *
     * @param int $activeusers Number of active users
     * @param int $suspendedusers Number of suspended users
     * @param int $lastsynchtime Last synchronization timestamp
     * @param array $statusStats Statistics by user status
     * @param int $totalApiUsers Total number of users in the API
     * @param int $totalMissingUsers Total number of missing users
     * @param int $totalProcessedUsers Total number of processed users
     * @param int $totalUnprocessedUsers Total number of unprocessed users
     * @param float $executionTime Execution time in seconds
     * @return string Notification message in HTML format
     */
    private function generate_notification_message(
        $activeusers, 
        $suspendedusers, 
        $lastsynchtime, 
        $statusStats,
        $totalApiUsers,
        $totalMissingUsers,
        $totalProcessedUsers,
        $totalUnprocessedUsers,
        $executionTime
    ) {
        global $SITE, $CFG;
        
        $sitename = $SITE->fullname;
        $syncdate = ($lastsynchtime > 0) ? userdate($lastsynchtime) : get_string('never', 'auth_contactws');
        $siteurl = $CFG->wwwroot;
        
        // Start the email HTML
        $message = '<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>' . get_string('notification_subject', 'auth_contactws') . '</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; background-color: #f5f5f5; margin: 0; padding: 0;">
    <!-- Main Container -->
    <table border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #f5f5f5;">
        <tr>
            <td align="center" style="padding: 20px;">
                <!-- Email Container -->
                <table border="0" cellpadding="0" cellspacing="0" width="600" style="background-color: #ffffff; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                    <!-- Header -->
                    <tr>
                        <td align="center" style="padding: 25px 30px; background-color: #0f6cbf; border-top-left-radius: 8px; border-top-right-radius: 8px;">
                            <table border="0" cellpadding="0" cellspacing="0" width="100%">
                                <tr>
                                    <td style="text-align: center; padding-top: 15px;">
                                        <h1 style="color: white; margin: 0; font-size: 24px;">' . get_string('notification_title', 'auth_contactws', $sitename) . '</h1>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    
                    <!-- Content -->
                    <tr>
                        <td style="padding: 30px;">
                            <!-- Intro Text -->
                            <table border="0" cellpadding="0" cellspacing="0" width="100%">
                                <tr>
                                    <td style="color: #666666; font-size: 15px; padding-bottom: 20px;">
                                        ' . get_string('notification_intro', 'auth_contactws') . '
                                    </td>
                                </tr>
                            </table>
                            
                            <!-- Sync Info Box -->
                            <table border="0" cellpadding="0" cellspacing="0" width="100%" style="margin-bottom: 20px; background-color: #e8f4ff; border-radius: 6px;">
                                <tr>
                                    <td style="padding: 15px;">
                                        <h2 style="margin: 0 0 10px 0; font-size: 18px; color: #0f6cbf;">
                                            <img src="' . $CFG->wwwroot . '/pix/i/calendar.png" alt="" width="16" height="16" style="vertical-align: middle;"> 
                                            ' . get_string('sync_info_heading', 'auth_contactws') . '
                                        </h2>
                                        <p style="margin: 5px 0; font-weight: bold;">' . get_string('notification_last_sync', 'auth_contactws', $syncdate) . '</p>';
        
        // Add execution time if available
        if ($executionTime > 0) {
            $formattedTime = round($executionTime, 2);
            $timeColor = $formattedTime > 180 ? '#d9534f' : ($formattedTime > 60 ? '#f0ad4e' : '#5cb85c');
            
            $message .= '<p style="margin: 5px 0; color: ' . $timeColor . ';">' .
                        get_string('notification_execution_time', 'auth_contactws', $formattedTime) . '</p>';
        }
        
        $message .= '                </td>
                                </tr>
                            </table>
                            
                            <!-- User Stats Section -->
                            <table border="0" cellpadding="0" cellspacing="0" width="100%" style="margin-bottom: 20px;">
                                <tr>
                                    <td style="padding-bottom: 10px;">
                                        <h2 style="margin: 0; font-size: 18px; color: #333;">
                                            <img src="' . $CFG->wwwroot . '/pix/i/stats.png" alt="" width="16" height="16" style="vertical-align: middle;"> 
                                            ' . get_string('notification_stats_heading', 'auth_contactws') . '
                                        </h2>
                                    </td>
                                </tr>
                                
                                <!-- Stats Split into 2 columns -->
                                <tr>
                                    <td>
                                        <table border="0" cellpadding="0" cellspacing="0" width="100%">
                                            <tr>
                                                <!-- API Users Column -->
                                                <td width="50%" style="vertical-align: top;">
                                                    <table border="0" cellpadding="0" cellspacing="0" width="95%" style="min-height: 160px; background-color: #f9f9f9; border-radius: 6px;">
                                                        <tr>
                                                            <td style="padding: 15px;">
                                                                <h3 style="margin: 0 0 15px 0; color: #0f6cbf; font-size: 16px;">API SARH</h3>
                                                                <p style="margin: 8px 0; font-size: 14px; color: #333; font-weight: bold;">
                                                                    ' . get_string('notification_total_api_users', 'auth_contactws', number_format($totalApiUsers)) . '
                                                                </p>';
        
        // If we have unprocessed users, show that info
        if ($totalUnprocessedUsers > 0) {
            $processedPercent = ($totalProcessedUsers / $totalApiUsers) * 100;
            
            $message .= '<div style="background-color: #e9ecef; height: 20px; border-radius: 4px; margin: 10px 0; overflow: hidden;">
                            <div style="background-color: #5cb85c; height: 100%; width: ' . $processedPercent . '%; text-align: center; color: white; font-size: 12px; line-height: 20px;">
                                ' . number_format($totalProcessedUsers) . ' (' . round($processedPercent) . '%)
                            </div>
                        </div>
                        <p style="margin: 8px 0; font-size: 13px; color: #f0ad4e;">
                            <strong>⚠️ ' . get_string('notification_unprocessed_users', 'auth_contactws', number_format($totalUnprocessedUsers)) . '</strong>
                        </p>';
        }
        
        $message .= '                    </td>
                                                        </tr>
                                                    </table>
                                                </td>
                                                
                                                <!-- Moodle Users Column -->
                                                <td width="50%" style="vertical-align: top;">
                                                    <table border="0" cellpadding="0" cellspacing="0" width="95%" style="margin-left: auto; min-height: 160px; background-color: #f9f9f9; border-radius: 6px;">
                                                        <tr>
                                                            <td style="padding: 15px;">
                                                                <h3 style="margin: 0 0 15px 0; color: #0f6cbf; font-size: 16px;">Moodle</h3>
                                                                <p style="margin: 8px 0; font-size: 14px; color: #5cb85c;">
                                                                    <strong>✅ ' . get_string('notification_active_users', 'auth_contactws', number_format($activeusers)) . '</strong>
                                                                </p>
                                                                <p style="margin: 8px 0; font-size: 14px; color: #d9534f;">
                                                                    <strong>❌ ' . get_string('notification_suspended_users', 'auth_contactws', number_format($suspendedusers)) . '</strong>
                                                                </p>
                                                                <p style="margin: 8px 0; font-size: 14px; color: #f0ad4e;">
                                                                    <strong>⚠️ ' . get_string('notification_total_missing_users', 'auth_contactws', number_format($totalMissingUsers)) . '</strong>
                                                                </p>
                                                            </td>
                                                        </tr>
                                                    </table>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>';
        
        // Add status stats table
        if (!empty($statusStats)) {
            $message .= '<!-- Status Stats Table -->
                        <table border="0" cellpadding="0" cellspacing="0" width="100%" style="margin-bottom: 20px;">
                            <tr>
                                <td style="padding-bottom: 10px;">
                                    <h2 style="margin: 0; font-size: 18px; color: #333;">
                                        <img src="' . $CFG->wwwroot . '/pix/i/withsubcat.png" alt="" width="16" height="16" style="vertical-align: middle;"> 
                                        ' . get_string('notification_status_heading', 'auth_contactws') . '
                                    </h2>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <table border="0" cellpadding="0" cellspacing="0" width="100%" style="border-collapse: collapse; border: 1px solid #ddd; background-color: #fff;">
                                        <thead>
                                            <tr style="background-color: #f5f5f5;">
                                                <th style="padding: 10px; text-align: left; border: 1px solid #ddd; font-size: 14px;">
                                                    ' . get_string('notification_status_name', 'auth_contactws') . '
                                                </th>
                                                <th style="padding: 10px; text-align: center; border: 1px solid #ddd; font-size: 14px;">
                                                    ' . get_string('notification_status_count', 'auth_contactws') . '
                                                </th>
                                                <th style="padding: 10px; text-align: center; border: 1px solid #ddd; font-size: 14px;">
                                                    ' . get_string('notification_status_missing', 'auth_contactws') . '
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody>';
            
            // Active statuses (1, 3, 5)
            $activeStatusCodes = [1, 3, 5];
            foreach ($activeStatusCodes as $statusCode) {
                if (isset($statusStats[$statusCode])) {
                    $status = $statusStats[$statusCode];
                    $missingPercentage = ($status['count'] > 0) ? round(($status['missing'] / $status['count']) * 100, 2) : 0;
                    
                    $message .= '<tr>
                                    <td style="padding: 10px; border: 1px solid #ddd; font-size: 14px; font-weight: bold;">
                                        ' . $status['name'] . '
                                    </td>
                                    <td style="padding: 10px; border: 1px solid #ddd; font-size: 14px; text-align: center;">
                                        ' . number_format($status['count']) . '
                                    </td>
                                    <td style="padding: 10px; border: 1px solid #ddd; font-size: 14px; text-align: center;">';
                    
                    // If there are missing users, show in red with a warning icon
                    if ($status['missing'] > 0) {
                        $message .= '<span style="color: #d9534f; font-weight: bold;">
                                        ⚠️ ' . number_format($status['missing']) . ' (' . $missingPercentage . '%)
                                     </span>';
                    } else {
                        $message .= '<span style="color: #5cb85c; font-weight: bold;">
                                        ✅ 0
                                     </span>';
                    }
                    
                    $message .= '    </td>
                                </tr>';
                }
            }
            
            // Other statuses
            if (isset($statusStats['other']) && $statusStats['other']['count'] > 0) {
                $missingPercentage = ($statusStats['other']['count'] > 0) ? round(($statusStats['other']['missing'] / $statusStats['other']['count']) * 100, 2) : 0;
                
                $message .= '<tr style="background-color: #f9f9f9;">
                                <td style="padding: 10px; border: 1px solid #ddd; font-size: 14px; font-style: italic;">
                                    ' . get_string('status_other', 'auth_contactws') . '
                                </td>
                                <td style="padding: 10px; border: 1px solid #ddd; font-size: 14px; text-align: center;">
                                    ' . number_format($statusStats['other']['count']) . '
                                </td>
                                <td style="padding: 10px; border: 1px solid #ddd; font-size: 14px; text-align: center;">';
                
                if ($statusStats['other']['missing'] > 0) {
                    $message .= '<span style="color: #d9534f; font-weight: bold;">
                                    ⚠️ ' . number_format($statusStats['other']['missing']) . ' (' . $missingPercentage . '%)
                                 </span>';
                } else {
                    $message .= '<span style="color: #5cb85c; font-weight: bold;">
                                    ✅ 0
                                 </span>';
                }
                
                $message .= '    </td>
                            </tr>';
            }
            
            $message .= '        </tbody>
                                </table>
                            </td>
                        </tr>
                    </table>';
        }
        
        // Add footer
        $message .= '        <!-- Footer -->
                            <table border="0" cellpadding="0" cellspacing="0" width="100%" style="margin-top: 30px; border-top: 1px solid #f0f0f0; padding-top: 20px;">
                                <tr>
                                    <td style="color: #888; font-size: 12px; text-align: center;">
                                        <p style="margin: 0 0 10px 0;">' . get_string('notification_footer', 'auth_contactws', $sitename) . '</p>
                                        <p style="margin: 0;">
                                            <a href="' . $siteurl . '" style="color: #0f6cbf; text-decoration: none;">
                                                ' . $sitename . '
                                            </a>
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>';
        
        return $message;
    }
}