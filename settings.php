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
    // Plugin Info & Dashboard Header
    $settings->add(new admin_setting_heading(
        'auth_contactws/dashboardheading',
        '<h3><i class="fa fa-tachometer fa-fw" aria-hidden="true"></i> ' . get_string('pluginname', 'auth_contactws') . ' Dashboard</h3>',
        '<div class="alert alert-info">' . get_string('auth_contactwsdescription', 'auth_contactws') . '</div>'
    ));
    
    // SECTION 1: SYNCHRONIZATION INFO
    // ------------------------------
    // Last synchronization information
    $lastsync = get_config('auth_contactws', 'last_sync_time');
    $executionTime = get_config('auth_contactws', 'sync_execution_time');
    
    $syncinfo = '<div class="card border-info mb-3">';
    $syncinfo .= '<div class="card-header bg-info text-white">';
    $syncinfo .= '<i class="fa fa-clock-o fa-fw" aria-hidden="true"></i> ' . get_string('sync_info_heading', 'auth_contactws');
    $syncinfo .= '</div>';
    $syncinfo .= '<div class="card-body">';
    
    if ($lastsync) {
        $syncdate = userdate($lastsync);
        $syncinfo .= '<p><strong>' . get_string('last_sync_info', 'auth_contactws', $syncdate) . '</strong></p>';
        
        if ($executionTime) {
            $formattedTime = round($executionTime, 2);
            $timeClass = $formattedTime > 180 ? 'text-danger' : ($formattedTime > 60 ? 'text-warning' : 'text-success');
            $syncinfo .= '<p class="' . $timeClass . '">';
            $syncinfo .= '<i class="fa fa-tachometer fa-fw" aria-hidden="true"></i> ';
            $syncinfo .= get_string('sync_execution_time', 'auth_contactws', $formattedTime);
            $syncinfo .= '</p>';
        }
        
        // Add a link to run the task manually
        $syncinfo .= '<div class="mt-3">';
        $syncinfo .= '<a href="' . new moodle_url('/admin/tool/task/schedule_task.php', ['task' => 'auth_contactws\task\sync_users_task']) . '" class="btn btn-sm btn-outline-info">';
        $syncinfo .= '<i class="fa fa-refresh fa-fw" aria-hidden="true"></i> ' . get_string('runsyncnow', 'auth_contactws');
        $syncinfo .= '</a>';
        $syncinfo .= '</div>';
    } else {
        $syncinfo .= '<div class="alert alert-warning">' . get_string('last_sync_never', 'auth_contactws') . '</div>';
        
        // Add a link to run the task for the first time
        $syncinfo .= '<div class="mt-3">';
        $syncinfo .= '<a href="' . new moodle_url('/admin/tool/task/schedule_task.php', ['task' => 'auth_contactws\task\sync_users_task']) . '" class="btn btn-sm btn-primary">';
        $syncinfo .= '<i class="fa fa-play fa-fw" aria-hidden="true"></i> ' . get_string('runsyncfirst', 'auth_contactws');
        $syncinfo .= '</a>';
        $syncinfo .= '</div>';
    }
    
    $syncinfo .= '</div></div>';

    $settings->add(new admin_setting_heading(
        'auth_contactws/syncinfo',
        '',
        $syncinfo
    ));

    // SECTION 2: USER STATISTICS
    // ------------------------------
    // User statistics section
    $activeusers = get_config('auth_contactws', 'active_users_count') ?? 0;
    $suspendedusers = get_config('auth_contactws', 'suspended_users_count') ?? 0;
    $totalApiUsers = get_config('auth_contactws', 'total_api_users') ?? 0;
    $totalMissingUsers = get_config('auth_contactws', 'total_missing_users') ?? 0;
    $totalProcessedUsers = get_config('auth_contactws', 'total_processed_users') ?? 0;
    $totalUnprocessedUsers = get_config('auth_contactws', 'total_unprocessed_users') ?? 0;

    $statsinfo = '<div class="card border-primary mb-3">';
    $statsinfo .= '<div class="card-header bg-primary text-white">';
    $statsinfo .= '<i class="fa fa-users fa-fw" aria-hidden="true"></i> ' . get_string('user_stats_heading', 'auth_contactws');
    $statsinfo .= '</div>';
    $statsinfo .= '<div class="card-body">';
    $statsinfo .= '<div class="row">';
    
    // First column: API users
    $statsinfo .= '<div class="col-md-6">';
    $statsinfo .= '<div class="card h-100">';
    $statsinfo .= '<div class="card-body">';
    $statsinfo .= '<h5 class="card-title"><i class="fa fa-database fa-fw" aria-hidden="true"></i> API SARH</h5>';
    $statsinfo .= '<p class="card-text text-primary">';
    $statsinfo .= '<strong>' . get_string('total_api_users_info', 'auth_contactws', number_format($totalApiUsers)) . '</strong></p>';
    
    // If we have unprocessed users, show that info
    if ($totalUnprocessedUsers > 0) {
        $processedPercent = ($totalProcessedUsers / $totalApiUsers) * 100;
        $statsinfo .= '<div class="progress mb-2" style="height: 20px;">';
        $statsinfo .= '<div class="progress-bar bg-success" role="progressbar" style="width: ' . $processedPercent . '%">';
        $statsinfo .= number_format($totalProcessedUsers) . ' (' . round($processedPercent) . '%)';
        $statsinfo .= '</div>';
        $statsinfo .= '</div>';
        
        $statsinfo .= '<p class="card-text text-warning"><i class="fa fa-exclamation-triangle fa-fw" aria-hidden="true"></i> ';
        $statsinfo .= get_string('unprocessed_users_info', 'auth_contactws', number_format($totalUnprocessedUsers)) . '</p>';
    }
    
    $statsinfo .= '</div></div></div>';
    
    // Second column: Moodle users
    $statsinfo .= '<div class="col-md-6">';
    $statsinfo .= '<div class="card h-100">';
    $statsinfo .= '<div class="card-body">';
    $statsinfo .= '<h5 class="card-title"><i class="fa fa-graduation-cap fa-fw" aria-hidden="true"></i> Moodle</h5>';
    
    $statsinfo .= '<p class="card-text"><i class="fa fa-check-circle fa-fw text-success" aria-hidden="true"></i> ';
    $statsinfo .= get_string('active_users_info', 'auth_contactws', number_format($activeusers)) . '</p>';
    
    $statsinfo .= '<p class="card-text"><i class="fa fa-ban fa-fw text-danger" aria-hidden="true"></i> ';
    $statsinfo .= get_string('suspended_users_info', 'auth_contactws', number_format($suspendedusers)) . '</p>';
    
    $statsinfo .= '<p class="card-text"><i class="fa fa-exclamation-circle fa-fw text-warning" aria-hidden="true"></i> ';
    $statsinfo .= get_string('missing_users_info', 'auth_contactws', number_format($totalMissingUsers)) . '</p>';
    
    $statsinfo .= '</div></div></div>';
    
    $statsinfo .= '</div>'; // End row
    $statsinfo .= '</div></div>'; // End card

    $settings->add(new admin_setting_heading(
        'auth_contactws/userstats',
        '',
        $statsinfo
    ));

    // SECTION 3: STATUS STATISTICS
    // ------------------------------
    // Users by status
    $statusStats = json_decode(get_config('auth_contactws', 'status_statistics'), true);
    if (!empty($statusStats)) {
        $statusinfo = '<div class="card border-success mb-3">';
        $statusinfo .= '<div class="card-header bg-success text-white">';
        $statusinfo .= '<i class="fa fa-bar-chart fa-fw" aria-hidden="true"></i> ' . get_string('status_stats_heading', 'auth_contactws');
        $statusinfo .= '</div>';
        $statusinfo .= '<div class="card-body" style="padding: 0;">';
        $statusinfo .= '<div class="table-responsive">';
        $statusinfo .= '<table class="table table-striped table-hover mb-0">';
        $statusinfo .= '<thead class="thead-dark">';
        $statusinfo .= '<tr>';
        $statusinfo .= '<th>' . get_string('status_name', 'auth_contactws') . '</th>';
        $statusinfo .= '<th class="text-center">' . get_string('status_count', 'auth_contactws') . '</th>';
        $statusinfo .= '<th class="text-center">' . get_string('status_missing', 'auth_contactws') . '</th>';
        $statusinfo .= '<th class="text-center">' . get_string('status_percentage', 'auth_contactws') . '</th>';
        $statusinfo .= '</tr>';
        $statusinfo .= '</thead>';
        $statusinfo .= '<tbody>';
        
        // Active statuses (1, 3, 5)
        $activeStatusCodes = [1, 3, 5];
        foreach ($activeStatusCodes as $statusCode) {
            if (isset($statusStats[$statusCode])) {
                $status = $statusStats[$statusCode];
                $percentage = ($totalApiUsers > 0) ? round(($status['count'] / $totalApiUsers) * 100, 2) : 0;
                $missingPercentage = ($status['count'] > 0) ? round(($status['missing'] / $status['count']) * 100, 2) : 0;
                
                $statusinfo .= '<tr>';
                $statusinfo .= '<td><strong>' . $status['name'] . '</strong></td>';
                $statusinfo .= '<td class="text-center">' . number_format($status['count']) . '</td>';
                $statusinfo .= '<td class="text-center">';
                
                // If there are missing users, show in red with a warning icon
                if ($status['missing'] > 0) {
                    $statusinfo .= '<span class="text-danger">';
                    $statusinfo .= '<i class="fa fa-exclamation-triangle fa-fw" aria-hidden="true"></i> ';
                    $statusinfo .= number_format($status['missing']) . ' (' . $missingPercentage . '%)';
                    $statusinfo .= '</span>';
                } else {
                    $statusinfo .= '<span class="text-success">';
                    $statusinfo .= '<i class="fa fa-check fa-fw" aria-hidden="true"></i> 0';
                    $statusinfo .= '</span>';
                }
                
                $statusinfo .= '</td>';
                $statusinfo .= '<td class="text-center">';
                
                // Progress bar for percentage
                $statusinfo .= '<div class="progress" style="height: 20px;">';
                $statusinfo .= '<div class="progress-bar bg-info" role="progressbar" style="width: ' . $percentage . '%">';
                $statusinfo .= $percentage . '%';
                $statusinfo .= '</div>';
                $statusinfo .= '</div>';
                
                $statusinfo .= '</td>';
                $statusinfo .= '</tr>';
            }
        }
        
        // Other statuses
        if (isset($statusStats['other']) && $statusStats['other']['count'] > 0) {
            $percentage = ($totalApiUsers > 0) ? round(($statusStats['other']['count'] / $totalApiUsers) * 100, 2) : 0;
            $missingPercentage = ($statusStats['other']['count'] > 0) ? round(($statusStats['other']['missing'] / $statusStats['other']['count']) * 100, 2) : 0;
            
            $statusinfo .= '<tr class="table-secondary">';
            $statusinfo .= '<td><em>' . get_string('status_other', 'auth_contactws') . '</em></td>';
            $statusinfo .= '<td class="text-center">' . number_format($statusStats['other']['count']) . '</td>';
            $statusinfo .= '<td class="text-center">';
            
            if ($statusStats['other']['missing'] > 0) {
                $statusinfo .= '<span class="text-danger">';
                $statusinfo .= '<i class="fa fa-exclamation-triangle fa-fw" aria-hidden="true"></i> ';
                $statusinfo .= number_format($statusStats['other']['missing']) . ' (' . $missingPercentage . '%)';
                $statusinfo .= '</span>';
            } else {
                $statusinfo .= '<span class="text-success">';
                $statusinfo .= '<i class="fa fa-check fa-fw" aria-hidden="true"></i> 0';
                $statusinfo .= '</span>';
            }
            
            $statusinfo .= '</td>';
            $statusinfo .= '<td class="text-center">';
            
            // Progress bar for percentage
            $statusinfo .= '<div class="progress" style="height: 20px;">';
            $statusinfo .= '<div class="progress-bar bg-secondary" role="progressbar" style="width: ' . $percentage . '%">';
            $statusinfo .= $percentage . '%';
            $statusinfo .= '</div>';
            $statusinfo .= '</div>';
            
            $statusinfo .= '</td>';
            $statusinfo .= '</tr>';
        }
        
        $statusinfo .= '</tbody>';
        $statusinfo .= '</table>';
        $statusinfo .= '</div>'; // End table-responsive
        $statusinfo .= '</div></div>'; // End card
        
        $settings->add(new admin_setting_heading(
            'auth_contactws/statusstats',
            '',
            $statusinfo
        ));
    }

    // SECTION 4: CONFIGURATION
    // ------------------------------
    // Plugin Configuration Section
    $settings->add(new admin_setting_heading(
        'auth_contactws/configheading',
        '<hr><h4><i class="fa fa-cog fa-fw" aria-hidden="true"></i> ' . get_string('auth_contactwssettings', 'auth_contactws') . '</h4>',
        '<div class="alert alert-secondary">Configure the connection settings to the SARH Web Services API.</div>'
    ));
    
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

    // SECTION 5: NOTIFICATION SETTINGS
    // ------------------------------
    // Task Settings Heading
    $settings->add(new admin_setting_heading(
        'auth_contactws/tasksettings',
        '<h4><i class="fa fa-bell fa-fw" aria-hidden="true"></i> ' . get_string('task_settings_heading', 'auth_contactws') . '</h4>',
        get_string('task_settings_heading_desc', 'auth_contactws')
    ));

    // Enable admin notifications
    $settings->add(new admin_setting_configcheckbox(
        'auth_contactws/enable_admin_notifications',
        get_string('enable_admin_notifications', 'auth_contactws'),
        get_string('enable_admin_notifications_desc', 'auth_contactws'),
        0
    ));

    // Select site admins for notifications
    $admins = get_admins();
    $adminoptions = [];
    foreach ($admins as $admin) {
        $adminoptions[$admin->id] = $admin->firstname . ' ' . $admin->lastname . ' (' . $admin->email . ')';
    }

    $settings->add(new admin_setting_configmultiselect(
        'auth_contactws/notification_admin_ids',
        get_string('notification_admin_ids', 'auth_contactws'),
        get_string('notification_admin_ids_desc', 'auth_contactws'),
        [],
        $adminoptions
    ));

    // SECTION 6: FIELD LOCKS
    // ------------------------------
    // Auth plugin locks
    $settings->add(new admin_setting_heading(
        'auth_contactws/lockheading',
        '<h4><i class="fa fa-lock fa-fw" aria-hidden="true"></i> ' . get_string('auth_fieldlocks', 'auth') . '</h4>',
        get_string('auth_fieldlocks_help', 'auth')
    ));
    
    $authplugin = get_auth_plugin('contactws');
    display_auth_lock_options($settings, $authplugin->authtype, $authplugin->userfields,
        '', false, false, $authplugin->customfields);
}