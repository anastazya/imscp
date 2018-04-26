<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2018 by Laurent Declercq <l.declercq@nuxwin.com>
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

namespace iMSCP\Model;

/**
 * Class SuIdentity
 * @package iMSCP\Model
 */
class SuIdentity implements SuIdentityInterface
{
    /**
     * @var UserIdentityInterface
     */
    protected $suIdentity;

    /**
     * @var UserIdentityInterface
     */
    protected $userIdentity;

    /**
     * @inheritdoc
     */
    public function __construct(UserIdentityInterface $suIdentity, UserIdentityInterface $userIdentity)
    {
        $this->suIdentity = $suIdentity;
        $this->userIdentity = $userIdentity;
    }

    /**
     * Get user unique identifier
     *
     * @return int
     */
    public function getUserId(): int
    {
        return $this->userIdentity->getUserId();
    }

    /**
     * Get user name
     *
     * @return string
     */
    public function getUsername(): string
    {
        return $this->userIdentity->getUsername();
    }

    /**
     * Get user password (hashed)
     * @return string
     */
    public function getUserPassword(): string
    {
        return $this->userIdentity->getUserPassword();
    }

    /**
     * Get user email address
     *
     * @return string
     */
    public function getUserEmail(): string
    {
        return $this->userIdentity->getUserEmail();
    }

    /**
     * Get user type
     *
     * @return string
     */
    public function getUserType(): string
    {
        return $this->userIdentity->getUserType();
    }

    /**
     * Get user creator unique identifier
     *
     * @return int
     */
    public function getUserCreatedBy(): int
    {
        return $this->userIdentity->getUserCreatedBy();
    }

    /**
     * get SU user unique identitfier
     *
     * @return int
     */
    public function getSuUserId(): int
    {
        return $this->suIdentity->getUserId();
    }

    /**
     * Get SU user name
     *
     * @return string
     */
    public function getSuUsername(): string
    {
        return $this->suIdentity->getUsername();
    }

    /**
     * Get SU user email
     *
     * @return string
     */
    public function getSuUserEmail(): string
    {
        return $this->suIdentity->getUserEmail();
    }

    /**
     * get SU user type
     *
     * @return string
     */
    public function getSuUserType(): string
    {
        return $this->suIdentity->getUserType();
    }

    /**
     * Get SU user creator unique identifier
     *
     * @return int
     */
    public function getSuUserCreatedBy(): int
    {
        return $this->suIdentity->getUserCreatedBy();
    }
}