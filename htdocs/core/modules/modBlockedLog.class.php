<?php
/* Copyright (C) 2017   Laurent Destailleur  <eldy@users.sourcefore.net>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * 	\defgroup   blockedlog   Module BlockedLog
 *  \brief      Add a log into a block chain for some actions.
 *  \file       htdocs/core/modules/modBlockedLog.class.php
 *  \ingroup    blockedlog
 *  \brief      Description and activation file for module BlockedLog
 */
include_once DOL_DOCUMENT_ROOT .'/core/modules/DolibarrModules.class.php';

/**
 *	Class to describe a BlockedLog module
 */
class modBlockedLog extends DolibarrModules
{
    /**
	 *   Constructor. Define names, constants, directories, boxes, permissions
	 *
	 *   @param      DoliDB		$db      Database handler
     */
    function __construct($db)
    {
    	global $langs,$conf,$mysoc;

        $this->db = $db;
        $this->numero = 3200;

		// Family can be 'crm','financial','hr','projects','products','ecm','technic','other'
		// It is used to group modules in module setup page
        $this->family = "base";
        // Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
        $this->name = preg_replace('/^mod/i','',get_class($this));
        $this->description = "Enable a log on some business events into a non reversible log. This module may be mandatory for some countries.";
		// Possible values for version are: 'development', 'experimental', 'dolibarr' or version
        $this->version = 'experimental';
        // Key used in llx_const table to save module status enabled/disabled (where MYMODULE is value of property name of module in uppercase)
        $this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
        // Where to store the module in setup page (0=common,1=interface,2=others,3=very specific)
        $this->special = 2;
        // Name of image file used for this module.
        $this->picto='technic';

        // Data directories to create when module is enabled
        $this->dirs = array();

        // Config pages
        //-------------
        $this->config_page_url = array('blockedlog.php@blockedlog');

        // Dependancies
        //-------------
	    $this->hidden = false;	// A condition to disable module
	    $this->depends = array('always'=>'modFacture');	   // List of modules id that must be enabled if this module is enabled
        $this->requiredby = array();	                   // List of modules id to disable if this one is disabled
	    $this->conflictwith = array();	                   // List of modules id this module is in conflict with
        $this->langfiles = array('blockedlog');

        $this->warnings_activation = array();                     // Warning to show when we activate module. array('always'='text') or array('FR'='textfr','ES'='textes'...)
        $this->warnings_activation_ext = array();                 // Warning to show when we activate an external module. array('always'='text') or array('FR'='textfr','ES'='textes'...)
        $this->warnings_unactivation = array('FR'=>'BlockedLogAreRequiredByYourCountryLegislation');

        // Currently, activation is not automatic because only companies (in France) making invoices to non business customers must
        // enable this module.
        // It is automatic only if $conf->global->BLOCKEDLOG_DISABLE_NOT_ALLOWED_FOR_COUNTRY is on.
        if (! empty($conf->global->BLOCKEDLOG_DISABLE_NOT_ALLOWED_FOR_COUNTRY))
        {
        	$this->automatic_activation = array('FR'=>'BlockedLogActivatedBecauseRequiredByYourCountryLegislation');
        }

        $this->always_enabled = !empty($conf->blockedlog->enabled) && !empty($conf->global->BLOCKEDLOG_DISABLE_NOT_ALLOWED_FOR_COUNTRY) && in_array($mysoc->country_code, explode(',', $conf->global->BLOCKEDLOG_DISABLE_NOT_ALLOWED_FOR_COUNTRY));

        // Constants
        //-----------
        $this->const = array();

        // New pages on tabs
        // -----------------
        $this->tabs = array();

        // Boxes
        //------
        $this->boxes = array();

        // Main menu entries
        //------------------
        $this->menu = array();
    }

    /**
     * Check if module was already used before unactivation linked to warnings_unactivation property
     */
    function alreadyUsed() {

    	$res = $this->db->query("SELECT count(*) as nb FROM ".MAIN_DB_PREFIX."blockedlog");
    	if($res!==false) {
    		$obj = $this->db->fetch_object($res);
    		return ($obj->nb > 0);
    	}

    	return false;
    }

    /**
	 * Function called when module is disabled.
	 * The remove function removes tabs, constants, boxes, permissions and menus from Dolibarr database.
	 * Data directories are not deleted
	 *
	 * @param      string	$options    Options when enabling module ('', 'noboxes')
	 * @return     int             		1 if OK, 0 if KO
	 */
    function remove($options = '') {

    	global $user;

    	require_once DOL_DOCUMENT_ROOT.'/blockedlog/class/blockedlog.class.php';

    	$object=new stdClass;
    	$object->id = 1;
    	$object->element = 'module';
    	$object->ref = 'module';
    	$object->date = time();

    	$b=new BlockedLog($this->db);
    	$b->setObjectData($object, 'MODULE_RESET', -1);

    	$res = $b->create($user);
    	if($res<=0) {
    		$this->error = $b->error;
    		return $res;
    	}

    	return $this->_remove(array(), $options);

    }
}