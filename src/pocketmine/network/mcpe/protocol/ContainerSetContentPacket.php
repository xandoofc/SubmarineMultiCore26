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
use pocketmine\network\mcpe\protocol\types\inventory\ItemStackWrapper;
use function count;

class ContainerSetContentPacket extends DataPacket
{
	public const NETWORK_ID = ProtocolInfo::CONTAINER_SET_CONTENT_PACKET;

	/** @var int */
	public $windowId;
	/** @var int */
	public $targetEid;
	/** @var ItemStackWrapper[] */
	public $slots = [];
	/** @var int[] */
	public $hotbar = [];

	protected function decodePayload() : void
	{
		$this->windowId = $this->getUnsignedVarInt();
		$this->targetEid = $this->getEntityUniqueId();
		$count = $this->getUnsignedVarInt();
		for ($s = 0; $s < $count && !$this->feof(); ++$s) {
			$this->slots[$s] = $this->getSlot($this->getProtocol());
		}

		$hotbarCount = $this->getUnsignedVarInt(); //MCPE always sends this, even when it's not a player inventory
		for ($s = 0; $s < $hotbarCount && !$this->feof(); ++$s) {
			$this->hotbar[$s] = $this->getVarInt();
		}
	}

	protected function encodePayload() : void
	{
		$this->putUnsignedVarInt($this->windowId);
		$this->putEntityUniqueId($this->targetEid);
		$this->putUnsignedVarInt(count($this->slots));
		foreach ($this->slots as $slot) {
			$this->putSlot($slot, $this->getProtocol());
		}
		if ($this->windowId === ContainerIds::INVENTORY && count($this->hotbar) > 0) {
			$this->putUnsignedVarInt(count($this->hotbar));
			foreach ($this->hotbar as $slot) {
				$this->putVarInt($slot);
			}
		} else {
			$this->putUnsignedVarInt(0);
		}
	}

	public function mustBeDecoded() : bool
	{
		return false;
	}

	public function handle(NetworkSession $session) : bool
	{
		return $session->handleContainerSetContent($this);
	}

}
