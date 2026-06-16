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

namespace pocketmine\inventory\transaction\action;

use pocketmine\event\inventory\InventoryClickEvent;
use pocketmine\inventory\Inventory;
use pocketmine\inventory\transaction\InventoryTransaction;
use pocketmine\item\Item;
use pocketmine\Player;

use function spl_object_hash;

/**
 * Represents an action causing a change in an inventory slot.
 */
class SlotChangeAction extends InventoryAction
{
	protected Inventory $inventory;
	protected int $inventorySlot;

	public function __construct(Inventory $inventory, int $inventorySlot, Item $sourceItem, Item $targetItem)
	{
		parent::__construct($sourceItem, $targetItem);
		$this->inventory = $inventory;
		$this->inventorySlot = $inventorySlot;
	}

	/**
	 * Returns the inventory involved in this action.
	 */
	public function getInventory() : Inventory
	{
		return $this->inventory;
	}

	/**
	 * Returns the slot in the inventory which this action modified.
	 */
	public function getSlot() : int
	{
		return $this->inventorySlot;
	}

	/**
	 * Checks if the item in the inventory at the specified slot is the same as this action's source item.
	 */
	public function isValid(Player $source) : bool
	{
		return (
			$this->inventory->slotExists($this->inventorySlot) &&
			$this->inventory->getItem($this->inventorySlot)->equalsExact($this->sourceItem)
		);
	}

	public function onPreExecute(Player $source) : bool
	{
		$ev = new InventoryClickEvent($this->inventory, $source, $this->inventorySlot, $this->sourceItem);
		$ev->call();
		return !$ev->isCancelled();
	}

	/**
	 * Adds this action's target inventory to the transaction's inventory list.
	 */
	public function onAddToTransaction(InventoryTransaction $transaction) : void
	{
		$transaction->addInventory($this->inventory);
	}

	/**
	 * Sets the item into the target inventory.
	 */
	public function execute(Player $source) : bool
	{
		return $this->inventory->setItem($this->inventorySlot, $this->targetItem, false);
	}

	/**
	 * Sends slot changes to other viewers of the inventory. This will not send any change back to the source Player.
	 */
	public function onExecuteSuccess(Player $source) : void
	{
		$viewers = $this->inventory->getViewers();
		unset($viewers[spl_object_hash($source)]);
		$this->inventory->sendSlot($this->inventorySlot, $viewers);
	}

	/**
	 * Sends the original slot contents to the source player to revert the action.
	 */
	public function onExecuteFail(Player $source) : void
	{
		$this->inventory->sendSlot($this->inventorySlot, $source);
	}
}
