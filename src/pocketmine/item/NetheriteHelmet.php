<?php

/*
 *
 *   _____       _                          _
 *  / ____|     | |                        (_)
 * | (___  _   _| |__  _ __ ___   __ _ _ __ _ _ __   ___
 *  \___ \| | | | '_ \| '_ ` _ \ / _` | '__| | '_ \ / _ \
 *  ____) | |_| | |_) | | | | | | (_| | |  | | | | |  __/
 * |_____/ \__,_|_.__/|_| |_| |_|\__,_|_|  |_|_| |_|\___|
 *
 * This program is private software. No license required.
 * Publication of this program is forbidden and will be punished.
 *
 * @author SEMENNEJO
 * @link vk.com/vk.snikers && t.me/semennejo
 *
 *
 */

declare(strict_types=1);

namespace pocketmine\item;

use pocketmine\network\mcpe\protocol\ProtocolInfo;

class NetheriteHelmet extends Armor
{
	public function __construct(int $meta = 0)
	{
		parent::__construct(self::NETHERITE_HELMET, $meta, "Netherite Helmet");
	}

	public function getDefensePoints() : int
	{
		return 3;
	}

	public function getMaxDurability() : int
	{
		return 408;
	}

	public function getArmorSlot() : int
	{
		return 0;
	}

	public function getItemProtocol(int $playerProtocol) : ?Item
	{
		if ($playerProtocol < ProtocolInfo::PROTOCOL_407) {
			return Item::get(Item::DIAMOND_HELMET, $this->getDamage(), $this->getCount(), $this->getCompoundTag());
		}
		return parent::getItemProtocol($playerProtocol);
	}
}
