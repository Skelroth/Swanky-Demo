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
 * Standard values filtering used in the core.
 * @static
 * @package AjaXplorer
 * @subpackage Core
 */
class AJXP_VarsFilter {

    /**
     * Filter the very basic keywords from the XML  : AJXP_USER, AJXP_INSTALL_PATH, AJXP_DATA_PATH
     * Calls the vars.filter hooks.
     * @static
     * @param $value
     * @return mixed|string
     */
	public static function filter($value){
		if(is_string($value) && strpos($value, "AJXP_USER")!==false){
			if(AuthService::usersEnabled()){
				$loggedUser = AuthService::getLoggedUser();
				if($loggedUser != null){
                    if($loggedUser->hasParent() && $loggedUser->getResolveAsParent()){
                        $loggedUserId = $loggedUser->getParent();
                    }else{
                        $loggedUserId = $loggedUser->getId();
                    }
					$value = str_replace("AJXP_USER", $loggedUserId, $value);
				}else{
					return "";
				}
			}else{
				$value = str_replace("AJXP_USER", "shared", $value);
			}
		}
		if(is_string($value) && strpos($value, "AJXP_GROUP_PATH")!==false){
			if(AuthService::usersEnabled()){
				$loggedUser = AuthService::getLoggedUser();
				if($loggedUser != null){
					$gPath = $loggedUser->getGroupPath();
                    $value = str_replace("AJXP_GROUP_PATH_FLAT", str_replace("/", "_", trim($gPath, "/")), $value);
                    $value = str_replace("AJXP_GROUP_PATH", $gPath, $value);
				}else{
					return "";
				}
			}else{
                $value = str_replace(array("AJXP_GROUP_PATH", "AJXP_GROUP_PATH_FLAT"), "shared", $value);
            }
		}
		if(is_string($value) && strpos($value, "AJXP_INSTALL_PATH") !== false){
			$value = str_replace("AJXP_INSTALL_PATH", AJXP_INSTALL_PATH, $value);
		}
		if(is_string($value) && strpos($value, "AJXP_DATA_PATH") !== false){
			$value = str_replace("AJXP_DATA_PATH", AJXP_DATA_PATH, $value);
		}
        $tab = array(&$value);
		AJXP_Controller::applyIncludeHook("vars.filter", $tab);
		return $value;
	}
}
