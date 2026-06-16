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

namespace pocketmine\block\utils;

use pocketmine\utils\EnumTrait;

/**
 * This doc-block is generated automatically, do not modify it manually.
 * This must be regenerated whenever registry members are added, removed or changed.
 * @see build/generate-registry-annotations.php
 * @generate-registry-docblock
 *
 * @method static TreeType ACACIA()
 * @method static TreeType BIRCH()
 * @method static TreeType DARK_OAK()
 * @method static TreeType JUNGLE()
 * @method static TreeType OAK()
 * @method static TreeType SPRUCE()
 */
final class TreeType
{
	use EnumTrait {
		register as Enum_register;
		__construct as Enum___construct;
	}

	/** @var TreeType[] */
	private static array $numericIdMap = [];

	protected static function setup() : void
	{
		self::registerAll(
			new TreeType("oak", "Oak", 0),
			new TreeType("spruce", "Spruce", 1),
			new TreeType("birch", "Birch", 2),
			new TreeType("jungle", "Jungle", 3),
			new TreeType("acacia", "Acacia", 4),
			new TreeType("dark_oak", "Dark Oak", 5)
		);
	}

	protected static function register(TreeType $type) : void
	{
		self::Enum_register($type);
		self::$numericIdMap[$type->getMagicNumber()] = $type;
	}

	/**
	 * @internal
	 *
	 * @throws \InvalidArgumentException
	 */
	public static function fromMagicNumber(int $magicNumber) : TreeType
	{
		self::checkInit();
		if (!isset(self::$numericIdMap[$magicNumber])) {
			throw new \InvalidArgumentException("Unknown tree type magic number $magicNumber");
		}
		return self::$numericIdMap[$magicNumber];
	}

	private function __construct(
		string $enumName,
		private string $displayName,
		private int $magicNumber
	) {
		$this->Enum___construct($enumName);
	}

	public function getDisplayName() : string
	{
		return $this->displayName;
	}

	public function getMagicNumber() : int
	{
		return $this->magicNumber;
	}
}
