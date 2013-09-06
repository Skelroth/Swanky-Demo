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
 * @package AjaXplorer_Plugins
 * @subpackage Core
 * @class AbstractAjxpUser
 * @abstract
 * User abstraction, the "conf" driver must provides its own implementation
 */
abstract class AbstractAjxpUser
{
	var $id;
	var $hasAdmin = false;
	var $rights;
    /**
     * @var AJXP_Role[]
     */
    var $roles;
	var $prefs;
	var $bookmarks;
	var $version;
	var $parentUser;
    var $resolveAsParent = false;

    var $groupPath = "/";
    /**
     * @var AJXP_Role
     */
    public $mergedRole;

    /**
     * @var AJXP_Role
     */
    public $parentRole;

    /**
     * @var AJXP_Role Accessible for update
     */
    public $personalRole;

	/**
	 * Conf Storage implementation
	 *
	 * @var AbstractConfDriver
	 */
	var $storage;
	
	function AbstractAjxpUser($id, $storage=null){
		$this->id = $id;
		if($storage == null){
			$storage = ConfService::getConfStorageImpl();
		}
		$this->storage = $storage;		
		$this->load();
	}

    function checkCookieString($cookieString){
        if($this->getPref("cookie_hash") == "") return false;
        $hashes = explode(",", $this->getPref("cookie_hash"));
        return in_array($cookieString, $hashes);
    }

    function invalidateCookieString($cookieString = ""){
        if($this->getPref("cookie_hash") == "") return false;
        $hashes = explode(",", $this->getPref("cookie_hash"));
        if(in_array($cookieString, $hashes)) $hashes = array_diff($hashes, array($cookieString));
        $this->setPref("cookie_hash", implode(",", $hashes));
        $this->save("user");
    }

	function getCookieString(){
		$hashes = $this->getPref("cookie_hash");
        if($hashes == ""){
            $hashes = array();
        }else{
            $hashes = explode(",", $hashes);
        }
        $newHash =  md5($this->id.":".time());
        array_push($hashes, $newHash);
        $this->setPref("cookie_hash", implode(",",$hashes));
        $this->save("user");
		return $newHash; //md5($this->id.":".$newHash.":ajxp");
	}
	
	function getId(){
		return $this->id;
	}

    /**
     * @return bool
     */
    function storageExists(){
		
	}
	
	function getVersion(){
		if(!isSet($this->version)) return "";
		return $this->version;
	}
	
	function setVersion($v){
		$this->version = $v;
	}

    /**
     * @param AJXP_Role $roleObject
     */
    function addRole($roleObject){
        if(isSet($this->roles[$roleObject->getId()])){
            // NOTHING SPECIAL TO DO !
            return;
        }
		if(!isSet($this->rights["ajxp.roles"])) $this->rights["ajxp.roles"] = array();
		$this->rights["ajxp.roles"][$roleObject->getId()] = true;
        uksort($this->rights["ajxp.roles"], array($this, "orderRoles"));
        $this->roles[$roleObject->getId()] = $roleObject;
        $this->recomputeMergedRole();
	}
	
	function removeRole($roleId){
		if(isSet($this->rights["ajxp.roles"]) && isSet($this->rights["ajxp.roles"][$roleId])){
			unset($this->rights["ajxp.roles"][$roleId]);
            uksort($this->rights["ajxp.roles"], array($this, "orderRoles"));
            if(isSet($this->roles[$roleId])) unset($this->roles[$roleId]);
        }
        $this->recomputeMergedRole();
	}
	
	function getRoles(){
		if(isSet($this->rights["ajxp.roles"])) {
            uksort($this->rights["ajxp.roles"], array($this, "orderRoles"));
            return $this->rights["ajxp.roles"];
        }else {
            return array();
        }
	}

    function getProfile(){
        if(isSet($this->rights["ajxp.profile"])) {
            return $this->rights["ajxp.profile"];
        }
        if($this->isAdmin()) return "admin";
        if($this->hasParent()) return "shared";
        if($this->getId() == "guest") return "guest";
        return "standard";
    }

    function setProfile($profile){
        $this->rights["ajxp.profile"] = $profile;
    }

    function setLock($lockAction){
        $this->rights["ajxp.lock"] = $lockAction;
    }

    function removeLock(){
        $this->rights["ajxp.lock"] = false;
    }

    function getLock(){
        if(!empty($this->rights["ajxp.lock"])){
            return $this->rights["ajxp.lock"];
        }
        return false;
    }

	function isAdmin(){
		return $this->hasAdmin; 
	}
	
	function setAdmin($boolean){
		$this->hasAdmin = $boolean;
	}
	
	function hasParent(){
		return isSet($this->parentUser);
	}
	
	function setParent($user){
		$this->parentUser = $user;
	}
	
	function getParent(){
		return $this->parentUser;
	}
	
	function canRead($rootDirId){
        if(!empty($this->rights["ajxp.lock"])) return false;
        return $this->mergedRole->canRead($rootDirId);
	}
	
	function canWrite($rootDirId){
        if(!empty($this->rights["ajxp.lock"])) return false;
        return $this->mergedRole->canWrite($rootDirId);
    }

	/**
	 * Test if user can switch to this repository
	 *
	 * @param integer $repositoryId
	 * @return boolean
	 */
	function canSwitchTo($repositoryId){
		$repositoryObject = ConfService::getRepositoryById($repositoryId);
        if($repositoryObject == null) return false;
        return ConfService::repositoryIsAccessible($repositoryId, $repositoryObject, $this, false, false);
        /*
		if($repositoryObject->getAccessType() == "ajxp_conf" && !$this->isAdmin()) return false;
        if($repositoryObject->getUniqueUser() && $this->id != $repositoryObject->getUniqueUser()) return false;
		return ($this->mergedRole->canRead($repositoryId) || $this->mergedRole->canWrite($repositoryId)) ;
        */
	}
	
	function getRight($rootDirId){
        return $this->mergedRole->getAcl($rootDirId);
	}

	function getPref($prefName){
        if($prefName == "lang"){
            // Migration path
            if(isSet($this->mergedRole)){
                $l = $this->mergedRole->filterParameterValue("core.conf", "lang", AJXP_REPO_SCOPE_ALL, "");
                if($l != "") return $l;
            }
        }
		if(isSet($this->prefs[$prefName])) return $this->prefs[$prefName];
		return "";
	}
	
	function setPref($prefName, $prefValue){
		$this->prefs[$prefName] = $prefValue;
	}
	
	function setArrayPref($prefName, $prefPath, $prefValue){
		if(!isSet($this->prefs[$prefName])) $this->prefs[$prefName] = array();
		$this->prefs[$prefName][$prefPath] = $prefValue;
	}
	
	function getArrayPref($prefName, $prefPath){
		if(!isSet($this->prefs[$prefName]) || !isSet($this->prefs[$prefName][$prefPath])) return "";
		return $this->prefs[$prefName][$prefPath];
	}
		
	function addBookmark($path, $title="", $repId = -1){
		if(!isSet($this->bookmarks)) $this->bookmarks = array();
		if($repId == -1) $repId = ConfService::getCurrentRepositoryId();
		if($title == "") $title = basename($path);
		if(!isSet($this->bookmarks[$repId])) $this->bookmarks[$repId] = array();
		foreach ($this->bookmarks[$repId] as $v)
		{
			$toCompare = "";
			if(is_string($v)) $toCompare = $v;
			else if(is_array($v)) $toCompare = $v["PATH"];
			if($toCompare == trim($path)) return ; // RETURN IF ALREADY HERE!
		}
		$this->bookmarks[$repId][] = array("PATH"=>trim($path), "TITLE"=>$title);
	}
	
	function removeBookmark($path){
		$repId = ConfService::getCurrentRepositoryId();
		if(isSet($this->bookmarks) 
			&& isSet($this->bookmarks[$repId])
			&& is_array($this->bookmarks[$repId]))
			{
				foreach ($this->bookmarks[$repId] as $k => $v)
				{
					$toCompare = "";
					if(is_string($v)) $toCompare = $v;
					else if(is_array($v)) $toCompare = $v["PATH"];					
					if($toCompare == trim($path)) unset($this->bookmarks[$repId][$k]);
				}
			} 		
	}
	
	function renameBookmark($path, $title){
		$repId = ConfService::getCurrentRepositoryId();
		if(isSet($this->bookmarks) 
			&& isSet($this->bookmarks[$repId])
			&& is_array($this->bookmarks[$repId]))
			{
				foreach ($this->bookmarks[$repId] as $k => $v)
				{
					$toCompare = "";
					if(is_string($v)) $toCompare = $v;
					else if(is_array($v)) $toCompare = $v["PATH"];					
					if($toCompare == trim($path)){
						 $this->bookmarks[$repId][$k] = array("PATH"=>trim($path), "TITLE"=>$title);
					}
				}
			} 		
	}
	
	function getBookmarks()
	{
		if(isSet($this->bookmarks) 
			&& isSet($this->bookmarks[ConfService::getCurrentRepositoryId()]))
			return $this->bookmarks[ConfService::getCurrentRepositoryId()];
		return array();
	}
	
	abstract function load();
	
	abstract function save($context = "superuser");
	
	abstract function getTemporaryData($key);
	
	abstract function saveTemporaryData($key, $value);

    /** Decode a user supplied password before using it */
    function decodeUserPassword($password){
        if (function_exists('mcrypt_decrypt'))
        {
             // The initialisation vector is only required to avoid a warning, as ECB ignore IV
             $iv = mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB), MCRYPT_RAND);
             // We have encoded as base64 so if we need to store the result in a database, it can be stored in text column
             $password = trim(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, md5($this->getId()."\1CDAFx¨op#"), base64_decode($password), MCRYPT_MODE_ECB, $iv), "\0");
        }
        return $password;
    }

    public function setGroupPath($groupPath, $update = false)
    {
        $this->groupPath = $groupPath;
    }

    public function getGroupPath()
    {
        if(!isSet($this->groupPath)) return null;
        return $this->groupPath;
    }

    public function recomputeMergedRole(){
        if(!count($this->roles)) {
            throw new Exception("Empty role, this is not normal");
        }
        uksort($this->roles, array($this, "orderRoles"));
        $this->mergedRole =  $this->roles[array_shift(array_keys($this->roles))];
        if(count($this->roles) > 1){
            $this->parentRole = $this->mergedRole;
        }
        $index = 0;
        foreach($this->roles as $role){
            if($index > 0) {
                $this->mergedRole = $role->override($this->mergedRole);
                if($index < count($this->roles) -1 ) $this->parentRole = $role->override($this->parentRole);
            }
            $index ++;
        }
        if($this->hasParent() && isSet($this->parentRole)){
            // It's a shared user, we don't want it to inherit the rights
            $this->parentRole->clearAcls();
            $this->mergedRole = $this->parentRole->override($this->personalRole);
            //$this->mergedRole
        }
    }

    protected function migrateRightsToPersonalRole(){
        $this->personalRole = new AJXP_Role("AJXP_USR_"."/".$this->id);
        $this->roles["AJXP_USR_"."/".$this->id] = $this->personalRole;
        foreach($this->rights as $rightKey => $rightValue){
            if($rightKey == "ajxp.actions" && is_array($rightValue)){
                foreach($rightValue as $repoId => $repoData){
                    foreach($repoData as $actionName => $actionState){
                        $this->personalRole->setActionState("plugin.all", $actionName, $repoId, $actionState);
                    }
                }
                unset($this->rights[$rightKey]);
            }
            if(strpos($rightKey, "ajxp.") === 0) continue;
            $this->personalRole->setAcl($rightKey, $rightValue);
            unset($this->rights[$rightKey]);
        }
        // Move old CUSTOM_DATA values to personal role parameter
        $customValue = $this->getPref("CUSTOM_PARAMS");
        $custom = ConfService::getConfStorageImpl()->getOption("CUSTOM_DATA");
        if(is_array($custom) && count($custom)){
            foreach($custom as $key => $value){
                if(isSet($customValue[$key])){
                    $this->personalRole->setParameterValue(ConfService::getConfStorageImpl()->getId(), $key, $customValue[$key]);
                }
            }
        }

        // Move old WALLET values to personal role parameter
        $wallet = $this->getPref("AJXP_WALLET");
        if(is_array($wallet) && count($wallet)){
            foreach($wallet as $repositoryId => $walletData){
                $repoObject = ConfService::getRepositoryById($repositoryId);
                if($repoObject == null) continue;
                $accessType = "access.".$repoObject->getAccessType();
                foreach($walletData as $paramName => $paramValue){
                    $this->personalRole->setParameterValue($accessType, $paramName, $paramValue, $repositoryId);
                }
            }
        }

    }

    protected function orderRoles($r1, $r2){
        if(strpos($r1, "AJXP_USR_") === 0) return 1;
        if(strpos($r2, "AJXP_USR_") === 0) return -1;
        return strcmp($r1,$r2);
    }

    public function setResolveAsParent($resolveAsParent)
    {
        $this->resolveAsParent = $resolveAsParent;
    }

    public function getResolveAsParent()
    {
        return $this->resolveAsParent;
    }

    /**
     * @param array $roles
     * @return array
     */
    protected function filterRolesForSaving($roles){
        $res = array();
        foreach($roles as $rName => $status){
            if(!$status) continue;
            if(strpos($rName, "AJXP_GRP_/") === 0) continue;
            $res[$rName] = true;
        }
        return $res;
    }

}