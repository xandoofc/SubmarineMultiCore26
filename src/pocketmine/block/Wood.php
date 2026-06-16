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

use pocketmine\network\mcpe\protocol\ProtocolInfo;

class Wood extends Solid
{

	public const OAK = 0;
	public const SPRUCE = 1;
	public const BIRCH = 2;
	public const JUNGLE = 3;
	public const ACACIA = 4;
	public const DARK_OAK = 5;

	public const STRIPPED_OAK = 8;
	public const STRIPPED_SPRUCE = 9;
	public const STRIPPED_BIRCH = 10;
	public const STRIPPED_JUNGLE = 11;
	public const STRIPPED_ACACIA = 12;
	public const STRIPPED_DARK_OAK = 13;

	protected $id = self::WOOD;

	public function __construct(int $meta = 0)
	{
		$this->meta = $meta;
	}

	public function getName() : string
	{
		static $names = [
			self::OAK => "Oak Wood",
			self::SPRUCE => "Spruce Wood",
			self::BIRCH => "Birch Wood",
			self::JUNGLE => "Jungle Wood",
			self::ACACIA => "Acacia Wood",
			self::DARK_OAK => "Dark Oak Wood",
			self::STRIPPED_OAK => "Stripped Oak Wood",
			self::STRIPPED_SPRUCE => "Stripped Spruce Wood",
			self::STRIPPED_BIRCH => "Stripped Birch Wood",
			self::STRIPPED_JUNGLE => "Stripped Jungle Wood",
			self::STRIPPED_ACACIA => "Stripped Acacia Wood",
			self::STRIPPED_DARK_OAK => "Stripped Dark Oak Wood",
		];
		return $names[$this->getDamage()] ?? "Unknown";
	}

	public function getHardness() : float
	{
		return 2;
	}

	public function getToolType() : int
	{
		return BlockToolType::TYPE_AXE;
	}

	public function getFuelTime() : int
	{
		return 300;
	}

	public function getFlameEncouragement() : int
	{
		return 5;
	}

	public function getFlammability() : int
	{
		return 5;
	}

	public function getBlockProtocol(int $playerProtocol) : ?Block
	{
		if ($playerProtocol < ProtocolInfo::PROTOCOL_340) {
			return Block::get(Block::LOG, 0);
		}
		return parent::getBlockProtocol($playerProtocol);
	}
}
