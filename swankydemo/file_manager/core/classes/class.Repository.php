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
 * The basic abstraction of a data store. Can map a FileSystem, but can also map data from a totally
 * different source, like the application configurations, a mailbox, etc.
 * @package AjaXplorer
 * @subpackage Core
 */
class Repository implements AjxpGroupPathProvider {

    /**
     * @var string
     */
	var $uuid;
    /**
     * @var string
     */
	var $id;
    /**
     * @var string
     */
	var $path;
    /**
     * @var string
     */
	var $display;
    /**
     * @var string
     */
	var $displayStringId;
    /**
     * @var string
     */
	var $accessType = "fs";
    /**
     * @var string
     */
	var $recycle = "";
    /**
     * @var bool
     */
	var $create = true;
    /**
     * @var bool
     */
	var $writeable = true;
    /**
     * @var bool
     */
	var $enabled = true;
    /**
     * @var array
     */
	var $options = array();
    /**
     * @var string
     */
	var $slug;
    /**
     * @var bool
     */
    public $isTemplate = false;
	
    /**
     * @var string
     */
	private $owner;
    /**
     * @var string
     */
	private $parentId;
    /**
     * @var string
     */
	private $uniqueUser;
    /**
     * @var bool
     */
	private $inferOptionsFromParent;
	/**
	 * @var Repository
	 */
	private $parentTemplateObject;
	/**
     * @var array
     */
	public $streamData;

    /**
     * @var String the groupPath of the administrator who created that repository.
     */
    protected $groupPath;


    public $driverInstance;

    /**
     * @param string $id
     * @param string $display
     * @param string $driver
     * @return void
     */
	function Repository($id, $display, $driver){
		$this->setAccessType($driver);
		$this->setDisplay($display);
		$this->setId($id);
		$this->uuid = md5(microtime());
		$this->slug = AJXP_Utils::slugify($display);
        $this->inferOptionsFromParent = false;
        $this->options["CREATION_TIME"] = time();
        if(AuthService::usersEnabled() && AuthService::getLoggedUser() != null){
            $this->options["CREATION_USER"] = AuthService::getLoggedUser()->getId();
        }
	}

	/**
     * Create a shared version of this repository
     * @param string $newLabel
     * @param array $newOptions
     * @param string $parentId
     * @param string $owner
     * @param string $uniqueUser
     * @return Repository
     */
	function createSharedChild($newLabel, $newOptions, $parentId = null, $owner = null, $uniqueUser = null){
		$repo = new Repository(0, $newLabel, $this->accessType);
		$newOptions = array_merge($this->options, $newOptions);
		$repo->options = $newOptions;
		if($parentId == null){
			$parentId = $this->getId();
		}
		$repo->setOwnerData($parentId, $owner, $uniqueUser);
		return $repo;
	}
	/**
     * Create a child from this repository if it's a template
     * @param string $newLabel
     * @param array $newOptions
     * @param string $owner
     * @param string $uniqueUser
     * @return Repository
     */
	function createTemplateChild($newLabel, $newOptions, $owner = null, $uniqueUser = null){
		$repo = new Repository(0, $newLabel, $this->accessType);
		$repo->options = $newOptions;
		$repo->setOwnerData($this->getId(), $owner, $uniqueUser);
		$repo->setInferOptionsFromParent(true);
		return $repo;
	}
	/**
     * Recompute uuid
     * @return bool
     */
	function upgradeId(){
		if(!isSet($this->uuid)) {
			$this->uuid = md5(serialize($this));
			//$this->uuid = md5(time());
			return true;
		}
		return false;
	}
	/**
     * Get a uuid
     * @param bool $serial
     * @return string
     */
	function getUniqueId($serial=false){
		if($serial){
			return md5(serialize($this));
		}
		return $this->uuid;
	}
	/**
     * Alias for this repository
     * @return string
     */
	function getSlug(){
		return $this->slug;
	}
	/**
     * Use the slugify function to generate an alias from the label
     * @param string $slug
     * @return void
     */
	function setSlug($slug = null){
		if($slug == null){
			$this->slug = AJXP_Utils::slugify($this->display);
		}else{
			$this->slug = $slug;
		}
	}
	/**
     * Get the <client_settings> content of the manifest.xml
     * @return DOMElement|DOMNodeList|string
     */
	function getClientSettings(){
        $plugin = AJXP_PluginsService::findPlugin("access", $this->accessType);
        if(!$plugin) return "";
        if(isSet($this->parentId)){
            $parentObject = ConfService::getRepositoryById($this->parentId);
            if($parentObject != null && $parentObject->isTemplate){
                $ic = $parentObject->getOption("TPL_ICON_SMALL");
                $settings = $plugin->getManifestRawContent("//client_settings", "node");
                if(!empty($ic) && $settings->length){
                    $newAttr = $settings->item(0)->ownerDocument->createAttribute("icon_tpl_id");
                    $newAttr->nodeValue = $this->parentId;
                    $settings->item(0)->appendChild($newAttr);
                    return $settings->item(0)->ownerDocument->saveXML($settings->item(0));
                }
            }
        }
        return $plugin->getManifestRawContent("//client_settings", "string");
	}
	/**
     * Find the streamWrapper declared by the access driver
     * @param bool $register
     * @param array $streams
     * @return bool
     */
	function detectStreamWrapper($register = false, &$streams=null){
		$plugin = AJXP_PluginsService::findPlugin("access", $this->accessType);
		if(!$plugin) return(false);
		$streamData = $plugin->detectStreamWrapper($register);
		if(!$register && $streamData !== false && is_array($streams)){
			$streams[$this->accessType] = $this->accessType;
		}
		if($streamData !== false) $this->streamData = $streamData;
		return ($streamData !== false);
	}
	
    /**
     * Add options
     * @param $oName
     * @param $oValue
     * @return void
     */
	function addOption($oName, $oValue){
		if(strpos($oName, "PATH") !== false){
			$oValue = str_replace("\\", "/", $oValue);
		}
		$this->options[$oName] = $oValue;
	}
	/**
     * Get the repository options, filtered in various maners
     * @param string $oName
     * @param bool $safe Do not filter
     * @return mixed|string
     */
	function getOption($oName, $safe=false){
        if(!$safe && $this->inferOptionsFromParent){
            if(!isset($this->parentTemplateObject)){
                $this->parentTemplateObject = ConfService::getRepositoryById($this->parentId);
            }
            if(isSet($this->parentTemplateObject)){
                $value = $this->parentTemplateObject->getOption($oName, $safe);
                if(is_string($value) && strstr($value, "AJXP_ALLOW_SUB_PATH") !== false){
                    $val = rtrim(str_replace("AJXP_ALLOW_SUB_PATH", "", $value), "/")."/".$this->options[$oName];
                    return AJXP_Utils::securePath($val);
                }
            }
        }
        if(isSet($this->options[$oName])){
			$value = $this->options[$oName];			
			if(!$safe) $value = AJXP_VarsFilter::filter($value);
			return $value;
		}
		if($this->inferOptionsFromParent){
			if(!isset($this->parentTemplateObject)){
				$this->parentTemplateObject = ConfService::getRepositoryById($this->parentId);
			}
			if(isSet($this->parentTemplateObject)){
				return $this->parentTemplateObject->getOption($oName, $safe);
			}
		}
		return "";
	}

    function resolveVirtualRoots($path){

        // Gathered from the current role
        $roots = $this->listVirtualRoots();
        if(!count($roots)) return $path;
        foreach($roots as $rootKey => $rootValue){
            if(strpos($path, "/".ltrim($rootKey, "/")) === 0){
                return preg_replace("/^\/{$rootKey}/", $rootValue["path"], $path, 1);
            }
        }
        return $path;

    }

    function listVirtualRoots(){

        return array();
        /* TEST STUB
        $roots = array(
            "root1" => array(
                "right" => "rw",
                "path" => "/Test"),
            "root2" => array(
                "right" => "r",
                "path" => "/Retoto/sub"
            ));
        return $roots;
        */
    }

	/**
     * Get the options that already have a value
     * @return array
     */
	function getOptionsDefined(){
        //return array_keys($this->options);
		$keys = array();
		foreach($this->options as $key => $value){
			if(is_string($value) && strstr($value, "AJXP_ALLOW_SUB_PATH") !== false) continue;
            $keys[] = $key;
		}
		return $keys;
	}

    /**
     * Get the DEFAULT_RIGHTS option
     * @return string
     */
	function getDefaultRight(){
		$opt = $this->getOption("DEFAULT_RIGHTS");
		return (isSet($opt)?$opt:"");
	}
	
	
	/**
     * The the access driver type
	 * @return String
	 */
	function getAccessType() {
		return $this->accessType;
	}
	
	/**
     * The label of this repository
	 * @return String
	 */
	function getDisplay() {
		if(isSet($this->displayStringId)){
			$mess = ConfService::getMessages();
			if(isSet($mess[$this->displayStringId])){
				return SystemTextEncoding::fromUTF8($mess[$this->displayStringId]);
			}
		}
		return $this->display;
	}
	
	/**
	 * @return string
	 */
	function getId() {
        if($this->isWriteable()) return $this->getUniqueId();
		return $this->id;
	}
	
	/**
	 * @return boolean
	 */
	function getCreate() {
		return $this->getOption("CREATE");
	}
	
	/**
	 * @param boolean $create
	 */
	function setCreate($create) {
		$this->options["CREATE"] = $create;
	}

	
	/**
	 * @param String $accessType
	 */
	function setAccessType($accessType) {
		$this->accessType = $accessType;
	}
	
	/**
	 * @param String $display
	 */
	function setDisplay($display) {
		$this->display = $display;
	}
	
	/**
	 * @param int $id
	 */
	function setId($id) {
		$this->id = $id;
	}
	
	function isWriteable(){
		return $this->writeable;
	}
	
	function setWriteable($w){
		$this->writeable = $w;
	}
	
	function isEnabled(){
		return $this->enabled;
	}
	
	function setEnabled($e){
		$this->enabled = $e;
	}
	
	function setDisplayStringId($id){
		$this->displayStringId = $id;
	}
	
	function setOwnerData($repoParentId, $ownerUserId = null, $childUserId = null){
		$this->owner = $ownerUserId;
		$this->uniqueUser = $childUserId;
		$this->parentId = $repoParentId;
	}
	
	function getOwner(){
		return $this->owner;
	}
	
	function getParentId(){
		return $this->parentId;
	}
	
	function getUniqueUser(){
		return $this->uniqueUser;
	}
	
	function hasOwner(){
		return isSet($this->owner);
	}
		
	function hasParent(){
		return isSet($this->parentId);
	}

    function setInferOptionsFromParent($bool){
        $this->inferOptionsFromParent = $bool;
    }

    function getInferOptionsFromParent(){
        return $this->inferOptionsFromParent;
    }

    /**
     * @param String $groupPath
     */
    public function setGroupPath($groupPath)
    {
        $this->groupPath = $groupPath;
    }

    /**
     * @return String
     */
    public function getGroupPath()
    {
        return $this->groupPath;
    }

    /**
     * @param String $descriptionText
     */
    public function setDescription( $descriptionText ){
        $this->options["USER_DESCRIPTION"] = $descriptionText;
    }

    /**
     * @return String
     */
    public function getDescription (){
        $m = ConfService::getMessages();
        if(isset($this->options["USER_DESCRIPTION"]) && !empty($this->options["USER_DESCRIPTION"])){
            if(isSet($m[$this->options["USER_DESCRIPTION"]])) {
                return $m[$this->options["USER_DESCRIPTION"]];
            } else {
                return $this->options["USER_DESCRIPTION"];
            }
        }if(isSet($this->parentId) && isset($this->owner)){
            if(isSet($this->options["CREATION_TIME"])){
                $date = AJXP_Utils::relativeDate($this->options["CREATION_TIME"], $m);
                return str_replace(array("%date", "%user"), array($date, $this->owner), $m["473"]);
            }else{
                return str_replace(array("%user"), array($this->owner), $m["472"]);
            }
        }else if($this->isWriteable() && isSet($this->options["CREATION_TIME"])){
            $date = AJXP_Utils::relativeDate($this->options["CREATION_TIME"], $m);
            if(isSet($this->options["CREATION_USER"])){
                return str_replace(array("%date", "%user"), array($date, $this->options["CREATION_USER"]), $m["471"]);
            }else{
                return str_replace(array("%date"), array($date), $m["470"]);
            }
        }else{
            return $m["474"];
        }
    }

    /**
     * Infer a security scope for this repository. Will determine to whome the messages
     * will be broadcasted.
     * @return bool|string
     */
    public function securityScope(){
        $path = $this->getOption("PATH", true);
        if($this->accessType == "ajxp_conf") return "USER";
        if(empty($path)) return false;
        if(strpos($path, "AJXP_USER") !== false) return "USER";
        if(strpos($path, "AJXP_GROUP_PATH") !== false) return "GROUP";
        if(strpos($path, "AJXP_GROUP_PATH_FLAT") !== false) return "GROUP";
        return false;
    }

}