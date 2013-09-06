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
require_once('../classes/class.AbstractTest.php');

/**
 * Check that DOMXml is enabled
 * @package AjaXplorer
 * @subpackage Tests
 */
class PHPDomXML extends AbstractTest
{
    function PHPDomXML() { parent::AbstractTest("DOM Xml enabled", "Dom XML is required, you may have to install the php-xml extension."); }
    function doTest() 
    { 
        $this->failedLevel = "error";
        if (!class_exists("DOMDocument")){
        	$this->testedParams["DOM Enabled"] = "No";
        	return FALSE;
        }
        $this->testedParams["DOM Enabled"] = "Yes";
        return TRUE;
    }
};

?>