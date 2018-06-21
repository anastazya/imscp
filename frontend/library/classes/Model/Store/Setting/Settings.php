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

namespace iMSCP\Model\Store\Setting;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\KeyValueStore\Mapping\Annotations as KeyValue;
use iMSCP\Model\Store\StoreAbstract;

/**
 * Class Settings
 * @package iMSCP\Model\Store
 * @KeyValue\Entity(storageName="imscp_storage")
 */
class Settings extends StoreAbstract implements \IteratorAggregate
{
    /**
     * @var SettingInterface[]
     */
    private $settings = [];

    /**
     * Services constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->settings = new ArrayCollection();
    }

    /**
     * Return setting
     *
     * @param string $name
     * @return mixed|null
     */
    public function getSetting(string $name)
    {
        $setting = $this->settings->get($name);
        if (NULL === $this->settings) {
            throw new \RuntimeException(sprintf("Couldn't find setting by name: %s", $name));
        }

        return $setting;
    }

    /**
     * Add a setting
     *
     * @param SettingInterface $setting
     * @return Settings
     */
    public function addSetting(SettingInterface $setting): Settings
    {
        $this->settings[$setting->getName()] = $setting;
        return $this;
    }

    /**
     * Remove a setting
     *
     * @param SettingInterface $setting
     * @return Settings
     */
    public function removeSetting(SettingInterface $setting): Settings
    {
        $this->settings->remove($setting->getName());
        return $this;
    }

    /**
     * Remove a setting by name
     *
     * @param string $name
     * @return Settings
     */
    public function removeSettingByName(string $name): Settings
    {
        $this->settings->remove($name);
        return $this;
    }

    /**
     * @return \ArrayIterator
     */
    public function getIterator(): \ArrayIterator
    {
        return $this->settings->getIterator();
    }
}
