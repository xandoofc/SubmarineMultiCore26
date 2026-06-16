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

use pocketmine\network\mcpe\NetworkSession as PacketHandlerInterface;
use pocketmine\network\mcpe\protocol\types\inventory\InventoryTransactionChangedSlotsHack;
use pocketmine\network\mcpe\protocol\types\inventory\MismatchTransactionData;
use pocketmine\network\mcpe\protocol\types\inventory\NormalTransactionData;
use pocketmine\network\mcpe\protocol\types\inventory\ReleaseItemTransactionData;
use pocketmine\network\mcpe\protocol\types\inventory\TransactionData;
use pocketmine\network\mcpe\protocol\types\inventory\UseItemOnEntityTransactionData;
use pocketmine\network\mcpe\protocol\types\inventory\UseItemTransactionData;

use function count;

class InventoryTransactionPacket extends DataPacket
{
	public const NETWORK_ID = ProtocolInfo::INVENTORY_TRANSACTION_PACKET;

	public const TYPE_NORMAL = 0;
	public const TYPE_MISMATCH = 1;
	public const TYPE_USE_ITEM = 2;
	public const TYPE_USE_ITEM_ON_ENTITY = 3;
	public const TYPE_RELEASE_ITEM = 4;

	/** @var int */
	public $requestId = 0;
	/** @var InventoryTransactionChangedSlotsHack[] */
	public $requestChangedSlots;
	/** @var TransactionData */
	public $trData;

	protected function decodePayload() : void
	{
		$in = $this;
		if ($this->getProtocol() >= ProtocolInfo::PROTOCOL_407) {
			$this->requestId = $in->readLegacyItemStackRequestId();
			$this->requestChangedSlots = [];
			if ($this->requestId !== 0) {
				for ($i = 0, $len = $in->getUnsignedVarInt(); $i < $len; ++$i) {
					$this->requestChangedSlots[] = InventoryTransactionChangedSlotsHack::read($in);
				}
			}
		}

		$transactionType = $in->getUnsignedVarInt();
		$this->trData = match ($transactionType) {
			self::TYPE_NORMAL => new NormalTransactionData(),
			self::TYPE_MISMATCH => new MismatchTransactionData(),
			self::TYPE_USE_ITEM => new UseItemTransactionData(),
			self::TYPE_USE_ITEM_ON_ENTITY => new UseItemOnEntityTransactionData(),
			self::TYPE_RELEASE_ITEM => new ReleaseItemTransactionData(),
			default => throw new PacketDecodeException("Unknown transaction type $transactionType"),
		};

		$this->trData->decode($in, $this->getProtocol());
	}

	protected function encodePayload() : void
	{
		$out = $this;
		if ($this->getProtocol() >= ProtocolInfo::PROTOCOL_407) {
			$out->writeLegacyItemStackRequestId($this->requestId);
			if ($this->requestId !== 0) {
				$out->putUnsignedVarInt(count($this->requestChangedSlots));
				foreach ($this->requestChangedSlots as $changedSlots) {
					$changedSlots->write($out);
				}
			}
		}

		$out->putUnsignedVarInt($this->trData->getTypeId());
		$this->trData->encode($out, $this->getProtocol());
	}

	public function handle(PacketHandlerInterface $session) : bool
	{
		return $session->handleInventoryTransaction($this);
	}
}
