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

use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\enchantment\ProtectionEnchantment;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\IntTag;
use pocketmine\Player;
use pocketmine\utils\Binary;
use pocketmine\utils\Color;
use pocketmine\utils\Utils;

use function mt_rand;

abstract class Armor extends Durable
{
	public const SLOT_HELMET = 0;
	public const SLOT_CHESTPLATE = 1;
	public const SLOT_LEGGINGS = 2;
	public const SLOT_BOOTS = 3;

	public const TAG_CUSTOM_COLOR = "customColor"; //TAG_Int

	public function getMaxStackSize() : int
	{
		return 1;
	}

	abstract public function getArmorSlot() : int;

	/**
	 * Returns the dyed colour of this armour piece. This generally only applies to leather armour.
	 */
	public function getCustomColor() : ?Color
	{
		if ($this->getNamedTag()->hasTag(self::TAG_CUSTOM_COLOR, IntTag::class)) {
			return Color::fromARGB(Binary::unsignInt($this->getNamedTag()->getInt(self::TAG_CUSTOM_COLOR)));
		}

		return null;
	}

	/**
	 * Sets the dyed colour of this armour piece. This generally only applies to leather armour.
	 */
	public function setCustomColor(Color $color) : void
	{
		$this->setNamedTagEntry(new IntTag(self::TAG_CUSTOM_COLOR, Binary::signInt($color->toARGB())));
	}

	public function clearCustomColor() : void
	{
		$this->removeNamedTagEntry(self::TAG_CUSTOM_COLOR);
	}

	/**
	 * Returns the total enchantment protection factor this armour piece offers from all applicable protection
	 * enchantments on the item.
	 */
	public function getEnchantmentProtectionFactor(EntityDamageEvent $event) : int
	{
		$epf = 0;

		foreach ($this->getEnchantments() as $enchantment) {
			$type = $enchantment->getType();
			if ($type instanceof ProtectionEnchantment && $type->isApplicable($event)) {
				$epf += $type->getProtectionFactor($enchantment->getLevel());
			}
		}

		return $epf;
	}

	protected function getUnbreakingDamageReduction(int $amount) : int
	{
		if (($unbreakingLevel = $this->getEnchantmentLevel(Enchantment::UNBREAKING)) > 0) {
			$negated = 0;

			$chance = 1 / ($unbreakingLevel + 1);
			for ($i = 0; $i < $amount; ++$i) {
				if (mt_rand(1, 100) > 60 && Utils::getRandomFloat() > $chance) { //unbreaking only applies to armor 40% of the time at best
					$negated++;
				}
			}

			return $negated;
		}

		return 0;
	}

	public function onClickAir(Player $player, Vector3 $directionVector) : bool
	{
		$current = $player->getArmorInventory()->getItem($this->getArmorSlot());
		if ($current->isNull()) {
			$player->getArmorInventory()->setItem($this->getArmorSlot(), $this->pop());

			return true;
		} elseif (!$current->equals($this) && $player->getInventory()->canAddItem($current)) {
			$player->getArmorInventory()->setItem($this->getArmorSlot(), $this->pop());
			$player->getInventory()->addItem($current);

			return true;
		}

		return false;
	}
}
