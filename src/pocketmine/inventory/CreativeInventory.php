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

namespace pocketmine\inventory;

use pocketmine\item\Item;
use pocketmine\network\mcpe\cache\CreativeItemsCache;
use pocketmine\network\mcpe\protocol\types\inventory\CreativeItemEntry;
use pocketmine\Player;
use function array_map;
use function count;

class CreativeInventory extends BaseInventory {

	protected int $size = 0;

	public function __construct(Player $player){
		$items = array_map(fn(CreativeItemEntry $entry) => $entry->getItem(), CreativeItemsCache::getInstance()->getItems($player->getProtocolVersion()));
		parent::__construct($items, ($this->size = count($items) + 1));
	}

	public function getName() : string{
		return "Creative";
	}

	public function contains(Item $item) : bool{
		$checkDamage = !$item->hasAnyDamageValue();
		$checkTags = $item->hasCompoundTag();
		foreach ($this->getContents() as $i) {
			if ($item->equals($i, $checkDamage, $checkTags)) {
				return true;
			}
		}
		return false;
	}

	public function getDefaultSize() : int{
		return $this->size;
	}
}
