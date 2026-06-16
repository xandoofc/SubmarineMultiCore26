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

namespace pocketmine\entity\object;

use pocketmine\block\Water;
use pocketmine\entity\Entity;
use pocketmine\event\entity\ItemDespawnEvent;
use pocketmine\event\entity\ItemSpawnEvent;
use pocketmine\event\inventory\InventoryPickupItemEvent;
use pocketmine\item\Item;
use pocketmine\math\AxisAlignedBB;
use pocketmine\network\mcpe\protocol\ActorEventPacket;
use pocketmine\network\mcpe\protocol\AddItemActorPacket;
use pocketmine\network\mcpe\protocol\TakeItemActorPacket;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStackWrapper;
use pocketmine\Player;
use pocketmine\timings\Timings;
use UnexpectedValueException;

use function get_class;

class ItemEntity extends Entity
{
	public const NETWORK_ID = self::ITEM;

	/** @var string */
	protected $owner = "";
	/** @var string */
	protected $thrower = "";
	/** @var int */
	protected $pickupDelay = 0;
	/** @var Item */
	protected $item;

	public float $width = 0.25;
	public float $height = 0.25;
	protected float $baseOffset = 0.125;

	protected $gravity = 0.04;
	protected $drag = 0.02;

	public bool $canCollide = true;

	/** @var int */
	protected $age = 0;

	protected function initEntity() : void
	{
		parent::initEntity();

		$this->setMaxHealth(5);
		$this->setImmobile(true);
		$this->setHealth($this->namedtag->getShort("Health", (int) $this->getHealth()));
		$this->age = $this->namedtag->getShort("Age", $this->age);
		$this->pickupDelay = $this->namedtag->getShort("PickupDelay", $this->pickupDelay);
		$this->owner = $this->namedtag->getString("Owner", $this->owner);
		$this->thrower = $this->namedtag->getString("Thrower", $this->thrower);

		$itemTag = $this->namedtag->getCompoundTag("Item");
		if ($itemTag === null) {
			throw new UnexpectedValueException("Invalid " . get_class($this) . " entity: expected \"Item\" NBT tag not found");
		}

		$this->item = Item::nbtDeserialize($itemTag);
		if ($this->item->isNull()) {
			throw new UnexpectedValueException("Item for " . get_class($this) . " is invalid");
		}
		(new ItemSpawnEvent($this))->call();
	}

	public function entityBaseTick(int $tickDiff = 1) : bool
	{
		if ($this->closed) {
			return false;
		}
		Timings::$itemEntityBaseTick->startTiming();
		try {
			$hasUpdate = parent::entityBaseTick($tickDiff);

			if (!$this->isFlaggedForDespawn() && $this->pickupDelay > -1 && $this->pickupDelay < 32767) { //Infinite delay
				$this->pickupDelay -= $tickDiff;
				if ($this->pickupDelay < 0) {
					$this->pickupDelay = 0;
				}

				if ($this->ticksLived % 25 === 0) {
					foreach ($this->level->getCollidingEntities($this->getBoundingBox()->expandedCopy(0.5, 1, 0.5), $this) as $entity) {
						if ($entity instanceof ItemEntity && !$entity->isFlaggedForDespawn()) {
							$item = $this->getItem();
							if ($item->getCount() < $item->getMaxStackSize()) {
								if ($entity->getItem()->equals($item, true, true)) {
									$nextAmount = $item->getCount() + $entity->getItem()->getCount();
									if ($nextAmount <= $item->getMaxStackSize()) {
										if ($this->ticksLived > $entity->ticksLived) {
											$entity->flagForDespawn();

											$item->setCount($nextAmount);
											$this->broadcastEntityEvent(ActorEventPacket::ITEM_ENTITY_MERGE, $nextAmount);
										} else {
											$this->flagForDespawn();

											$entity->getItem()->setCount($nextAmount);
											$entity->broadcastEntityEvent(ActorEventPacket::ITEM_ENTITY_MERGE, $nextAmount);
										}
									}
								}
							}
						}
					}
				}

				$this->age += $tickDiff;
				if ($this->age > 6000) {
					$ev = new ItemDespawnEvent($this);
					$ev->call();
					if ($ev->isCancelled()) {
						$this->age = 0;
					} else {
						$this->flagForDespawn();
						$hasUpdate = true;
					}
				}
			}

			return $hasUpdate;
		} finally {
			Timings::$itemEntityBaseTick->stopTiming();
		}
	}

	protected function tryChangeMovement() : void
	{
		$this->checkObstruction($this->x, $this->y, $this->z);
		parent::tryChangeMovement();
	}

	protected function applyDragBeforeGravity() : bool
	{
		return true;
	}

	protected function applyGravity() : void
	{
		if ($this->level->getBlockAt($this->getFloorX(), $this->getFloorY(), $this->getFloorZ()) instanceof Water) {
			$bb = $this->getBoundingBox();
			$waterCount = 0;

			for ($j = 0; $j < 5; ++$j) {
				$d1 = $bb->minY + ($bb->maxY - $bb->minY) * $j / 5 + 0.4;
				$d3 = $bb->minY + ($bb->maxY - $bb->minY) * ($j + 1) / 5 + 1;

				$bb2 = new AxisAlignedBB($bb->minX, $d1, $bb->minZ, $bb->maxX, $d3, $bb->maxZ);

				if ($this->level->isLiquidInBoundingBox($bb2, new Water())) {
					$waterCount += 0.2;
				}
			}

			if ($waterCount > 0) {
				$this->motion->y += 0.002 * ($waterCount * 2 - 1);
			} else {
				$this->motion->y -= $this->gravity;
			}
		} else {
			$this->motion->y -= $this->gravity;
		}
	}

	public function saveNBT() : void
	{
		parent::saveNBT();
		$this->namedtag->setTag($this->item->nbtSerialize(-1, "Item"));
		$this->namedtag->setShort("Health", (int) $this->getHealth());
		$this->namedtag->setShort("Age", $this->age);
		$this->namedtag->setShort("PickupDelay", $this->pickupDelay);
		if ($this->owner !== null) {
			$this->namedtag->setString("Owner", $this->owner);
		}
		if ($this->thrower !== null) {
			$this->namedtag->setString("Thrower", $this->thrower);
		}
	}

	public function getItem() : Item
	{
		return $this->item;
	}

	public function canCollideWith(Entity $entity) : bool
	{
		return parent::canCollideWith($entity) && $entity instanceof ItemEntity;
	}

	public function getPickupDelay() : int
	{
		return $this->pickupDelay;
	}

	public function setPickupDelay(int $delay) : void
	{
		$this->pickupDelay = $delay;
	}

	public function getOwner() : string
	{
		return $this->owner;
	}

	public function setOwner(string $owner) : void
	{
		$this->owner = $owner;
	}

	public function getThrower() : string
	{
		return $this->thrower;
	}

	public function setThrower(string $thrower) : void
	{
		$this->thrower = $thrower;
	}

	protected function sendSpawnPacket(Player $player) : void
	{
		$pk = new AddItemActorPacket();
		$pk->entityRuntimeId = $this->getId();
		$pk->position = $this->asVector3();
		$pk->motion = $this->getMotion();
		$pk->item = ItemStackWrapper::legacy($this->getItem());
		$pk->metadata = $this->propertyManager->getAll();

		$player->dataPacket($pk);
	}

	public function onCollideWithPlayer(Player $player) : void
	{
		if ($this->getPickupDelay() !== 0) {
			return;
		}

		$item = $this->getItem();
		$playerInventory = $player->getInventory();

		if ($player->isSurvival() && !$playerInventory->canAddItem($item)) {
			return;
		}

		$ev = new InventoryPickupItemEvent($playerInventory, $this);
		$ev->call();
		if ($ev->isCancelled()) {
			return;
		}

		$pk = new TakeItemActorPacket();
		$pk->eid = $player->getId();
		$pk->target = $this->getId();
		$this->server->broadcastPacket($this->getViewers(), $pk);

		$playerInventory->addItem(clone $item);
		$this->flagForDespawn();
	}
}
