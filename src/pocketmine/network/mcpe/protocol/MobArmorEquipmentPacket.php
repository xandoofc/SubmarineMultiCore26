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

use pocketmine\network\mcpe\NetworkSession;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStackWrapper;

class MobArmorEquipmentPacket extends DataPacket
{
	public const NETWORK_ID = ProtocolInfo::MOB_ARMOR_EQUIPMENT_PACKET;

	/** @var int */
	public $entityRuntimeId;

	//this intentionally doesn't use an array because we don't want any implicit dependencies on internal order

	/** @var ItemStackWrapper */
	public $head;
	/** @var ItemStackWrapper */
	public $chest;
	/** @var ItemStackWrapper */
	public $legs;
	/** @var ItemStackWrapper */
	public $feet;
	/** @var ItemStackWrapper */
	public $body;

	protected function decodePayload() : void
	{
		$this->entityRuntimeId = $this->getEntityRuntimeId();
		$this->head = $this->getSlot($this->getProtocol());
		$this->chest = $this->getSlot($this->getProtocol());
		$this->legs = $this->getSlot($this->getProtocol());
		$this->feet = $this->getSlot($this->getProtocol());
		if ($this->getProtocol() >= ProtocolInfo::PROTOCOL_712) {
			$this->body = $this->getSlot($this->getProtocol());
		}
	}

	protected function encodePayload() : void
	{
		$this->putEntityRuntimeId($this->entityRuntimeId);
		$this->putSlot($this->head, $this->getProtocol());
		$this->putSlot($this->chest, $this->getProtocol());
		$this->putSlot($this->legs, $this->getProtocol());
		$this->putSlot($this->feet, $this->getProtocol());
		if ($this->getProtocol() >= ProtocolInfo::PROTOCOL_712) {
			$this->putSlot($this->body, $this->getProtocol());
		}
	}

	public function mustBeDecoded() : bool
	{
		return false;
	}

	public function handle(NetworkSession $session) : bool
	{
		return $session->handleMobArmorEquipment($this);
	}
}
