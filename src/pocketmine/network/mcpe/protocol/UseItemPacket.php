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

use pocketmine\math\Vector3;
use pocketmine\network\mcpe\NetworkSession;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStackWrapper;

class UseItemPacket extends DataPacket
{
	public const NETWORK_ID = ProtocolInfo::USE_ITEM_PACKET;

	/** @var int */
	public $x;
	/** @var int */
	public $y;
	/** @var int */
	public $z;
	/** @var int */
	public $blockId;
	/** @var int */
	public $face;
	/** @var Vector3 */
	public $playerPos;
	/** @var Vector3 */
	public $clickPos;
	/** @var int */
	public $slot;
	/** @var ItemStackWrapper */
	public $item;

	protected function decodePayload() : void
	{
		$this->getBlockPosition($this->x, $this->y, $this->z);
		$this->blockId = $this->getUnsignedVarInt();
		$this->face = $this->getVarInt();
		$this->clickPos = $this->getVector3();
		$this->playerPos = $this->getVector3();
		$this->slot = $this->getVarInt();
		$this->item = $this->getSlot($this->getProtocol());
	}

	protected function encodePayload() : void
	{
		$this->putBlockPosition($this->x, $this->y, $this->z);
		$this->putUnsignedVarInt($this->blockId);
		$this->putVarInt($this->face);
		$this->putVector3($this->clickPos);
		$this->putVector3($this->playerPos);
		$this->putVarInt($this->slot);
		$this->putSlot($this->item, $this->getProtocol());
	}

	public function handle(NetworkSession $session) : bool
	{
		return $session->handleUseItem($this);
	}
}
