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
use pocketmine\network\mcpe\protocol\types\inventory\FullContainerName;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStackWrapper;

class InventorySlotPacket extends DataPacket
{
	public const NETWORK_ID = ProtocolInfo::INVENTORY_SLOT_PACKET;

	public int $windowId;
	public int $inventorySlot;
	public int $isNullItem;
	public ?FullContainerName $containerName = null;
	public int $dynamicContainerSize = 0;//??
	public int $dynamicContainerId = 0;//??
	public ?ItemStackWrapper $storage = null;
	public ItemStackWrapper $item;

	protected function decodePayload() : void
	{
		$this->windowId = $this->getUnsignedVarInt();
		$this->inventorySlot = $this->getUnsignedVarInt();
		if ($this->getProtocol() >= ProtocolInfo::PROTOCOL_975) {
			$this->containerName = $this->readOptional(fn () => FullContainerName::read($this, $this->getProtocol()));
			$this->storage = $this->readOptional(fn () => $this->getNetworkItemStackDescriptor($this->getProtocol()));
			$this->item = $this->getNetworkItemStackDescriptor($this->getProtocol());
			return;
		}
		if ($this->getProtocol() >= ProtocolInfo::PROTOCOL_407 && $this->getProtocol() <= ProtocolInfo::PROTOCOL_428) {
			$this->isNullItem = $this->getVarInt();
		}
		if ($this->getProtocol() >= ProtocolInfo::PROTOCOL_712) {
			if ($this->getProtocol() >= ProtocolInfo::PROTOCOL_729) {
				$this->containerName = FullContainerName::read($this, $this->getProtocol());
				if ($this->getProtocol() >= ProtocolInfo::PROTOCOL_748) {
					$this->storage = $this->getSlot($this->getProtocol());
				} else {
					$this->putUnsignedVarInt($this->dynamicContainerSize);
				}
			} else {
				$this->containerName = new FullContainerName(0);
				$this->dynamicContainerId = $this->getUnsignedVarInt();
			}
		}
		$this->item = $this->getSlot($this->getProtocol());
	}

	protected function encodePayload() : void
	{
		$this->putUnsignedVarInt($this->windowId);
		$this->putUnsignedVarInt($this->inventorySlot);
		if ($this->getProtocol() >= ProtocolInfo::PROTOCOL_975) {
			$this->writeOptional($this->containerName, fn (FullContainerName $v) => $v->write($this, $this->getProtocol()));
			$this->writeOptional($this->storage, fn (ItemStackWrapper $v) => $this->putNetworkItemStackDescriptor($v, $this->getProtocol()));
			$this->putNetworkItemStackDescriptor($this->item, $this->getProtocol());
			return;
		}
		if ($this->getProtocol() >= ProtocolInfo::PROTOCOL_407 && $this->getProtocol() <= ProtocolInfo::PROTOCOL_428) {
			$this->putVarInt($this->item->getItemStack()->isNull() ? 0 : 1);
		}
		if ($this->getProtocol() >= ProtocolInfo::PROTOCOL_712) {
			if ($this->getProtocol() >= ProtocolInfo::PROTOCOL_729) {
				($this->containerName ?? new FullContainerName(0))->write($this, $this->getProtocol());
				if ($this->getProtocol() >= ProtocolInfo::PROTOCOL_748) {
					$this->putSlot($this->storage, $this->getProtocol());
				} else {
					$this->putUnsignedVarInt($this->dynamicContainerSize);
				}
			} else {
				$this->putUnsignedVarInt($this->dynamicContainerId);
			}
		}
		$this->putSlot($this->item, $this->getProtocol());
	}

	public function mustBeDecoded() : bool
	{
		return false;
	}

	public function handle(NetworkSession $session) : bool
	{
		return $session->handleInventorySlot($this);
	}
}
