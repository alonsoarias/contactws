# SARH Authentication Plugin for Moodle

## Description

The SARH Authentication Plugin (auth_contactws) allows Moodle to authenticate users against an external SARH Web Services API. This plugin synchronizes user data between the SARH system and Moodle, providing a seamless login experience while keeping user information updated.

## Key Features

- External authentication against SARH Web Services API
- Automatic user creation and data synchronization
- Field mapping between SARH and Moodle user profiles
- Case transformation (lowercase for usernames/emails, uppercase for names)
- Scheduled user synchronization and suspension
- Admin notifications with user statistics

## Requirements

- Moodle 3.11 or higher (tested with Moodle 3.11 to 4.2)
- PHP 7.4 or higher
- SARH Web Services API access credentials
- Custom profile fields created in Moodle for SARH-specific data

### Required Custom Profile Fields

The following custom profile fields must be created in Moodle:
- NombreCampana (Campaign Name)
- NombreCentro (Center Name)
- Cargo (Position)
- JefeInmediato (Immediate Supervisor)
- FechaContrato (Contract Date) - should be a Date/Time field

## Installation

1. Download the plugin
2. Extract the folder and rename it to `contactws`
3. Place the folder in your Moodle installation under `/auth/`
4. Visit the notifications page as an administrator to complete the installation
5. Navigate to Site Administration > Plugins > Authentication > Manage Authentication
6. Enable the "SARH Web Services Authentication" plugin

## Configuration

### Authentication Settings

1. Go to Site Administration > Plugins > Authentication > SARH Web Services Authentication
2. Configure the following settings:
   - **API Base URL**: The base URL for the SARH Web Services API (e.g., https://webdes.americasbps.com/ApiSarh/api)
   - **API Username**: Username for SARH API authentication
   - **API Password**: Password for SARH API authentication

### Task Settings

In the same configuration page, you can configure the scheduled tasks:

1. **Enable admin notifications**: Toggle to enable/disable email notifications to administrators
2. **Notify administrators**: Select which administrators should receive user statistics and reports

## Scheduled Tasks

The plugin includes two scheduled tasks:

### 1. SARH User Synchronization (Daily at 3:00 AM)

This task:
- Connects to the SARH API to retrieve the current user status
- Suspends users who are not present in the API response
- Handles duplicate document numbers by keeping the newest user active
- Maintains users with statuses 1, 3, and 5 as active
- Tracks users who exist in SARH but not in Moodle

### 2. SARH Admin Notification (Daily at 8:30 AM)

This task:
- Sends email notifications to selected administrators
- Provides statistics on active and suspended users
- Reports on users present in SARH but missing in Moodle
- Includes last synchronization information

## User Authentication Flow

1. User enters their username and password on the Moodle login page
2. The plugin authenticates against the SARH API
3. If authentication is successful, user data is retrieved from the API
4. The plugin maps the data to Moodle user fields
5. If the user doesn't exist in Moodle, a new account is created
6. If the user exists, their information is updated
7. The user is logged in to Moodle

## Debug Information

The plugin includes extensive debugging information. To view this information:

1. Enable debugging in Moodle (Site Administration > Development > Debugging)
2. Set debug level to DEVELOPER
3. Check "Display debug messages"

Debug messages will appear prefixed with `[auth_contactws]` followed by the component name in brackets.

## Troubleshooting

### Authentication Issues

- Verify the API credentials are correct
- Check the API Base URL is accessible from your Moodle server
- Ensure the SARH API is operational
- Check debug logs for specific error messages

### User Data Issues

- Verify that all required custom profile fields exist in Moodle
- Check the field mapping in the `user_field_mapping.php` file
- Ensure users have the correct document numbers (idnumber) in both systems

### Task Issues

- Verify the scheduled tasks are enabled in Moodle
- Check the Moodle cron is running properly
- Review task logs in the Moodle interface

## License

This plugin is licensed under the GNU GPL v3 or later. See the LICENSE file for details.

## Contributing

Contributions to this plugin are welcome. Please feel free to submit issues or pull requests.

## Contact

For support or questions, please contact:
- Pedro Arias
- soporte@ingeweb.co
- AmericasBPS

---

© 2025 AmericasBPS | SARH Authentication Plugin for Moodle | Developed for educcamvirtual.com