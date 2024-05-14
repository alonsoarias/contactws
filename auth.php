<?php


if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}

require_once($CFG->libdir.'/authlib.php');
require_once($CFG->dirroot.'/user/lib.php');


class auth_plugin_contactws extends auth_plugin_base {

    function auth_plugin_contactws() {
        $this->authtype = 'contactws';
        $this->config = get_config('auth/contactws');
		
		//user profile fields you want to update
		$fields_to_update = array('firstname', 'lastname','idnumber','phone1','address');

		foreach ($fields_to_update as $field) {
			$this->config->{'field_updatelocal_'.$field} = 'onlogin';
		}
		
		//user profile fields you want to update
		$fields_to_lock = array('firstname', 'lastname','idnumber','phone1','address');

		foreach ($fields_to_lock as $field) {
			$this->config->{'field_lock_'.$field} = 'onlogin';
		}
    }



    function user_login($username, $password) {

        global $CFG;
        $service = $this->config->service;   // Url del servicio web para autenticaci?n
        $userparam =  $this->config->userparam;
        $pswdparam =  $this->config->pswdparam;
        $addparam = $this->config->addparam;
        
        
        if ($res = curl_post($service, $userparam . "=" . urlencode($username) . "&" . $pswdparam . "=" . urlencode($password) . "&" . $addparam . "=true"    )  ){
         	
         	$result_xml = simplexml_load_string($res);
         	
         
         	    $is_valid_user =  $result_xml[0];
         	
         }


        if ($is_valid_user == "true"){//--//
        	
        	return true;
        	
        }
        
       
        return false;

    }
    
    function prevent_local_passwords() {
        return true;
    }


	function get_userinfo($username) {
		global $CFG,$DB;
		$userinfo = array();

		$servicio = "http://educcamvirtual.com/pwsservice/servicioeduccam.php";
		if ($res = curl_post($servicio, "Usuario=".utf8_decode($username)."&Contrasena=edusarh*$2&op=gud" )  ){
			$user = simplexml_load_string($res,'SimpleXMLElement', LIBXML_NOCDATA);

			$user = json_decode(json_encode($user), TRUE);

			if(!empty($user)) {
				$userinfo['firstname'] = $user['usuario']['nombres'];
				$userinfo['lastname'] = $user['usuario']['apellidos'];
				//$userinfo['email'] = $user['usuario']['email'];
				$userinfo['idnumber'] = $user['usuario']['numero_cedula'];
				$userinfo['phone1'] = $user['usuario']['phone1'];
				$userinfo['address'] = $user['usuario']['adress'];
			}


            //quitar valores vacios
			foreach($userinfo as $key => $value) {
				if (!$value) {
					unset($userinfo[$key]);
				}
			}

			$estado = (array_key_exists('usuario',$user) && array_key_exists('estado',$user['usuario']))?(int) $user['usuario']['estado']:-1;

			$suspendido = ($estado == 0) ? 1 : 0;

            if ($estado==-1){

                $usuario_actual=array();
            } else{

            	$sql = "SELECT u.*
  					  FROM {user} u
  					 WHERE u.auth = :auth
  						   AND lower(u.username) = lower(:username)";
  			    $usuario_actual = $DB->get_records_sql($sql, array('auth'=>$this->authtype,'username'=>$username));
            }

			foreach($usuario_actual as $usr) {
			  if($usr->suspended==0 && $suspendido==1){
				$updateuser = new stdClass();
				$updateuser->id = $usr->id;
				$updateuser->suspended = $suspendido;
                //user_update_user($updateuser, false);

                $suspendido=0;

			  }

              if($suspendido == 1) {
					//mtrace("\t".get_string('auth_dbsuspenduser', 'auth_db', array('name'=>$usr->username, 'id'=>$usr->id)));
					\core\session\manager::kill_user_sessions($usr->id);
              }
            }
		}
		return $userinfo;
    }


    function is_internal() {
        return false;
    }


    function can_change_password() {
        return !empty($this->config->changepasswordurl);
    }


    function change_password_url() {
        return new moodle_url($this->config->changepasswordurl);
    }


    function config_form($config, $err, $user_fields) {
        global $OUTPUT;

        include "config.html";
    }


    function process_config($config) {
        // set to defaults if undefined
        if (!isset ($config->service)) {
            $config->service = '127.0.0.1';
        }
        if (!isset ($config->userparam)) {
            $config->userparam = 'Usuario';
        }
        if (!isset ($config->pswdparam)) {
            $config->pswdparam = 'Contrasena';
        }
        if (!isset ($config->addparam)) {
            $config->pswdparam = 'true';
        }
     if (!isset ($config->changepasswordurl)) {
            $config->changepasswordurl = '#';
        }
        
        
        
     

        // save settings
        set_config('service',    $config->service,    'auth/contactws');
        set_config('userparam',    $config->userparam,    'auth/contactws');
        set_config('pswdparam',    $config->pswdparam,    'auth/contactws');
        set_config('addparam',    $config->addparam,    'auth/contactws');
        set_config('changepasswordurl',    $config->changepasswordurl,    'auth/contactws');
  

        return true;
    }

}


function curl_post($url, $post = NULL, array $options = array()) {
    $defaults = array(
        CURLOPT_POST => 1,
        CURLOPT_HEADER => 0,
        CURLOPT_URL => $url,
        CURLOPT_FRESH_CONNECT => 1,
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_FORBID_REUSE => 1,
        CURLOPT_TIMEOUT => 14,
        CURLOPT_POSTFIELDS => $post
    );
    

    $ch = curl_init();
    curl_setopt_array($ch, ($options + $defaults));
    if (!$result = curl_exec($ch)) {
        echo curl_error($ch);
        trigger_error(curl_error($ch));
    }
    curl_close($ch);
    return $result;
}


