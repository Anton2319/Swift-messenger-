<?php
/**
 * OAuth 2.0 scope entity
 *
 * @package     league/oauth2-server
 * @author      Alex Bilbie <hello@alexbilbie.com>
 * @copyright   Copyright (c) Alex Bilbie
 * @license     http://mit-license.org/
 * @link        https://github.com/thephpleague/oauth2-server
 */

namespace League\OAuth2\Server\Entity;

use League\OAuth2\Server\AbstractServer;

/**
 * Scope entity class
 */

class ScopeEntity {

    /**
     * Scope id
     *
     * @var integer
     */
    var $id;

	/**
	 * Scope name
	 *
	 * @var string
	 */
	var $scope;


	/**
     * Scope description
     *
     * @var string
     */
    var $description;

    /**
     * Authorization or resource server
     *
     * @var \League\OAuth2\Server\AbstractServer
     */
    protected $server;

    /**
     * __construct
     *
     * @param \League\OAuth2\Server\AbstractServer $server
     *
     * @return self
     */
    public function __construct(AbstractServer $server)
    {
        $this->server = $server;

        return $this;
    }

    /**
     * Return the scope identifer
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

	/**
	 * Return the scope's name
	 *
	 * @return string
	 */
	public function getScope()
	{
		return $this->scope;
	}

    /**
     * Return the scope's description
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Returns a JSON object when entity is passed into json_encode
     *
     * @return array
     */

    public function jsonSerialize()
    {
        return array(
            'id'    =>  $this->getId(),
	        'scope' =>  $this->getScope(),
            'description'   =>  $this->getDescription()
        );
    }
}
