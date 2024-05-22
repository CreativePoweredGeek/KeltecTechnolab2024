<?php

namespace CartThrob\Model;

use ExpressionEngine\Service\Model\Model;

/**
 * Session Model
 */
class Session extends AbstractModel
{
    /**
     * @todo Add rules to validate related data
     * @var string[]
     */
    protected static $_validation_rules = [
        'member_id' => 'required|numeric|validateMemberExists',
        'session_id' => 'required|alphaNumeric',
        'cart_id' => 'numeric',
        'fingerprint' => 'required',
        'expires' => 'required',
        'sess_expiration' => 'numeric',
    ];

    protected static $_primary_key = 'session_id';
    protected static $_table_name = 'cartthrob_sessions';

    protected $session_id;
    protected $member_id;
    protected $cart_id;
    protected $fingerprint;
    protected $expires;
    protected $sess_expiration;

    protected static $_relationships = [
        'Member' => [
            'type' => 'BelongsTo',
            'model' => 'ee:Member',
            'from_key' => 'member_id',
            'to_key' => 'member_id',
        ],
    ];
}
