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

namespace pocketmine\network\mcpe\cache;

use pocketmine\item\Durable;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\network\mcpe\protocol\types\inventory\CreativeGroupEntry;
use pocketmine\network\mcpe\protocol\types\inventory\CreativeItemEntry;
use pocketmine\utils\Filesystem;
use pocketmine\utils\SingletonTrait;

use function array_diff;
use function base64_decode;
use function count;
use function hex2bin;
use function json_decode;
use function krsort;
use function scandir;
use const pocketmine\BEDROCK_DATA_PATH;

final class CreativeItemsCache
{
	use SingletonTrait;

	private static function make() : self
	{
		$groups = $items = [];

		$groupsDirectory = BEDROCK_DATA_PATH . 'creative_groups/';
		$itemsDirectory = BEDROCK_DATA_PATH . 'creative_items/';

		foreach (array_diff(scandir($groupsDirectory), ["..", "."]) as $protocol) {
			$itemsProtocol = json_decode(Filesystem::fileGetContents($groupsDirectory . $protocol . '/creative_group.json'), true);

			$creativeGroupEntry = [];
			foreach ($itemsProtocol as $group) {
				if (isset($group["icon"])) {
					$icon = $group["icon"];
					$item = Item::get((int) $icon["id"], (int) ($icon["damage"] ?? 0));
				} else {
					$item = ItemFactory::air();
				}

				$creativeGroupEntry[] = new CreativeGroupEntry(
					(int) $group["category_id"],
					$group["category_name"],
					$item
				);

			}

			$groups[$protocol] = $creativeGroupEntry;
		}

		foreach (array_diff(scandir($itemsDirectory), ["..", "."]) as $protocol) {
			$itemsProtocol = json_decode(Filesystem::fileGetContents($itemsDirectory . $protocol . '/creative_items.json'), true);

			$creativeItemEntry = [];
			foreach ($itemsProtocol as $itemJson) {
				$id = (int) $itemJson["id"];
				$meta = (int) ($itemJson["damage"] ?? 0);

				$nbt = "";

				//Backwards compatibility
				if (isset($itemJson["nbt"])) {
					$nbt = $itemJson["nbt"];
				} elseif (isset($itemJson["nbt_hex"])) {
					$nbt = hex2bin($itemJson["nbt_hex"]);
				} elseif (isset($itemJson["nbt_b64"])) {
					$nbt = base64_decode($itemJson["nbt_b64"], true);
				}

				$groupId = (int) ($itemJson["groupId"] ?? 0);

				$item = Item::get($id, $meta, 1, $nbt);
				if ($item->getName() === "Unknown") {
					continue;
				}

				$creativeItemEntry[] = new CreativeItemEntry(
					count($creativeItemEntry) + 1,
					$item,
					$groupId
				);
			}

			$items[$protocol] = $creativeItemEntry;
		}

		krsort($groups);
		krsort($items);

		return new self($groups, $items);
	}

	/**
	 * @param CreativeGroupEntry[][] $groups
	 * @param CreativeItemEntry[][]  $items
	 */
	public function __construct(
		private array $groups = [],
		private array $items = []
	) {
	}

	public function getGroups(int $protocolVersion) : array
	{
		foreach ($this->groups as $protocol => $groups) {
			if ($protocolVersion >= $protocol) {
				return $groups;
			}
		}

		return [];
	}

	public function getItems(int $protocolVersion) : array
	{
		foreach ($this->items as $protocol => $items) {
			if ($protocolVersion >= $protocol) {
				return $items;
			}
		}

		return [];
	}

	public function clearItems(?int $protocolVersion = null) : void
	{
		if ($protocolVersion === null) {
			foreach ($this->items as $protocol => $items) {
				if ($protocolVersion >= $protocol) {
					unset($this->items[$protocol]);
				}
			}
		} else {
			foreach ($this->items as $protocol => $items) {
				unset($this->items[$protocol]);
			}
		}

		krsort($this->items);
	}

	public function addItem(Item $item, ?int $groupId = null, ?int $protocolVersion = null) : void
	{
		$addItemClosure = fn (int $protocol, Item $item, ?int $groupId) => $this->items[$protocol][] = new CreativeItemEntry(count($this->items) + 1, $item, $groupId ?? 0);
		if ($protocolVersion !== null) {
			foreach ($this->items as $protocol => $items) {
				if ($protocolVersion >= $protocol) {
					$addItemClosure($protocol, $item, $groupId);
				}
			}
		} else {
			foreach ($this->items as $protocol => $items) {
				$addItemClosure($protocol, $item, $groupId);
			}
		}

		krsort($this->items);
	}

	public function removeItem(Item $item, ?int $protocolVersion = null) : void
	{
		if ($protocolVersion !== null) {
			foreach ($this->items as $protocol => $items) {
				if ($protocolVersion >= $protocol) {
					foreach ($items as $index => $itemEntry) {
						if ($item->equals($itemEntry->getItem(), !($item instanceof Durable))) {
							unset($this->items[$protocol][$index]);
						}
					}
				}
			}
		} else {
			foreach ($this->items as $protocol => $items) {
				foreach ($items as $index => $itemEntry) {
					if ($item->equals($itemEntry->getItem(), !($item instanceof Durable))) {
						unset($this->items[$protocol][$index]);
					}
				}
			}
		}

		krsort($this->items);
	}

	public function getItemIndex(Item $item, ?int $protocolVersion = null) : int
	{
		if ($protocolVersion !== null) {
			foreach ($this->items as $protocol => $items) {
				if ($protocolVersion >= $protocol) {
					foreach ($items as $index => $itemEntry) {
						if ($item->equals($itemEntry->getItem(), !($item instanceof Durable))) {
							return $index;
						}
					}
				}
			}
		} else {
			foreach ($this->items as $protocol => $items) {
				foreach ($items as $index => $itemEntry) {
					if ($item->equals($itemEntry->getItem(), !($item instanceof Durable))) {
						return $index;
					}
				}
			}
		}

		return -1;
	}

	public function sync(array $newItems) : void{
		foreach ($this->items as $items) {
			foreach ($items as $itemEntry) {
				$item = $itemEntry->getItem();

				if (($new = $newItems[ItemFactory::getListOffset($item->getId(), $item->getDamage())] ?? null) !== null) {
					$itemEntry->setItem($new);
				}
			}
		}
	}
}
