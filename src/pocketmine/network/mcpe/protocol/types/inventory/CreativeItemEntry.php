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

namespace pocketmine\network\mcpe\protocol\types\inventory;

use pocketmine\item\Item;
use pocketmine\network\mcpe\NetworkBinaryStream;
use pocketmine\network\mcpe\protocol\ProtocolInfo;

final class CreativeItemEntry
{
	public function __construct(
		private int $entryId,
		private Item $item,
		private readonly int $groupId
	) {}

	public function getEntryId() : int
	{
		return $this->entryId;
	}

	public function getItem() : Item
	{
		return $this->item;
	}

	public function setItem(Item $item) : void{
		$this->item = $item;
	}

	public function getGroupId() : int
	{
		return $this->groupId;
	}

	public function setEntryId(int $entryId) : void
	{
		$this->entryId = $entryId;
	}

	public static function read(NetworkBinaryStream $in, int $protocolVersion) : self
	{
		if ($protocolVersion > ProtocolInfo::PROTOCOL_419) {
			$entryId = $in->readCreativeItemNetId();
		} else {
			$entryId = $in->getUnsignedVarInt();
		}

		$item = $in->getSlot($protocolVersion, false)->getItemStack();

		if ($protocolVersion >= ProtocolInfo::PROTOCOL_776) {
			$groupId = $in->getUnsignedVarInt();
		}
		return new self($entryId, $item, $groupId ?? 0);
	}

	public function write(NetworkBinaryStream $out, int $protocolVersion) : void
	{
		if ($protocolVersion > ProtocolInfo::PROTOCOL_419) {
			$out->writeCreativeItemNetId($this->entryId);
		} else {
			$out->putUnsignedVarInt($this->entryId);
		}

		$out->putSlot(ItemStackWrapper::legacy($this->item), $protocolVersion, false);

		if ($protocolVersion >= ProtocolInfo::PROTOCOL_776) {
			$out->putUnsignedVarInt($this->groupId);
		}
	}
}
