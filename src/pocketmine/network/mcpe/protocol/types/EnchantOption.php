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

namespace pocketmine\network\mcpe\protocol\types;

use pocketmine\network\mcpe\NetworkBinaryStream;
use pocketmine\network\mcpe\protocol\ProtocolInfo;

use function count;

;

final class EnchantOption
{
	/**
	 * @param Enchant[] $equipActivatedEnchantments
	 * @param Enchant[] $heldActivatedEnchantments
	 * @param Enchant[] $selfActivatedEnchantments
	 */
	public function __construct(
		private int $cost,
		private int $slotFlags,
		private array $equipActivatedEnchantments,
		private array $heldActivatedEnchantments,
		private array $selfActivatedEnchantments,
		private string $name,
		private int $optionId
	) {
	}

	public function getCost() : int
	{
		return $this->cost;
	}

	public function getSlotFlags() : int
	{
		return $this->slotFlags;
	}

	/** @return Enchant[] */
	public function getEquipActivatedEnchantments() : array
	{
		return $this->equipActivatedEnchantments;
	}

	/** @return Enchant[] */
	public function getHeldActivatedEnchantments() : array
	{
		return $this->heldActivatedEnchantments;
	}

	/** @return Enchant[] */
	public function getSelfActivatedEnchantments() : array
	{
		return $this->selfActivatedEnchantments;
	}

	public function getName() : string
	{
		return $this->name;
	}

	public function getOptionId() : int
	{
		return $this->optionId;
	}

	/**
	 * @return Enchant[]
	 */
	private static function readEnchantList(NetworkBinaryStream $in, int $protocol) : array
	{
		$result = [];
		for ($i = 0, $len = $in->getUnsignedVarInt(); $i < $len; ++$i) {
			$result[] = Enchant::read($in, $protocol);
		}
		return $result;
	}

	/**
	 * @param Enchant[] $list
	 */
	private static function writeEnchantList(NetworkBinaryStream $out, array $list, int $protocol) : void
	{
		$out->putUnsignedVarInt(count($list));
		foreach ($list as $item) {
			$item->write($out, $protocol);
		}
	}

	public static function read(NetworkBinaryStream $in, int $protocol) : self
	{
		$cost = $protocol >= ProtocolInfo::PROTOCOL_975 ? $in->getByte() : $in->getUnsignedVarInt();

		$slotFlags = $in->getLInt();
		$equipActivatedEnchants = self::readEnchantList($in, $protocol);
		$heldActivatedEnchants = self::readEnchantList($in, $protocol);
		$selfActivatedEnchants = self::readEnchantList($in, $protocol);

		$name = $in->getString();
		$optionId = $in->readRecipeNetId();

		return new self($cost, $slotFlags, $equipActivatedEnchants, $heldActivatedEnchants, $selfActivatedEnchants, $name, $optionId);
	}

	public function write(NetworkBinaryStream $out, int $protocol) : void
	{
		if ($protocol >= ProtocolInfo::PROTOCOL_975) {
			$out->putByte($this->cost);
		} else {
			$out->putUnsignedVarInt($this->cost);
		}

		$out->putLInt($this->slotFlags);
		self::writeEnchantList($out, $this->equipActivatedEnchantments, $protocol);
		self::writeEnchantList($out, $this->heldActivatedEnchantments, $protocol);
		self::writeEnchantList($out, $this->selfActivatedEnchantments, $protocol);

		$out->putString($this->name);
		$out->writeRecipeNetId($this->optionId);
	}
}
