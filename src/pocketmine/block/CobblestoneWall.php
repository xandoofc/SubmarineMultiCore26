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

use pocketmine\item\TieredTool;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Facing;

class CobblestoneWall extends Transparent
{
	public const NONE_MOSSY_WALL = 0;
	public const MOSSY_WALL = 1;
	public const GRANITE_WALL = 2;
	public const DIORITE_WALL = 3;
	public const ANDESITE_WALL = 4;
	public const SANDSTONE_WALL = 5;
	public const BRICK_WALL = 6;
	public const STONE_BRICK_WALL = 7;
	public const MOSSY_STONE_BRICK_WALL = 8;
	public const NETHER_BRICK_WALL = 9;
	public const END_STONE_BRICK_WALL = 10;
	public const PRISMARINE_WALL = 11;
	public const RED_SANDSTONE_WALL = 12;
	public const RED_NETHER_BRICK_WALL = 13;

	protected $id = self::COBBLESTONE_WALL;

	public function __construct(int $meta = 0)
	{
		$this->meta = $meta;
	}

	public function getToolType() : int
	{
		return BlockToolType::TYPE_PICKAXE;
	}

	public function getToolHarvestLevel() : int
	{
		return TieredTool::TIER_WOODEN;
	}

	public function getHardness() : float
	{
		return 2;
	}

	public function getName() : string
	{
		static $names = [
			self::NONE_MOSSY_WALL => "Cobblestone",
			self::MOSSY_WALL => "Mossy Cobblestone",
			self::GRANITE_WALL => "Granite",
			self::DIORITE_WALL => "Diorite",
			self::ANDESITE_WALL => "Andesite",
			self::SANDSTONE_WALL => "Sandstone",
			self::BRICK_WALL => "Brick",
			self::STONE_BRICK_WALL => "Stone Brick",
			self::MOSSY_STONE_BRICK_WALL => "Mossy Stone Brick",
			self::NETHER_BRICK_WALL => "Nether Brick",
			self::END_STONE_BRICK_WALL => "End Stone Brick",
			self::PRISMARINE_WALL => "Prismarine",
			self::RED_SANDSTONE_WALL => "Red Sandstone",
			self::RED_NETHER_BRICK_WALL => "Red Nether Brick"
		];
		return ($names[$this->getVariant()] ?? "Unknown") . " Wall";
	}

	protected function recalculateBoundingBox() : ?AxisAlignedBB
	{
		//walls don't have any special collision boxes like fences do

		$north = $this->canConnect($this->getSide(Facing::NORTH));
		$south = $this->canConnect($this->getSide(Facing::SOUTH));
		$west = $this->canConnect($this->getSide(Facing::WEST));
		$east = $this->canConnect($this->getSide(Facing::EAST));

		$inset = 0.25;
		if (
			$this->getSide(Facing::UP)->getId() === Block::AIR && //if there is a block on top, it stays as a post
			(
				($north && $south && !$west && !$east) ||
				(!$north && !$south && $west && $east)
			)
		) {
			//If connected to two sides on the same axis but not any others, AND there is not a block on top, there is no post and the wall is thinner
			$inset = 0.3125;
		}

		return new AxisAlignedBB(
			$this->x + ($west ? 0 : $inset),
			$this->y,
			$this->z + ($north ? 0 : $inset),
			$this->x + 1 - ($east ? 0 : $inset),
			$this->y + 1.5,
			$this->z + 1 - ($south ? 0 : $inset)
		);
	}

	/**
	 * @return bool
	 */
	public function canConnect(Block $block)
	{
		return $block instanceof static || $block instanceof FenceGate || ($block->isSolid() && !$block->isTransparent());
	}
}
