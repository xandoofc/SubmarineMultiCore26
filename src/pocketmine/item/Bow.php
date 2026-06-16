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

use pocketmine\entity\Entity;
use pocketmine\entity\projectile\Arrow as ArrowEntity;
use pocketmine\entity\projectile\Projectile;
use pocketmine\event\entity\EntityShootBowEvent;
use pocketmine\event\entity\ProjectileLaunchEvent;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\Player;

use function intdiv;
use function min;

class Bow extends Tool implements Releasable
{
	public function __construct(int $meta = 0)
	{
		parent::__construct(self::BOW, $meta, "Bow");
	}

	public function getFuelTime() : int
	{
		return 200;
	}

	public function getMaxDurability() : int
	{
		return 385;
	}

	public function onReleaseUsing(Player $player) : bool
	{
		$inventory = null;
		if ($player->getOffHandInventory()->contains(ItemFactory::get(Item::ARROW, -1))) {
			$inventory = $player->getOffHandInventory();
		} elseif ($player->getInventory()->contains(ItemFactory::get(Item::ARROW, -1))) {
			$inventory = $player->getInventory();
		}

		if ($player->isSurvival() && $inventory === null) {
			$player->getInventory()->sendContents($player);
			return false;
		}

		if ($inventory !== null) {
			$index = $inventory->first(ItemFactory::get(Item::ARROW, -1));
			if ($index !== -1) {
				$arrow = $inventory->getItem($index);
				$arrow->setCount(1);
			} else {
				$inventory->sendContents($player);
				return false;
			}
		} else {
			$arrow = ItemFactory::get(Item::ARROW, 0, 1);
		}

		$nbt = Entity::createBaseNBT(
			$player->add(0, $player->getEyeHeight(), 0),
			$player->getDirectionVector(),
			($player->yaw > 180 ? 360 : 0) - $player->yaw,
			-$player->pitch
		);

		$diff = $player->getItemUseDuration();
		$p = $diff / 20;
		$baseForce = min((($p ** 2) + $p * 2) / 3, 1);

		$entity = Entity::createEntity("Arrow", $player->getLevel(), $nbt, $player, $baseForce >= 1);
		if ($entity instanceof Projectile) {
			$infinity = $this->hasEnchantment(Enchantment::INFINITY);
			if ($entity instanceof ArrowEntity) {
				if ($infinity) {
					$entity->setPickupMode(ArrowEntity::PICKUP_CREATIVE);
				}
				if (($punchLevel = $this->getEnchantmentLevel(Enchantment::PUNCH)) > 0) {
					$entity->setPunchKnockback($punchLevel);
				}
			}
			if (($powerLevel = $this->getEnchantmentLevel(Enchantment::POWER)) > 0) {
				$entity->setBaseDamage($entity->getBaseDamage() + (($powerLevel + 1) / 2));
			}
			if ($this->hasEnchantment(Enchantment::FLAME)) {
				$entity->setOnFire(intdiv($entity->getFireTicks(), 20) + 100);
			}
			$ev = new EntityShootBowEvent($player, $this, $entity, $baseForce * 3, 1.0);

			if ($baseForce < 0.1 || $diff < 5 || $player->isSpectator()) {
				$ev->setCancelled();
			}

			$ev->call();

			$entity = $ev->getProjectile(); //This might have been changed by plugins

			if ($ev->isCancelled()) {
				$entity->flagForDespawn();
				$player->getInventory()->sendContents($player);
			} else {
				$entity->entityShoot($player, 0.0, $ev->getForce(), $ev->getInaccuracy());
				if ($player->isSurvival()) {
					if (!$infinity) { //TODO: tipped arrows are still consumed when Infinity is applied
						if ($inventory !== null) {
							$inventory->removeItem($arrow);
						}
					}
					$this->applyDamage(1);
				}

				if ($entity instanceof Projectile) {
					$projectileEv = new ProjectileLaunchEvent($entity);
					$projectileEv->call();
					if ($projectileEv->isCancelled()) {
						$ev->getProjectile()->flagForDespawn();
					} else {
						$ev->getProjectile()->spawnToAll();
						$player->getLevel()->broadcastLevelSoundEvent($player, LevelSoundEventPacket::SOUND_BOW);
					}
				} else {
					$entity->spawnToAll();
				}
			}
		} else {
			$entity->spawnToAll();
		}

		return true;
	}

	public function canStartUsingItem(Player $player) : bool
	{
		return !$player->hasFiniteResources() || $player->getOffHandInventory()->contains($arrow = ItemFactory::get(ItemIds::ARROW)) || $player->getInventory()->contains($arrow);
	}
}
