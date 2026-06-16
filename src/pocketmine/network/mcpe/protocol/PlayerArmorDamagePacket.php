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

namespace pocketmine\network\mcpe\protocol;

#include <rules/DataPacket.h>

use pocketmine\network\mcpe\NetworkSession;

class PlayerArmorDamagePacket extends DataPacket /* implements ClientboundPacket*/
{
	public const NETWORK_ID = ProtocolInfo::PLAYER_ARMOR_DAMAGE_PACKET;

	private const FLAG_HEAD = 0;
	private const FLAG_CHEST = 1;
	private const FLAG_LEGS = 2;
	private const FLAG_FEET = 3;
	private const FLAG_BODY = 4;

	private ?int $headSlotDamage;
	private ?int $chestSlotDamage;
	private ?int $legsSlotDamage;
	private ?int $feetSlotDamage;
	private ?int $bodySlotDamage;

	public static function create(?int $headSlotDamage, ?int $chestSlotDamage, ?int $legsSlotDamage, ?int $feetSlotDamage, ?int $bodySlotDamage) : self
	{
		$result = new self();
		$result->headSlotDamage = $headSlotDamage;
		$result->chestSlotDamage = $chestSlotDamage;
		$result->legsSlotDamage = $legsSlotDamage;
		$result->feetSlotDamage = $feetSlotDamage;
		$result->bodySlotDamage = $bodySlotDamage;

		return $result;
	}

	public function getHeadSlotDamage() : ?int
	{
		return $this->headSlotDamage;
	}

	public function getChestSlotDamage() : ?int
	{
		return $this->chestSlotDamage;
	}

	public function getLegsSlotDamage() : ?int
	{
		return $this->legsSlotDamage;
	}

	public function getFeetSlotDamage() : ?int
	{
		return $this->feetSlotDamage;
	}

	public function getBodySlotDamage() : ?int
	{
		return $this->bodySlotDamage;
	}

	private function maybeReadDamage(int $flags, int $flag) : ?int
	{
		if (($flags & (1 << $flag)) !== 0) {
			return $this->getVarInt();
		}
		return null;
	}

	protected function decodePayload() : void
	{
		$flags = $this->getByte();

		$this->headSlotDamage = $this->maybeReadDamage($flags, self::FLAG_HEAD);
		$this->chestSlotDamage = $this->maybeReadDamage($flags, self::FLAG_CHEST);
		$this->legsSlotDamage = $this->maybeReadDamage($flags, self::FLAG_LEGS);
		$this->feetSlotDamage = $this->maybeReadDamage($flags, self::FLAG_FEET);
		if ($this->getProtocol() >= ProtocolInfo::PROTOCOL_712) {
			$this->bodySlotDamage = $this->maybeReadDamage($flags, self::FLAG_BODY);
		}
	}

	private function composeFlag(?int $field, int $flag) : int
	{
		return $field !== null ? (1 << $flag) : 0;
	}

	private function maybeWriteDamage(?int $field) : void
	{
		if ($field !== null) {
			$this->putVarInt($field);
		}
	}

	protected function encodePayload() : void
	{
		if ($this->getProtocol() >= ProtocolInfo::PROTOCOL_712) {
			$flags = $this->composeFlag($this->headSlotDamage, self::FLAG_HEAD) |
				$this->composeFlag($this->chestSlotDamage, self::FLAG_CHEST) |
				$this->composeFlag($this->legsSlotDamage, self::FLAG_LEGS) |
				$this->composeFlag($this->feetSlotDamage, self::FLAG_FEET) |
				$this->composeFlag($this->bodySlotDamage, self::FLAG_BODY);
		} else {
			$flags = $this->composeFlag($this->headSlotDamage, self::FLAG_HEAD) |
				$this->composeFlag($this->chestSlotDamage, self::FLAG_CHEST) |
				$this->composeFlag($this->legsSlotDamage, self::FLAG_LEGS) |
				$this->composeFlag($this->feetSlotDamage, self::FLAG_FEET);
		}

		$this->putByte($flags);

		$this->maybeWriteDamage($this->headSlotDamage);
		$this->maybeWriteDamage($this->chestSlotDamage);
		$this->maybeWriteDamage($this->legsSlotDamage);
		$this->maybeWriteDamage($this->feetSlotDamage);
		if ($this->getProtocol() >= ProtocolInfo::PROTOCOL_712) {
			$this->maybeWriteDamage($this->bodySlotDamage);
		}
	}

	public function mustBeDecoded() : bool
	{
		return false;
	}

	public function handle(NetworkSession $session) : bool
	{
		return $session->handlePlayerArmorDamage($this);
	}
}
