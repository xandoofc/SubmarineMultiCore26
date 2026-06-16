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

namespace pocketmine\network\mcpe\protocol\types\inventory;

use InvalidArgumentException;
use InvalidStateException;
use pocketmine\inventory\BeaconInventory;
use pocketmine\inventory\CraftingGrid;
use pocketmine\inventory\EnchantInventory;
use pocketmine\inventory\FakeInventory;
use pocketmine\inventory\FakeResultInventory;
use pocketmine\inventory\transaction\action\CreativeInventoryAction;
use pocketmine\inventory\transaction\action\DropItemAction;
use pocketmine\inventory\transaction\action\EnchantAction;
use pocketmine\inventory\transaction\action\InventoryAction;
use pocketmine\inventory\transaction\action\SlotChangeAction;
use pocketmine\network\mcpe\NetworkBinaryStream;
use pocketmine\network\mcpe\protocol\PacketDecodeException;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\Player;
use UnexpectedValueException;
use function get_class;

class NetworkInventoryAction
{
	public const SOURCE_CONTAINER = 0;

	public const SOURCE_WORLD = 2; //drop/pickup item entity
	public const SOURCE_CREATIVE = 3;
	public const SOURCE_UNTRACKED_INTERACTION_UI = 100;
	public const SOURCE_TODO = 99999;

	/**
	 * Fake window IDs for the SOURCE_TODO type (99999)
	 *
	 * These identifiers are used for inventory source types which are not currently implemented server-side in MCPE.
	 * As a general rule of thumb, anything that doesn't have a permanent inventory is client-side. These types are
	 * to allow servers to track what is going on in client-side windows.
	 *
	 * Expect these to change in the future.
	 */
	public const SOURCE_TYPE_CRAFTING_ADD_INGREDIENT = -2;
	public const SOURCE_TYPE_CRAFTING_REMOVE_INGREDIENT = -3;
	public const SOURCE_TYPE_CRAFTING_RESULT = -4;
	public const SOURCE_TYPE_CRAFTING_USE_INGREDIENT = -5;

	public const SOURCE_TYPE_FAKE_INVENTORY_INPUT = -10;
	public const SOURCE_TYPE_FAKE_INVENTORY_MATERIAL = -11;
	public const SOURCE_TYPE_FAKE_INVENTORY_RESULT = -12;

	public const SOURCE_TYPE_ENCHANT_INPUT = -15;
	public const SOURCE_TYPE_ENCHANT_MATERIAL = -16;
	public const SOURCE_TYPE_ENCHANT_OUTPUT = -17;

	public const SOURCE_TYPE_TRADING_INPUT_1 = -20;
	public const SOURCE_TYPE_TRADING_INPUT_2 = -21;
	public const SOURCE_TYPE_TRADING_USE_INPUTS = -22;
	public const SOURCE_TYPE_TRADING_OUTPUT = -23;

	public const SOURCE_TYPE_BEACON = -24;

	/** Any client-side window dropping its contents when the player closes it */
	public const SOURCE_TYPE_CONTAINER_DROP_CONTENTS = -100;

	public const ACTION_MAGIC_SLOT_CREATIVE_DELETE_ITEM = 0;
	public const ACTION_MAGIC_SLOT_CREATIVE_CREATE_ITEM = 1;

	public const ACTION_MAGIC_SLOT_DROP_ITEM = 0;
	public const ACTION_MAGIC_SLOT_PICKUP_ITEM = 1;

	public int $sourceType;
	public int $windowId;
	public int $sourceFlags = 0;
	public int $inventorySlot;
	public ItemStackWrapper $oldItem;
	public ItemStackWrapper $newItem;
	public ?int $newItemStackId = null;

	/**
	 * @return $this
	 */
	public function read(NetworkBinaryStream $packet, bool $hasItemStackIds, int $protocol)
	{
		$this->sourceType = $packet->getUnsignedVarInt();

		switch ($this->sourceType) {
			case self::SOURCE_CONTAINER:
				$this->windowId = $packet->getVarInt();
				break;
			case self::SOURCE_WORLD:
				$this->sourceFlags = $packet->getUnsignedVarInt();
				break;
			case self::SOURCE_CREATIVE:
				break;
			case self::SOURCE_UNTRACKED_INTERACTION_UI:
			case self::SOURCE_TODO:
				$this->windowId = $packet->getVarInt();
				break;
			default:
				throw new PacketDecodeException("Unknown inventory action source type $this->sourceType");
		}

		$this->inventorySlot = $packet->getUnsignedVarInt();
		$this->oldItem = $packet->getSlot($protocol);
		$this->newItem = $packet->getSlot($protocol);

		if ($protocol >= ProtocolInfo::PROTOCOL_407 && $protocol < ProtocolInfo::PROTOCOL_431) {
			if ($hasItemStackIds) {
				$this->newItemStackId = $packet->readServerItemStackId();
			}
		}

		return $this;
	}

	/**
	 * @return void
	 */
	public function write(NetworkBinaryStream $packet, bool $hasItemStackIds, int $protocol)
	{
		$packet->putUnsignedVarInt($this->sourceType);

		switch ($this->sourceType) {
			case self::SOURCE_CONTAINER:
				$packet->putVarInt($this->windowId);
				break;
			case self::SOURCE_WORLD:
				$packet->putUnsignedVarInt($this->sourceFlags);
				break;
			case self::SOURCE_CREATIVE:
				break;
			case self::SOURCE_UNTRACKED_INTERACTION_UI:
			case self::SOURCE_TODO:
				$packet->putVarInt($this->windowId);
				break;
			default:
				throw new InvalidArgumentException("Unknown inventory action source type $this->sourceType");
		}

		$packet->putUnsignedVarInt($this->inventorySlot);
		$packet->putSlot($this->oldItem, $protocol);
		$packet->putSlot($this->newItem, $protocol);

		if ($protocol >= ProtocolInfo::PROTOCOL_407 && $protocol < ProtocolInfo::PROTOCOL_431) {
			if ($hasItemStackIds) {
				if ($this->newItemStackId === null) {
					throw new InvalidStateException("Item stack ID for newItem must be provided");
				}
				$packet->writeServerItemStackId($this->newItemStackId);
			}
		}
	}

	/**
	 * @return InventoryAction|null
	 *
	 * @deprecated
	 *
	 * @throws UnexpectedValueException
	 */
	public function createInventoryAction(Player $player)
	{
		$oldItem = $this->oldItem->getItemStack();
		$newItem = $this->newItem->getItemStack();
		if ($player->getProtocolVersion() >= ProtocolInfo::PROTOCOL_407 && $oldItem->equalsExact($newItem)) {
			//filter out useless noise in 1.13
			return null;
		}

		switch ($this->sourceType) {
			case self::SOURCE_CONTAINER:
				$slot = 0;
				if($this->inventorySlot === 27 && ($player->getWindow(Player::BEACON_WINDOW_ID) instanceof BeaconInventory)){
					$window = $player->getWindow(Player::BEACON_WINDOW_ID);
				}elseif($this->windowId === ContainerIds::UI && $this->inventorySlot > 0){
					if($this->inventorySlot === 50){
						return null; //useless noise
					}
					if($this->inventorySlot >= 28 && $this->inventorySlot <= 31){
						$window = $player->getCraftingGrid();
						if($window->getGridWidth() !== CraftingGrid::SIZE_SMALL){
							throw new UnexpectedValueException("Expected small crafting grid");
						}
						$slot = $this->inventorySlot - 28;
					}elseif($this->inventorySlot >= 32 && $this->inventorySlot <= 40){
						$window = $player->getCraftingGrid();
						if($window->getGridWidth() !== CraftingGrid::SIZE_BIG){
							throw new UnexpectedValueException("Expected big crafting grid");
						}
						$slot = $this->inventorySlot - 32;
					}else{
						throw new UnexpectedValueException("Unhandled magic UI slot offset $this->inventorySlot");
					}
				}else{
					$window = $player->getWindow($this->windowId);
					$slot = $this->inventorySlot;
				}

				if($window !== null){
					return new SlotChangeAction($window, $slot, $oldItem, $newItem);
				}

				throw new UnexpectedValueException("Player " . $player->getName() . " has no open container with window ID $this->windowId");
			case self::SOURCE_WORLD:
				if ($this->inventorySlot !== self::ACTION_MAGIC_SLOT_DROP_ITEM) {
					throw new PacketDecodeException("Only expecting drop-item world actions from the client!");
				}

				return new DropItemAction($newItem);
			case self::SOURCE_CREATIVE:
				switch ($this->inventorySlot) {
					case self::ACTION_MAGIC_SLOT_CREATIVE_DELETE_ITEM:
						$type = CreativeInventoryAction::TYPE_DELETE_ITEM;
						break;
					case self::ACTION_MAGIC_SLOT_CREATIVE_CREATE_ITEM:
						$type = CreativeInventoryAction::TYPE_CREATE_ITEM;
						break;
					default:
						throw new PacketDecodeException("Unexpected creative action type $this->inventorySlot");

				}

				return new CreativeInventoryAction($oldItem, $newItem, $type);
			case self::SOURCE_UNTRACKED_INTERACTION_UI:
			case self::SOURCE_TODO:
				$window = $player->findWindow(FakeInventory::class);

				switch ($this->windowId) {
					case self::SOURCE_TYPE_CRAFTING_ADD_INGREDIENT:
					case self::SOURCE_TYPE_CRAFTING_REMOVE_INGREDIENT:
					case self::SOURCE_TYPE_CONTAINER_DROP_CONTENTS: //TODO: this type applies to all fake windows, not just crafting
						return new SlotChangeAction($window ?? $player->getCraftingGrid(), $this->inventorySlot, $oldItem, $newItem);
					case self::SOURCE_TYPE_CRAFTING_RESULT:
					case self::SOURCE_TYPE_CRAFTING_USE_INGREDIENT:
						return null;
					case self::SOURCE_TYPE_ENCHANT_OUTPUT:
						if ($window instanceof EnchantInventory) {
							return new EnchantAction($window, $this->inventorySlot, $oldItem, $newItem);
						} else {
							if ($window === null) {
								throw new PacketDecodeException("Window not found");
							} else {
								throw new PacketDecodeException("Unexpected fake inventory given. Expected " . EnchantInventory::class . " , given " . get_class($window));
							}
						}
						// no break
					case self::SOURCE_TYPE_FAKE_INVENTORY_INPUT:
					case self::SOURCE_TYPE_FAKE_INVENTORY_MATERIAL:
						return null; // useless noise
					case self::SOURCE_TYPE_FAKE_INVENTORY_RESULT:
						if ($window instanceof FakeResultInventory) {
							if (!$window->onResult($player, $oldItem)) {
								$player->getInventory()->sendContents($player);

								throw new PacketDecodeException("Output doesnt match for Player " . $player->getName() . " in " . get_class($window));
							}
						}

						return null;
				}

				throw new PacketDecodeException("Player " . $player->getName() . " has no open container with window ID $this->windowId");
			default:
				throw new PacketDecodeException("Unknown inventory source type $this->sourceType");
		}
	}
}
