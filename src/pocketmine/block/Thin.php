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

use pocketmine\math\Axis;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Facing;

use function count;

abstract class Thin extends Transparent
{
	protected function recalculateCollisionBoxes() : array
	{
		$inset = 7 / 16;

		/** @var AxisAlignedBB[] $bbs */
		$bbs = [];

		$connectWest = $this->canConnect($this->getSide(Facing::WEST));
		$connectEast = $this->canConnect($this->getSide(Facing::EAST));

		if ($connectWest || $connectEast) {
			$bb = AxisAlignedBB::one()->squash(Axis::Z, $inset);

			if (!$connectWest) {
				$bb->trim(Facing::WEST, $inset);
			} elseif (!$connectEast) {
				$bb->trim(Facing::EAST, $inset);
			}
			$bbs[] = $bb;
		}

		$connectNorth = $this->canConnect($this->getSide(Facing::NORTH));
		$connectSouth = $this->canConnect($this->getSide(Facing::SOUTH));

		if ($connectNorth || $connectSouth) {
			$bb = AxisAlignedBB::one()->squash(Axis::X, $inset);

			if (!$connectNorth) {
				$bb->trim(Facing::NORTH, $inset);
			} elseif (!$connectSouth) {
				$bb->trim(Facing::SOUTH, $inset);
			}
			$bbs[] = $bb;
		}

		if (count($bbs) === 0) {
			//centre post AABB (only needed if not connected on any axis - other BBs overlapping will do this if any connections are made)
			return [
				AxisAlignedBB::one()->contract($inset, 0, $inset)
			];
		}

		return $bbs;
	}

	public function canConnect(Block $block) : bool
	{
		if ($block instanceof Thin) {
			return true;
		}

		//FIXME: currently there's no proper way to tell if a block is a full-block, so we check the bounding box size
		$bb = $block->getBoundingBox();
		return $bb !== null && $bb->getAverageEdgeLength() >= 1;
	}
}
