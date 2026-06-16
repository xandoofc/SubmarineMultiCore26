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
use pocketmine\network\mcpe\protocol\types\inventory\ContainerIds;
use pocketmine\network\mcpe\protocol\types\inventory\FullContainerName;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStackWrapper;

use function count;

class InventoryContentPacket extends DataPacket
{
	public const NETWORK_ID = ProtocolInfo::INVENTORY_CONTENT_PACKET;

	public int $windowId;
	/** @var ItemStackWrapper[] */
	public array $items = [];
	/** @var int[] */
	public array $index = [];
	public FullContainerName $containerName;
	public int $dynamicContainerSize = 0; //??
	public int $dynamicContainerId = 0; //??
	public ItemStackWrapper $storage;

	protected function decodePayload() : void
	{
		$this->windowId = $this->getUnsignedVarInt();
		$count = $this->getUnsignedVarInt();
		if ($this->getProtocol() >= ProtocolInfo::PROTOCOL_975) {
			for ($i = 0; $i < $count; ++$i) {
				$this->items[] = $this->getSlot($this->getProtocol());
			}
			$this->containerName = FullContainerName::read($this, $this->getProtocol());
			$this->storage = $this->getSlot($this->getProtocol());
			return;
		}
		for ($i = 0; $i < $count; ++$i) {
			$this->index[] = $this->getVarInt();
			$this->items[] = $this->getSlot($this->getProtocol());
		}
		if ($this->getProtocol() >= ProtocolInfo::PROTOCOL_712) {
			if ($this->getProtocol() >= ProtocolInfo::PROTOCOL_729) {
				$this->containerName = FullContainerName::read($this, $this->getProtocol());
				if ($this->getProtocol() >= ProtocolInfo::PROTOCOL_748) {
					$this->storage = $this->getSlot($this->getProtocol());
				} else {
					$this->dynamicContainerSize = $this->getUnsignedVarInt();
				}
			} else {
				$this->containerName = new FullContainerName(0);
				$this->dynamicContainerId = $this->getUnsignedVarInt();
			}
		}
	}

	protected function encodePayload() : void
	{
		$this->putUnsignedVarInt($this->windowId);
		$this->putUnsignedVarInt(count($this->items));
		if ($this->getProtocol() >= ProtocolInfo::PROTOCOL_975) {
			foreach ($this->items as $item) {
				$this->putSlot($item, $this->getProtocol(), true, $this->windowId !== ContainerIds::CREATIVE);
			}
			($this->containerName ?? new FullContainerName(0))->write($this, $this->getProtocol());
			$this->putSlot($this->storage, $this->getProtocol());
			return;
		}
		$index = 1;
		foreach ($this->items as $item) {
			if ($this->getProtocol() >= ProtocolInfo::PROTOCOL_407 && $this->getProtocol() <= ProtocolInfo::PROTOCOL_428) {
				if ($item->getStackId() === 0) {
					$this->putVarInt(0);
				} else {
					$this->putVarInt($index++);
				}
			}
			$this->putSlot($item, $this->getProtocol(), true, $this->windowId !== ContainerIds::CREATIVE);
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
	}

	public function mustBeDecoded() : bool
	{
		return false;
	}

	public function handle(NetworkSession $session) : bool
	{
		return $session->handleInventoryContent($this);
	}
}
