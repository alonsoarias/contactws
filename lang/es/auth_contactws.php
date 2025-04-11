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

$string['pluginname'] = 'Autenticación de Servicios Web SARH';
$string['auth_contactwsdescription'] = 'Los usuarios pueden iniciar sesión utilizando la API de Servicios Web SARH';
$string['baseurl'] = 'URL Base de la API';
$string['baseurl_desc'] = 'URL base para la API de Servicios Web SARH';
$string['apiusername'] = 'Usuario de la API';
$string['apiusername_desc'] = 'Nombre de usuario para la autenticación en la API SARH';
$string['apipassword'] = 'Contraseña de la API';
$string['apipassword_desc'] = 'Contraseña para la autenticación en la API SARH';
$string['auth_contactwssettings'] = 'Configuración de Autenticación SARH';
$string['errorauthtoken'] = 'Error al obtener token de autenticación';
$string['errorauthapi'] = 'Error al conectar con la API SARH';
$string['errorauthresponse'] = 'Respuesta inválida de la API SARH';
$string['errorauthcreateaccount'] = 'No se pueden crear nuevas cuentas';
$string['notwhileloggedinas'] = 'No se pueden gestionar inicios de sesión vinculados mientras está conectado como otro usuario';
$string['alreadylinked'] = 'Esta cuenta externa ya está vinculada a una cuenta de Moodle';
$string['privacy:metadata'] = 'El plugin de autenticación de Servicios Web SARH no almacena ningún dato personal';

// Cadenas relacionadas con tareas programadas
$string['task_sync_users'] = 'Sincronización de Usuarios SARH';
$string['task_notify_admins'] = 'Notificación a Administradores SARH';
$string['task_settings_heading'] = 'Configuración de Notificaciones';
$string['task_settings_heading_desc'] = 'Configure notificaciones por correo electrónico para mantenerse informado sobre la sincronización de usuarios.';
$string['enable_admin_notifications'] = 'Habilitar notificaciones a administradores';
$string['enable_admin_notifications_desc'] = 'Enviar notificaciones por correo electrónico a los administradores seleccionados con estadísticas de usuarios.';
$string['notification_admin_ids'] = 'Notificar a administradores';
$string['notification_admin_ids_desc'] = 'Seleccionar qué administradores deben recibir notificaciones.';
$string['sync_info_heading'] = 'Estado de Sincronización';
$string['last_sync_info'] = 'Última sincronización: {$a}';
$string['last_sync_never'] = 'Aún no se ha realizado ninguna sincronización.';
$string['sync_execution_time'] = 'Tiempo de ejecución: {$a} segundos';
$string['runsyncnow'] = 'Ejecutar Sincronización Ahora';
$string['runsyncfirst'] = 'Ejecutar Primera Sincronización';
$string['user_stats_heading'] = 'Estadísticas de Usuarios';
$string['active_users_info'] = 'Usuarios SARH activos en Moodle: {$a}';
$string['suspended_users_info'] = 'Usuarios SARH suspendidos en Moodle: {$a}';
$string['total_api_users_info'] = 'Total de usuarios desde la API SARH: {$a}';
$string['missing_users_info'] = 'Usuarios en API SARH pero no en Moodle: {$a}';
$string['processed_users_info'] = 'Usuarios procesados de la API: {$a}';
$string['unprocessed_users_info'] = 'Usuarios no procesados de la API (límite de tiempo alcanzado): {$a}';

// Estadísticas por estado
$string['status_stats_heading'] = 'Usuarios por Estado';
$string['status_name'] = 'Nombre del Estado';
$string['status_count'] = 'Cantidad';
$string['status_missing'] = 'Faltantes';
$string['status_percentage'] = 'Porcentaje';
$string['status_other'] = 'Otros Estados';

// Usuarios faltantes
$string['missing_users_heading'] = 'Usuarios Faltantes';
$string['missing_users_intro'] = 'Los siguientes usuarios están activos en SARH pero no en Moodle ({$a} total):';
$string['missing_user_docnumber'] = 'Número de Documento';
$string['missing_user_status'] = 'Estado';
$string['missing_user_count'] = 'Cantidad';
$string['missing_users_more'] = 'Y {$a} más...';

// Cadenas de mensajes de notificación
$string['notification_subject'] = 'Informe de Estado de Usuarios SARH';
$string['notification_smallmessage'] = 'El Informe de Estado de Usuarios SARH está disponible';
$string['notification_title'] = 'Informe de Estado de Usuarios SARH para {$a}';
$string['notification_intro'] = 'Este es un informe automatizado sobre el estado actual de los usuarios SARH en su sistema Moodle.';
$string['notification_stats_heading'] = 'Estadísticas de Usuarios';
$string['notification_active_users'] = 'Usuarios SARH activos en Moodle: {$a}';
$string['notification_suspended_users'] = 'Usuarios SARH suspendidos en Moodle: {$a}';
$string['notification_last_sync'] = 'Última sincronización: {$a}';
$string['notification_execution_time'] = 'Tiempo de ejecución: {$a} segundos';
$string['notification_total_api_users'] = 'Total de usuarios desde la API SARH: {$a}';
$string['notification_total_missing_users'] = 'Usuarios en API SARH pero no en Moodle: {$a}';
$string['notification_processed_users'] = 'Usuarios procesados de la API: {$a}';
$string['notification_unprocessed_users'] = 'Usuarios no procesados de la API (límite de tiempo alcanzado): {$a}';
$string['notification_unprocessed_warning'] = '¡Advertencia: Se alcanzó el límite de tiempo durante la sincronización!';

// Estadísticas por estado en notificación
$string['notification_status_heading'] = 'Usuarios por Estado en API SARH';
$string['notification_status_name'] = 'Nombre del Estado';
$string['notification_status_count'] = 'Cantidad';
$string['notification_status_missing'] = 'Faltantes en Moodle';

// Usuarios faltantes en notificación
$string['notification_missing_by_status_heading'] = 'Usuarios Faltantes por Estado';
$string['notification_missing_status'] = 'Estado';
$string['notification_missing_count'] = 'Cantidad';
$string['notification_missing_heading'] = 'Usuarios faltantes en Moodle';
$string['notification_missing_intro'] = 'Los siguientes usuarios están activos en SARH pero no existen en Moodle:';
$string['notification_missing_docnumber'] = 'Número de Documento';
$string['notification_no_missing_heading'] = 'No Hay Usuarios Faltantes';
$string['notification_no_missing_text'] = 'Todos los usuarios activos en SARH existen en Moodle.';
$string['notification_footer'] = 'Este es un mensaje automatizado de {$a}. Por favor, no responda a este correo electrónico.';
$string['never'] = 'Nunca';