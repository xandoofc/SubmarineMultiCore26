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

use pocketmine\event\player\PlayerBlockPickEvent;
use pocketmine\item\ItemIds;
use pocketmine\Player;

use function abs;
use function implode;

/**
 * Represents an action causing a change in an inventory slot.
 */
class ContainerSlotChangeAction extends SlotChangeAction
{

	protected int $fails = 0;

	/**
	 * Sets the item into the target inventory.
	 */
	public function execute(Player $source) : bool
	{
		$craftingGird = $source->getCraftingGrid();
		$out = null;
		$in = null;

		if ($this->sourceItem->equalsExact($this->targetItem)) {
			//This should never happen, somehow a change happened where nothing changed
		} elseif ($this->sourceItem->equals($this->targetItem, true, true)) {
			$item = clone $this->sourceItem;
			$countDiff = $this->targetItem->getCount() - $this->sourceItem->getCount();
			$item->setCount(abs($countDiff));

			if ($countDiff < 0) { //Count decreased
				$out = $item;
			} elseif ($countDiff > 0) { //Count increased
				$in = $item;
			} else {
				//Should be impossible (identical items and no count change)
				//This should be caught by the first condition even if it was possible
			}
		} elseif ($this->sourceItem->getId() !== ItemIds::AIR && $this->targetItem->getId() === ItemIds::AIR) {
			//Slot emptied (item removed)
			$out = $this->sourceItem;
		} elseif ($this->sourceItem->getId() === ItemIds::AIR && $this->targetItem->getId() !== ItemIds::AIR) {
			//Slot filled (item added)
			$in = $this->targetItem;
		} else {
			//Some other slot change - an item swap (tool damage changes will be ignored as they are processed server-side before any change is sent by the client

			$out = $this->sourceItem;
			$in = $this->targetItem;
		}

		if ($out !== null) {
			if (!$this->inventory->getItem($this->getSlot())->equals($out, $out->hasAnyDamageValue(), !$out->hasNamedTag())) {
				$source->getServer()->getLogger()->debug("Player inventory not contains " . $out . " in slot " . $this->getSlot() . ". Have " . $this->getInventory()->getItem($this->getSlot()));
				return false;
			}
		}

		if ($in !== null) {
			$validIsInItem = function () use ($source, $craftingGird, $in) : bool {
				if ($craftingGird->contains($in)) {
					return true;
				}

				if ($source->isCreative(true)) {
					if ($source->getCreativeInventory()->contains($in)) {
						return true;
					}

					$targetBlock = $source->getTargetBlock(6);
					if ($targetBlock !== null && $targetBlock->getId() === $in->getId() && $targetBlock->getDamage() === $in->getDamage()) {
						$ev = new PlayerBlockPickEvent($source, $targetBlock, $in);
						$ev->call();
						if (!$ev->isCancelled()) {
							return true;
						}
					}
				}

				return false;
			};

			if (!$validIsInItem()) {
				$source->getServer()->getLogger()->debug("Transaction inventory not contains " . $in . ". Transaction inventory contents: " . implode("; ", $craftingGird->getContents()));
				return false;
			}
		}

		if ($out !== null) {
			$craftingGird->addItem($out);
		}

		if ($in !== null) {
			$craftingGird->removeItem($in);
		}

		return $this->inventory->setItem($this->inventorySlot, $this->targetItem, false);
	}

	public function onExecuteFail(Player $source) : void{
		if (++$this->fails >= 5) {
			parent::onExecuteFail($source);
			return;
		}

		$source->addInventoryTransactionActions($this);
	}
}
