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

namespace pocketmine\entity\passive;

use pocketmine\entity\behavior\FloatBehavior;
use pocketmine\entity\behavior\FollowOwnerBehavior;
use pocketmine\entity\behavior\LookAtPlayerBehavior;
use pocketmine\entity\behavior\MateBehavior;
use pocketmine\entity\behavior\PanicBehavior;
use pocketmine\entity\behavior\RandomLookAroundBehavior;
use pocketmine\entity\behavior\RandomStrollBehavior;
use pocketmine\entity\behavior\StayWhileSittingBehavior;
use pocketmine\entity\behavior\TemptBehavior;
use pocketmine\entity\Tamable;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\ActorEventPacket;
use pocketmine\network\mcpe\protocol\AddActorPacket;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\network\mcpe\protocol\SetActorDataPacket;
use pocketmine\network\mcpe\protocol\types\entity\PropertySyncData;
use pocketmine\network\mcpe\protocol\types\EntityLink;
use pocketmine\Player;

use function array_map;
use function intval;
use function is_array;
use function min;
use function mt_rand;
use function rand;

class Cat extends Tamable
{
	public const NETWORK_ID = self::CAT;

	public const COLOR_WHITE = 0;
	public const COLOR_TUXEDO = 1;
	public const COLOR_RED = 2;
	public const COLOR_SIAMESE = 3;
	public const COLOR_BRITISH_SHORTHAIR = 4;
	public const COLOR_CALICO = 5;
	public const COLOR_PERSIAN = 6;
	public const COLOR_RAGDOLL = 7;
	public const COLOR_TABBY = 8;
	public const COLOR_BLACK = 9;
	public const COLOR_JELLIE = 10;

	public float $width = 0.6;
	public float $height = 0.7;
	/** @var StayWhileSittingBehavior */
	protected $behaviorSitting;

	protected function addBehaviors() : void
	{
		$this->behaviorPool->setBehavior(0, new FloatBehavior($this));
		$this->behaviorPool->setBehavior(1, new PanicBehavior($this, 2.0));
		$this->behaviorPool->setBehavior(2, $this->behaviorSitting = new StayWhileSittingBehavior($this));
		$this->behaviorPool->setBehavior(3, new MateBehavior($this, 2.0));
		$this->behaviorPool->setBehavior(4, new TemptBehavior($this, [
			Item::RAW_SALMON,
			Item::RAW_FISH
		], 1.0));
		$this->behaviorPool->setBehavior(5, new FollowOwnerBehavior($this, 1, 10, 2));
		$this->behaviorPool->setBehavior(6, new RandomStrollBehavior($this, 0.8));
		$this->behaviorPool->setBehavior(7, new LookAtPlayerBehavior($this, 14.0));
		$this->behaviorPool->setBehavior(8, new RandomLookAroundBehavior($this));

		// TODO: attack turtle and rabbit
	}

	protected function initEntity() : void
	{
		$this->setMaxHealth(10);
		$this->setMovementSpeed(0.3);
		$this->setFollowRange(16);
		$this->setAttackDamage(3);
		$this->propertyManager->setInt(self::DATA_VARIANT, intval($this->namedtag->getInt("CatType", mt_rand(0, 10))));

		parent::initEntity();
	}

	public function getName() : string
	{
		return "Cat";
	}

	public function onInteract(Player $player, Item $item, Vector3 $clickPos) : bool
	{
		if (!$this->isImmobile()) {
			if ($item->getId() == Item::RAW_SALMON || $item->getId() == Item::RAW_FISH) {
				if ($player->isSurvival()) {
					$item->pop();
				}
				if ($this->isTamed()) {
					$this->setInLove(true);
					$this->setHealth(min($this->getMaxHealth(), $this->getHealth() + 2));
				} elseif (mt_rand(0, 2) == 0) {
					$this->setOwningEntity($player);
					$this->setTamed();
					$this->setSittingFromBehavior(true);
					$this->broadcastEntityEvent(ActorEventPacket::TAME_SUCCESS);
				} else {
					$this->broadcastEntityEvent(ActorEventPacket::TAME_FAIL);
				}
				return true;
			} else {
				if ($this->isTamed()) {
					$this->setSittingFromBehavior(!$this->isSitting());
				}
			}
		}
		return parent::onInteract($player, $item, $clickPos);
	}

	public function getXpDropAmount() : int
	{
		$damage = $this->getLastDamageCause();
		if ($damage instanceof EntityDamageByEntityEvent) {
			$damager = $damage->getDamager();
			if ($damager instanceof Player || ($damager instanceof Wolf && $damager->isTamed())) {
				return rand(1, ($this->isInLove() ? 7 : 3));
			}
		}
		return 0;
	}

	public function getDrops() : array
	{
		return [
			ItemFactory::get(Item::STRING, 0, rand(0, 2)),
		];
	}

	public function setSittingFromBehavior(bool $value) : void
	{
		$this->behaviorSitting->setSitting($value);
	}

	protected function sendSpawnPacket(Player $player) : void
	{
		$pk = new AddActorPacket();
		$pk->entityRuntimeId = $this->getId();
		$pk->type = ($player->getProtocolVersion() >= ProtocolInfo::PROTOCOL_313 ? static::NETWORK_ID : Ocelot::NETWORK_ID);
		$pk->position = $this->asVector3();
		$pk->motion = $this->getMotion();
		$pk->yaw = $this->yaw;
		$pk->headYaw = $this->headYaw ?? $this->yaw;
		$pk->bodyYaw = $this->yaw;
		$pk->pitch = $this->pitch;
		$pk->attributes = $this->attributeMap->getAll();
		$metadata = $this->propertyManager->getAll();
		if ($player->getProtocolVersion() < ProtocolInfo::PROTOCOL_313) {
			if (isset($metadata[self::DATA_VARIANT])) {
				$metadata[self::DATA_VARIANT][1] = self::translateToOcelotColor($metadata[self::DATA_VARIANT][1]);
			}
		}
		$pk->metadata = $metadata;
		$pk->syncedProperties = new PropertySyncData([], []);

		if (!empty($this->passengers)) {
			foreach ($this->getPassengers() as $passenger) {
				$passenger->spawnTo($player);
			}

			$pk->links = array_map(function (int $entityId) {
				return new EntityLink(
					$this->getId(),
					$entityId,
					EntityLink::TYPE_RIDER,
					true,
					false
				);
			}, $this->passengers);
		}

		$player->dataPacket($pk);
	}

	public function sendData($player, ?array $data = null) : void
	{
		if (!is_array($player)) {
			$player = [$player];
		}

		$pk = new SetActorDataPacket();
		$pk->entityRuntimeId = $this->getId();
		$pk->metadata = $data ?? $this->propertyManager->getAll();
		$pk->syncedProperties = new PropertySyncData([], []);

		foreach ($player as $p) {
			if (isset($pk->metadata[self::DATA_VARIANT])) {
				if ($p->getProtocolVersion() < ProtocolInfo::PROTOCOL_313) {
					$pk->metadata[self::DATA_VARIANT][1] = self::translateToOcelotColor($pk->metadata[self::DATA_VARIANT][1]);
				}
			}

			$p->dataPacket(clone $pk);
		}
	}

	protected static function translateToOcelotColor(int $color) : int
	{
		$newColor = Ocelot::TYPE_BLACK;
		// подходящие цвета подобрал
		// у кого +- белый цвет, тот стал сиамским оцелотом
		switch ($color) {
			case self::COLOR_WHITE:
				$newColor = Ocelot::TYPE_SIAMESE;
				break;
			case self::COLOR_TUXEDO:
				$newColor = Ocelot::TYPE_BLACK;
				break;
			case self::COLOR_RED:
				$newColor = Ocelot::TYPE_RED;
				break;
			case self::COLOR_SIAMESE:
				$newColor = Ocelot::TYPE_SIAMESE;
				break;
			case self::COLOR_BRITISH_SHORTHAIR:
				$newColor = Ocelot::TYPE_SIAMESE;
				break;
			case self::COLOR_CALICO:
				$newColor = Ocelot::TYPE_SIAMESE;
				break;
			case self::COLOR_PERSIAN:
				$newColor = Ocelot::TYPE_RED;
				break;
			case self::COLOR_RAGDOLL:
				$newColor = Ocelot::TYPE_SIAMESE;
				break;
			case self::COLOR_TABBY:
				$newColor = Ocelot::TYPE_RED;
				break;
			case self::COLOR_BLACK:
				$newColor = Ocelot::TYPE_BLACK;
				break;
			case self::COLOR_JELLIE:
				$newColor = Ocelot::TYPE_SIAMESE;
				break;
		}

		return $newColor;
	}
}
