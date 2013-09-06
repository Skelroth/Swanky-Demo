<?php
/*
 * Copyright 2007-2013 Charles du Jeu <contact (at) cdujeu.me>
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


class AJXP_Sabre_AuthBackendBasic extends Sabre\DAV\Auth\Backend\AbstractBasic{

    protected $currentUser;
    private $repositoryId;

    /**
     * Utilitary method to detect basic header.
     * @return bool
     */
    public static function detectBasicHeader(){
        if(isSet($_SERVER["PHP_AUTH_USER"])) return true;
        if(isSet($_SERVER["HTTP_AUTHORIZATION"])) $value = $_SERVER["HTTP_AUTHORIZATION"];
        if(!isSet($value) && isSet($_SERVER["REDIRECT_HTTP_AUTHORIZATION"])) $value = $_SERVER["HTTP_AUTHORIZATION"];
        if(!isSet($value)) return false;
        return  (strpos(strtolower($value),'basic') ===0) ;
    }

    function __construct($repositoryId){
        $this->repositoryId = $repositoryId;
    }


	protected function validateUserPass($username, $password) {
		// Warning, this can only work if TRANSMIT_CLEAR_PASS is true;
        return AuthService::checkPassword($username, $password, false, -1);
	}

    public function authenticate(Sabre\DAV\Server $server, $realm){
        $auth = new Sabre\HTTP\BasicAuth();
        $auth->setHTTPRequest($server->httpRequest);
        $auth->setHTTPResponse($server->httpResponse);
        $auth->setRealm($realm);
        $userpass = $auth->getUserPass();
        if (!$userpass) {
            $auth->requireLogin();
            throw new Sabre\DAV\Exception\NotAuthenticated('No basic authentication headers were found');
        }

        // Authenticates the user
		//AJXP_Logger::logAction("authenticate: " . $userpass[0]);

		$confDriver = ConfService::getConfStorageImpl();
		$userObject = $confDriver->createUserObject($userpass[0]);
		$webdavData = $userObject->getPref("AJXP_WEBDAV_DATA");
		if (empty($webdavData) || !isset($webdavData["ACTIVE"]) || $webdavData["ACTIVE"] !== true) {
			return false;
		}
        // check if there are cached credentials. prevents excessive authentication calls to external
        // auth mechanism.
        $cachedPasswordValid = 0;
        $secret = (defined("AJXP_SECRET_KEY")? AJXP_SECRET_KEY:"\1CDAFx¨op#");
        $encryptedPass = md5($userpass[1].$secret.date('YmdHi'));
        if (isSet($webdavData["TMP_PASS"]) && ($encryptedPass == $webdavData["TMP_PASS"])) {
            $cachedPasswordValid = true;
            //AJXP_Logger::debug("Using Cached Password");
        }


        if (!$cachedPasswordValid && (!$this->validateUserPass($userpass[0],$userpass[1]))) {
            $auth->requireLogin();
            throw new Sabre\DAV\Exception\NotAuthenticated('Username or password does not match');
        }
        $this->currentUser = $userpass[0];

		AuthService::logUser($this->currentUser, null, true);
		$res = $this->updateCurrentUserRights(AuthService::getLoggedUser());
		if($res === false){
			return false;
		}

		// the method used here will invalidate the cached password every minute on the minute
		if (!$cachedPasswordValid) {
			$webdavData["TMP_PASS"] = $encryptedPass;
			$userObject->setPref("AJXP_WEBDAV_DATA", $webdavData);
			$userObject->save("user");
			AuthService::updateUser($userObject);
		}

        return true;
    }


    /**
     * @param AbstractAjxpUser $user
     * @return bool
     */
    protected function updateCurrentUserRights($user){
        if(!$user->canSwitchTo($this->repositoryId)){
            return false;
        }
        return true;
    }


}