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

namespace pocketmine\block;

use pocketmine\item\Item;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Facing;
use pocketmine\math\Vector3;
use pocketmine\Player;

abstract class Slab extends Transparent
{
	public function __construct(int $meta = 0)
	{
		$this->meta = $meta;
	}

	abstract public function getDoubleSlabId() : int;

	public function canBePlacedAt(Block $blockReplace, Vector3 $clickVector, int $face, bool $isClickedBlock) : bool
	{
		if (parent::canBePlacedAt($blockReplace, $clickVector, $face, $isClickedBlock)) {
			return true;
		}

		if ($blockReplace->getId() === $this->getId() && $blockReplace->getVariant() === $this->getVariant()) {
			if (($blockReplace->getDamage() & $this->getVariantTopBitmask()) !== 0) { //Trying to combine with top slab
				return $clickVector->y <= 0.5 || (!$isClickedBlock && $face === Facing::UP);
			} else {
				return $clickVector->y >= 0.5 || (!$isClickedBlock && $face === Facing::DOWN);
			}
		}

		return false;
	}

	public function place(Item $item, Block $blockReplace, Block $blockClicked, int $face, Vector3 $clickVector, Player $player = null) : bool
	{
		$this->meta &= $this->getVariantBitmask();
		if ($face === Facing::DOWN) {
			if ($blockClicked->getId() === $this->id && ($blockClicked->getDamage() & $this->getVariantTopBitmask()) === $this->getVariantTopBitmask() && $blockClicked->getVariant() === $this->getVariant()) {
				$this->getLevel()->setBlock($blockClicked, BlockFactory::get($this->getDoubleSlabId(), $this->getVariant()), true);

				return true;
			} elseif ($blockReplace->getId() === $this->id && $blockReplace->getVariant() === $this->getVariant()) {
				$this->getLevel()->setBlock($blockReplace, BlockFactory::get($this->getDoubleSlabId(), $this->getVariant()), true);

				return true;
			} else {
				$this->meta |= $this->getVariantTopBitmask();
			}
		} elseif ($face === Facing::UP) {
			if ($blockClicked->getId() === $this->id && ($blockClicked->getDamage() & $this->getVariantTopBitmask()) === 0 && $blockClicked->getVariant() === $this->getVariant()) {
				$this->getLevel()->setBlock($blockClicked, BlockFactory::get($this->getDoubleSlabId(), $this->getVariant()), true);

				return true;
			} elseif ($blockReplace->getId() === $this->id && $blockReplace->getVariant() === $this->getVariant()) {
				$this->getLevel()->setBlock($blockReplace, BlockFactory::get($this->getDoubleSlabId(), $this->getVariant()), true);

				return true;
			}
		} else { //TODO: collision
			if ($blockReplace->getId() === $this->id) {
				if ($blockReplace->getVariant() === $this->getVariant()) {
					$this->getLevel()->setBlock($blockReplace, BlockFactory::get($this->getDoubleSlabId(), $this->getVariant()), true);

					return true;
				}

				return false;
			} else {
				if ($clickVector->y > 0.5) {
					$this->meta |= $this->getVariantTopBitmask();
				}
			}
		}

		if ($blockReplace->getId() === $this->id && $blockClicked->getVariant() !== $this->getVariant()) {
			return false;
		}
		$this->getLevel()->setBlock($blockReplace, $this, true, true);

		return true;
	}

	public function getVariantBitmask() : int
	{
		return 0x07;
	}

	public function getVariantTopBitmask() : int
	{
		return 0x08;
	}

	protected function recalculateBoundingBox() : ?AxisAlignedBB
	{
		if (($this->meta & $this->getVariantTopBitmask()) > 0) { //up slab
			return new AxisAlignedBB(
				$this->x,
				$this->y + 0.5,
				$this->z,
				$this->x + 1,
				$this->y + 1,
				$this->z + 1
			);
		} else {
			return new AxisAlignedBB(
				$this->x,
				$this->y,
				$this->z,
				$this->x + 1,
				$this->y + 0.5,
				$this->z + 1
			);
		}
	}

	public function isPassable() : bool
	{
		return ($this->meta & $this->getVariantTopBitmask()) < 0;
	}
}
