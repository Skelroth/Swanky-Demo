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
 * @package AjaXplorer
 * @subpackage SabreDav
 */
class AJXP_Sabre_AuthBackendDigest extends Sabre\DAV\Auth\Backend\AbstractDigest{

    protected $currentUser;
    private  $secretKey;
    private $repositoryId;

    function __construct($repositoryId){
        $this->repositoryId = $repositoryId;
        if(defined('AJXP_SAFE_SECRET_KEY')){
            $this->secretKey = AJXP_SAFE_SECRET_KEY;
        }else{
            $this->secretKey = "\1CDAFx¨op#";
        }
    }

    public function getDigestHash($realm, $username){
        if(!AuthService::userExists($username)){
            return false;
        }
        $confDriver = ConfService::getConfStorageImpl();
        $user = $confDriver->createUserObject($username);
        $webdavData = $user->getPref("AJXP_WEBDAV_DATA");
        if(empty($webdavData) || !isset($webdavData["ACTIVE"]) || $webdavData["ACTIVE"] !== true || (!isSet($webdavData["PASS"]) && !isset($webdavData["HA1"]) ) ){
            return false;
        }
        if(isSet($webdavData["HA1"])){
            return $webdavData["HA1"];
        }else{
            $pass = $this->_decodePassword($webdavData["PASS"], $username);
            return md5("{$username}:{$realm}:{$pass}");
        }

    }

    public function authenticate(Sabre\DAV\Server $server, $realm){
        //AJXP_Logger::debug("Try authentication on $realm", $server);

        $success = parent::authenticate($server, $realm);
        if($success){
            $res = AuthService::logUser($this->currentUser, null, true);
            if($res < 1){
                throw new Sabre\DAV\Exception\NotAuthenticated();
            }
            $this->updateCurrentUserRights(AuthService::getLoggedUser());
            if(ConfService::getCoreConf("SESSION_SET_CREDENTIALS", "auth")){
                $webdavData = AuthService::getLoggedUser()->getPref("AJXP_WEBDAV_DATA");
                AJXP_Safe::storeCredentials($this->currentUser, $this->_decodePassword($webdavData["PASS"], $this->currentUser));
            }
        }
        if($success === false){
            throw new Sabre\DAV\Exception\NotAuthenticated();
        }
        ConfService::loadRepositoryDriver();
        return true;
    }


    protected function updateCurrentUserRights($user){
        if($this->repositoryId == null) {
            return true;
        }
        if(!$user->canSwitchTo($this->repositoryId)){
            throw new Sabre\DAV\Exception\NotAuthenticated();
        }
    }

    private function _decodePassword($encoded, $user){
        if (function_exists('mcrypt_decrypt'))
        {
            $iv = mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB), MCRYPT_RAND);
            $encoded = trim(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, md5($user.$this->secretKey), base64_decode($encoded), MCRYPT_MODE_ECB, $iv), "\0");
        }
        return $encoded;
    }



}
