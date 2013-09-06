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
class AJXP_Sabre_BrowserPlugin extends Sabre\DAV\Browser\Plugin
{

    protected $repositoryLabel;

    function __construct($currentRepositoryLabel = null){
        parent::__construct(false, true);
        $this->repositoryLabel = $currentRepositoryLabel;
    }

    function generateDirectoryIndex($path){

        $html = parent::generateDirectoryIndex($path);
        $html = str_replace("image/vnd.microsoft.icon", "image/png", $html);

        $title = ConfService::getCoreConf("APPLICATION_TITLE");
        $html = preg_replace("/<title>(.*)<\/title>/i", '<title>'.$title.'</title>', $html);

        $repoString = "</h1>";
        if(!empty($this->repositoryLabel)){
            $repoString = " - ".$this->repositoryLabel."</h1><h2>Index of ".$this->escapeHTML($path)."/</h2>";
        }
        $html = preg_replace("/<h1>(.*)<\/h1>/i", "<h1>".$title.$repoString, $html);

        $html = str_replace("h1 { font-size: 150% }", "h1 { font-size: 150% } \n h2 { font-size: 115% }", $html);

        return $html;

    }

    function getLocalAssetPath($name){
        if($name != "favicon.ico") {
            return parent::getLocalAssetPath($name);
        }
        return AJXP_INSTALL_PATH."/plugins/gui.ajax/res/themes/umbra/images/html-folder.png";
    }

}
