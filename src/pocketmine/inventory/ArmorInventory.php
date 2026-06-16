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

use pocketmine\entity\Living;
use pocketmine\item\Armor;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\network\mcpe\protocol\ContainerSetContentPacket;
use pocketmine\network\mcpe\protocol\ContainerSetSlotPacket;
use pocketmine\network\mcpe\protocol\InventoryContentPacket;
use pocketmine\network\mcpe\protocol\InventorySlotPacket;
use pocketmine\network\mcpe\protocol\MobArmorEquipmentPacket;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStackWrapper;
use pocketmine\Player;

use function array_map;
use function array_merge;

class ArmorInventory extends BaseInventory
{
	public const SLOT_HEAD = 0;
	public const SLOT_CHEST = 1;
	public const SLOT_LEGS = 2;
	public const SLOT_FEET = 3;

	/** @var Living */
	protected $holder;

	public function __construct(Living $holder)
	{
		$this->holder = $holder;
		parent::__construct();
	}

	public function getHolder() : Living
	{
		return $this->holder;
	}

	public function getName() : string
	{
		return "Armor";
	}

	public function getDefaultSize() : int
	{
		return 4;
	}

	public function getHelmet() : Item
	{
		return $this->getItem(self::SLOT_HEAD);
	}

	public function getChestplate() : Item
	{
		return $this->getItem(self::SLOT_CHEST);
	}

	public function getLeggings() : Item
	{
		return $this->getItem(self::SLOT_LEGS);
	}

	public function getBoots() : Item
	{
		return $this->getItem(self::SLOT_FEET);
	}

	public function setHelmet(Item $helmet) : bool
	{
		return $this->setItem(self::SLOT_HEAD, $helmet);
	}

	public function setChestplate(Item $chestplate) : bool
	{
		return $this->setItem(self::SLOT_CHEST, $chestplate);
	}

	public function setLeggings(Item $leggings) : bool
	{
		return $this->setItem(self::SLOT_LEGS, $leggings);
	}

	public function setBoots(Item $boots) : bool
	{
		return $this->setItem(self::SLOT_FEET, $boots);
	}

	public function setItem(int $index, Item $item, bool $send = true) : bool
	{
		if(
			($item instanceof Armor && $item->getArmorSlot() === $index) ||
			($index === self::SLOT_HEAD && ($item->getId() === ItemIds::SKULL || $item->getId() === ItemIds::PUMPKIN)) ||
			($index === self::SLOT_CHEST && $item->getId() === ItemIds::ELYTRA) ||
			$item->isNull()
		){
			return parent::setItem($index, $item, $send);
		}

		return false;
	}

	public function sendSlot(int $index, $target) : void
	{
		if ($target instanceof Player) {
			$target = [$target];
		}

		$pk = new MobArmorEquipmentPacket();
		$pk->entityRuntimeId = $this->getHolder()->getId();
		$pk->head = ItemStackWrapper::legacy($this->getHelmet());
		$pk->chest = ItemStackWrapper::legacy($this->getChestplate());
		$pk->legs = ItemStackWrapper::legacy($this->getLeggings());
		$pk->feet = ItemStackWrapper::legacy($this->getBoots());
		$pk->body = ItemStackWrapper::legacy(ItemFactory::get(Item::AIR));

		foreach ($target as $player) {
			if ($player === $this->getHolder()) {
				/** @var Player $player */

				if ($player->getProtocolVersion() >= ProtocolInfo::PROTOCOL_137) {
					$pk2 = new InventorySlotPacket();
					$pk2->windowId = $player->getWindowId($this);
					$pk2->inventorySlot = $index;
					$pk2->item = ItemStackWrapper::legacy($this->getItem($index));
					$pk2->storage = ItemStackWrapper::legacy(Item::get(Item::AIR));
					$player->dataPacket(clone $pk2);
				} else {
					$pk2 = new ContainerSetSlotPacket();
					$pk2->slot = $index;
					$pk2->item = ItemStackWrapper::legacy($this->getItem($index));
					$pk2->windowid = $player->getWindowId($this);
					$player->dataPacket(clone $pk2);
				}
			} else {
				$player->dataPacket(clone $pk);
			}
		}
	}

	public function sendContents($target) : void
	{
		if ($target instanceof Player) {
			$target = [$target];
		}

		$pk = new MobArmorEquipmentPacket();
		$pk->entityRuntimeId = $this->getHolder()->getId();
		$pk->head = ItemStackWrapper::legacy($this->getHelmet());
		$pk->chest = ItemStackWrapper::legacy($this->getChestplate());
		$pk->legs = ItemStackWrapper::legacy($this->getLeggings());
		$pk->feet = ItemStackWrapper::legacy($this->getBoots());
		$pk->body = ItemStackWrapper::legacy(ItemFactory::get(Item::AIR));

		foreach ($target as $player) {
			if ($player === $this->getHolder()) {
				if ($player->getProtocolVersion() >= ProtocolInfo::PROTOCOL_137) {
					$pk2 = new InventoryContentPacket();
					$pk2->windowId = $player->getWindowId($this);
					$pk2->items = array_map(fn (Item $item) => ItemStackWrapper::legacy($item), $this->getContents(true));
					$pk2->storage = ItemStackWrapper::legacy(Item::get(Item::AIR));
					$player->dataPacket(clone $pk2);
				} else {
					$pk2 = new ContainerSetContentPacket();
					$pk2->windowId = $player->getWindowId($this);
					$pk2->targetEid = $player->getId();
					$pk2->slots = array_map(fn (Item $item) => ItemStackWrapper::legacy($item), $this->getContents(true));
					$player->dataPacket(clone $pk2);
				}
			} else {
				$player->dataPacket(clone $pk);
			}
		}
	}

	public function onSlotChange(int $index, Item $before, bool $send) : void
	{
		$this->sendSlot($index, $this->getViewers());
	}

	/**
	 * @return Player[]
	 */
	public function getViewers() : array
	{
		return array_merge(parent::getViewers(), $this->holder->getViewers());
	}
}
