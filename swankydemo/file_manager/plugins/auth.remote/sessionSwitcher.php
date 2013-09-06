<?php
/*
 * Copyright 2007-2011 Charles du Jeu <contact (at) cdujeu.me>
 * This file is part of AjaXplorer.
 *
 * AjaXplorer is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * AjaXplorer is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with AjaXplorer.  If not, see <http://www.gnu.org/licenses/>.
 *
 * The latest code can be found at <http://www.ajaxplorer.info/>.
 */
defined('AJXP_EXEC') or die( 'Access not allowed');

/**
 * Utilitary class for switching session
 * @package AjaXplorer_Plugins
 * @subpackage Auth
 */
class SessionSwitcher
{
    /** The current session stack */
    public static $sessionArray;
    
    /** Construction. This kills the current session if any started, and restart the given session */
    public function __construct($name, $killPreviousSession = false, $loadPreviousSession = false, $saveHandlerType = "files", $saveHandlerData = null)
    {   
    	AJXP_Logger::debug("Switching to session ".$name); 	
        if (session_id() == "")
        {
			if(isSet($saveHandlerData)){
				session_set_save_handler(
					$saveHandlerData["open"], 
					$saveHandlerData["close"], 
					$saveHandlerData["read"], 
					$saveHandlerData["write"], 
					$saveHandlerData["destroy"], 
					$saveHandlerData["gc"]					
				);
			}else{
				if(ini_get("session.save_handler")!=$saveHandlerType){
					ini_set('session.save_handler', $saveHandlerType);
				}
			}
            // Start a default session and save on the handler
            session_start();
            SessionSwitcher::$sessionArray[] = array('id'=>session_id(), 'name'=>session_name());
            session_write_close();
        }else{
        	SessionSwitcher::$sessionArray[] = array('id'=>session_id(), 'name'=>session_name());
        }
        // Please note that there is no start here, session might be already started
        if (session_id() != "")
        {
            // There was a previous session
            if ($killPreviousSession)
            {
                if (isset($_COOKIE[session_name()]))
				setcookie(session_name(), '', time() - 42000, '/');
                session_destroy();
            }
            AJXP_Logger::debug("Closing previous session ".session_name()." / ".session_id());
            session_write_close();
            session_regenerate_id(false);
            $_SESSION = array();
        }

		if(isSet($saveHandlerData)){
			session_set_save_handler(
				$saveHandlerData["open"], 
				$saveHandlerData["close"], 
				$saveHandlerData["read"], 
				$saveHandlerData["write"], 
				$saveHandlerData["destroy"], 
				$saveHandlerData["gc"]					
			);
		}else{
			if(ini_get("session.save_handler")!=$saveHandlerType){
				ini_set('session.save_handler', $saveHandlerType);
			}
		}

        if($loadPreviousSession){
	        AJXP_Logger::debug("Restoring previous session".SessionSwitcher::$sessionArray[0]['id']);
			session_id(SessionSwitcher::$sessionArray[0]['id']);
        }else{
        	$newId = md5(SessionSwitcher::$sessionArray[0]['id'].$name);        
        	session_id($newId);
        }
        session_name($name);
        session_start();
        AJXP_Logger::debug("Restarted session ".session_name()." / ".session_id(), $_SESSION);
    }
};
?>