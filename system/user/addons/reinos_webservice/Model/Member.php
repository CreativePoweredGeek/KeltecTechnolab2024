<?php

/**
 * @author		Rein de Vries <support@reinos.nl>
 * @link		https://addons.reinos.nl
 * @copyright 	Copyright (c) 2011 - 2021 Reinos.nl Internet Media
 * @license     https://addons.reinos.nl/commercial-license
 *
 * Copyright (c) 2011 - 2021 Reinos.nl Internet Media
 * All rights reserved.
 *
 * This source is commercial software. Use of this software requires a
 * site license for each domain it is used on. Use of this software or any
 * of its source code without express written permission in the form of
 * a purchased commercial or other license is prohibited.
 *
 * THIS CODE AND INFORMATION ARE PROVIDED "AS IS" WITHOUT WARRANTY OF ANY
 * KIND, EITHER EXPRESSED OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND/OR FITNESS FOR A
 * PARTICULAR PURPOSE.
 *
 * As part of the license agreement for this software, all modifications
 * to this source must be submitted to the original author for review and
 * possible inclusion in future releases. No compensation will be provided
 * for patches, although where possible we will attribute each contribution
 * in file revision notes. Submitting such modifications constitutes
 * assignment of copyright to the original author (Rein de Vries and
 * Reinos.nl Internet Media) for such modifications. If you do not wish to assign
 * copyright to the original author, your license to  use and modify this
 * source is null and void. Use of this software constitutes your agreement
 * to this clause.
 */

namespace Reinos\Webservice\Model;

use EllisLab\ExpressionEngine\Service\Model\Model;

class Member extends Model {

    protected static $_primary_key = 'webservice_member_id';
    protected static $_table_name = REINOS_WEBSERVICE_MAP.'_members';

    protected static $_relationships = array(
        'Shortkey' => array(
            'type'     => 'hasMany',
            'model'    => REINOS_WEBSERVICE_SERVICE_NAME.':Shortkey',
            'from_key' => 'webservice_member_id',
            'to_key'   => 'webservice_member_id'
        ),

        'Key' => array(
            'type'     => 'HasOne',
            'model'    => REINOS_WEBSERVICE_SERVICE_NAME.':Key',
            'from_key' => 'webservice_member_id',
            'to_key'   => 'webservice_member_id'
        ),

        //attach member data
        'Member' => array(
            'type'     => 'belongsTo',
            'model'    => 'ee:Member',
            'from_key' => 'member_id',
            'to_key'   => 'member_id',
            'weak'     => TRUE,
            'inverse' => array(
                'name' => 'Member',
                'type' => 'hasMany'
            )
        ),
        'Role' => array(
            'type'     => 'belongsTo',
            'model'    => 'ee:Role',
            'from_key' => 'role_id',
            'to_key'   => 'role_id',
            'weak'     => TRUE,
            'inverse' => array(
                'name' => 'Member',
                'type' => 'hasMany'
            )
        ),
    );

    protected static $_events = array('beforeSave', 'afterSave', 'beforeDelete', 'afterInsert');

    protected $webservice_member_id;
    protected $member_id;
    protected $role_id;
    protected $services;
    protected $apis;
    protected $shortkeys;
    protected $active;
    protected $type;
    protected $auth;

    // ----------------------------------------------------------------

    public function getMemberName()
    {
        $name = "";

        if ($this->member_id)
        {
            $name = $this->Member->getMemberName();
        }
        elseif ($this->role_id)
        {
            $name = $this->Role->name;
        }

        return $name;
    }

    // ----------------------------------------------------------------

    /**
     * hook for before save
     */
    public function onBeforeSave()
    {
        $this->filterDuplicatedShortkeys();
    }

    // ----------------------------------------------------------------

    /**
     * Hook for after save
     */
    public function onAfterSave()
    {
        //save the shortkeys
        $this->saveShortkey();
    }

    // ----------------------------------------------------------------

    /**
     * Hook for after save
     */
    public function onAfterInsert()
    {
        //save the Key
        $this->saveKeySecret();
    }

    // ----------------------------------------------------------------

    /**
     * hook for before delete
     */
    public function onBeforeDelete()
    {
        //not needed because there is a relation????

        //delete current keys
        $shortkeys = ee('Model')->get(REINOS_WEBSERVICE_SERVICE_NAME.':Shortkey')->filter('webservice_member_id', $this->getProperty('webservice_member_id'))->all();
        if($shortkeys->count() > 0)
        {
            $shortkeys->delete();
        }
    }

    // ----------------------------------------------------------------

    /**
     * Filter the duplicates from the shortkey field
     */
    private function filterDuplicatedShortkeys()
    {
        //delete current keys
        $shortkeys = ee('Model')->get(REINOS_WEBSERVICE_SERVICE_NAME.':Shortkey')->filter('webservice_member_id', $this->getProperty('webservice_member_id'))->all();
        if($shortkeys->count() > 0)
        {
            $shortkeys->delete();
        }

        //Set the keys for this model and skip duplicated ones
        $shortkeys = $this->getProperty('shortkeys');
        if(!empty($shortkeys))
        {
            //save the keys that can be saved
            $new_shortkeys = array();

            foreach(explode("\n", $shortkeys) as $shortkey)
            {
                if($shortkey != '')
                {
                    //check if this key already exists
                    $shortkey_exists = ee('Model')->get(REINOS_WEBSERVICE_SERVICE_NAME.':Shortkey')->filter('shortkey', $shortkey)->all();
                    if($shortkey_exists->count() == 0)
                    {
                        //save the key
                        $new_shortkeys[] = $shortkey;
                    }
                }
            }

            //save the new keys also in this model
            $new_shortkeys = !empty($new_shortkeys) ? implode("\n", $new_shortkeys) : '';
            $this->setProperty('shortkeys', $new_shortkeys);
        }
    }

    // ----------------------------------------------------------------

    /**
     * Save the Shortkeys to the
     */
    private function saveShortkey()
    {
        //save the api keys via the model
        $shortkeys = $this->getProperty('shortkeys');

        if(!empty($shortkeys))
        {
            foreach(explode("\n", $shortkeys) as $shortkey)
            {
                if($shortkey != '')
                {
                    //set and save the key
                    $_shortkey = ee('Model')->make(REINOS_WEBSERVICE_SERVICE_NAME.':Shortkey');
                    $_shortkey->site_id = ee()->config->item('site_id');
                    $_shortkey->webservice_member_id = $this->getProperty('webservice_member_id');
                    $_shortkey->shortkey = $shortkey;
                    $_shortkey->save();
                }
            }
        }
    }

    // ----------------------------------------------------------------

    /**
     * Save the Key Secret
     */
    private function saveKeySecret()
    {
        $key = ee('Model')->make(REINOS_WEBSERVICE_SERVICE_NAME.':Key');
        $key->site_id = ee()->config->item('site_id');
        $key->webservice_member_id = $this->getProperty('webservice_member_id');
        $key->key = md5(time().$this->getProperty('webservice_member_id'));
        $key->secret = md5(rand().time().rand().$this->getProperty('webservice_member_id'));
        $key->save();
    }
}
