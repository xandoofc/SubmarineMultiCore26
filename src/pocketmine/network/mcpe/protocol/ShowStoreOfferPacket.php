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
use pocketmine\network\mcpe\protocol\types\ShowStoreOfferRedirectType;

class ShowStoreOfferPacket extends DataPacket
{
	public const NETWORK_ID = ProtocolInfo::SHOW_STORE_OFFER_PACKET;

	/** @var string */
	public $offerId;
	/** @var bool */
	public $showAll;
	/** @var ShowStoreOfferRedirectType */
	public $redirectType;

	protected function decodePayload() : void
	{
		$this->offerId = $this->getString();
		if ($this->getProtocol() >= ProtocolInfo::PROTOCOL_630) {
			$this->redirectType = ShowStoreOfferRedirectType::fromPacket($this->getByte());
		} else {
			$this->showAll = $this->getBool();
		}
	}

	protected function encodePayload() : void
	{
		$this->putString($this->offerId);
		if ($this->getProtocol() >= ProtocolInfo::PROTOCOL_630) {
			$this->putByte($this->redirectType->value);
		} else {
			$this->putBool($this->showAll);
		}
	}

	public function mustBeDecoded() : bool
	{
		return false;
	}

	public function handle(NetworkSession $session) : bool
	{
		return $session->handleShowStoreOffer($this);
	}
}
