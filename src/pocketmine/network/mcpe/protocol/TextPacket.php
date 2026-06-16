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

use pocketmine\network\mcpe\convert\ConstantTranslator;
use pocketmine\network\mcpe\NetworkSession;

use function count;

class TextPacket extends DataPacket
{
	public const NETWORK_ID = ProtocolInfo::TEXT_PACKET;

	private const CATEGORY_MESSAGE_ONLY = 0;
	private const CATEGORY_AUTHORED_MESSAGE = 1;
	private const CATEGORY_MESSAGE_WITH_PARAMETERS = 2;

	public const TYPE_RAW = 0;
	public const TYPE_CHAT = 1;
	public const TYPE_TRANSLATION = 2;
	public const TYPE_POPUP = 3;
	public const TYPE_JUKEBOX_POPUP = 4;
	public const TYPE_TIP = 5;
	public const TYPE_SYSTEM = 6;
	public const TYPE_WHISPER = 7;
	public const TYPE_ANNOUNCEMENT = 8;
	public const TYPE_JSON_WHISPER = 9;
	public const TYPE_JSON = 10;
	public const TYPE_JSON_ANNOUNCEMENT = 11;

	public const PARAMETERS_LIMIT = 5;

	/** @var int */
	public $type;
	/** @var bool */
	public $needsTranslation = false;
	/** @var string */
	public $sourceName;
	/** @var string */
	public $sourceThirdPartyName = "";
	/** @var int */
	public $sourcePlatform = 0;
	/** @var string */
	public $message;
	/** @var string[] */
	public $parameters = [];
	/** @var string */
	public $xboxUserId = "";
	/** @var string */
	public $platformChatId = "";
	/** @var string */
	public $filteredMessage = "";

	protected function decodePayload() : void
	{
		if ($this->getProtocol() >= ProtocolInfo::PROTOCOL_975) {
			$this->needsTranslation = $this->getBool();
			$category = $this->getByte();
			$this->type = $this->getByte();

			switch ($this->type) {
				case self::TYPE_CHAT:
				case self::TYPE_WHISPER:
				case self::TYPE_ANNOUNCEMENT:
					if ($category !== self::CATEGORY_AUTHORED_MESSAGE) {
						throw new PacketDecodeException("Invalid TextPacket category $category for authored type " . $this->type);
					}
					$this->sourceName = $this->getString();
					$this->message = $this->getString();
					break;
				case self::TYPE_RAW:
				case self::TYPE_TIP:
				case self::TYPE_SYSTEM:
				case self::TYPE_JSON_WHISPER:
				case self::TYPE_JSON:
				case self::TYPE_JSON_ANNOUNCEMENT:
					if ($category !== self::CATEGORY_MESSAGE_ONLY) {
						throw new PacketDecodeException("Invalid TextPacket category $category for message-only type " . $this->type);
					}
					$this->message = $this->getString();
					break;
				case self::TYPE_TRANSLATION:
				case self::TYPE_POPUP:
				case self::TYPE_JUKEBOX_POPUP:
					if ($category !== self::CATEGORY_MESSAGE_WITH_PARAMETERS) {
						throw new PacketDecodeException("Invalid TextPacket category $category for parameterized type " . $this->type);
					}
					$this->message = $this->getString();
					$count = $this->getUnsignedVarInt();
					if ($count > self::PARAMETERS_LIMIT) {
						throw new PacketDecodeException("Too many translation parameters count: $count");
					}
					for ($i = 0; $i < $count; ++$i) {
						$this->parameters[] = $this->getString();
					}
					break;
			}

			$this->xboxUserId = $this->getString();
			$this->platformChatId = $this->getString();
			$this->filteredMessage = $this->readOptional($this->getString(...)) ?? "";
			return;
		}

		$this->type = ConstantTranslator::getInstance()->fromNetworkId(TextPacket::class, $this->getByte(), $this->getProtocol());
		if ($this->getProtocol() >= ProtocolInfo::PROTOCOL_137) {
			$this->needsTranslation = $this->getBool();
		}
		switch ($this->type) {
			case self::TYPE_CHAT:
			case self::TYPE_WHISPER:
				// no break
			case self::TYPE_ANNOUNCEMENT:
				$this->sourceName = $this->getString();
				if ($this->getProtocol() >= ProtocolInfo::PROTOCOL_223 && $this->getProtocol() < ProtocolInfo::PROTOCOL_291) {
					$this->sourceThirdPartyName = $this->getString();
					$this->sourcePlatform = $this->getVarInt();
				}
				// no break
			case self::TYPE_RAW:
			case self::TYPE_TIP:
			case self::TYPE_SYSTEM:
			case self::TYPE_JSON_WHISPER:
			case self::TYPE_JSON:
			case self::TYPE_JSON_ANNOUNCEMENT:
				$this->message = $this->getString();
				break;
			case self::TYPE_POPUP:
				if ($this->getProtocol() < ProtocolInfo::PROTOCOL_137) {
					$this->sourceName = $this->getString();
					$this->message = $this->getString();
					break;
				}
				// no break
			case self::TYPE_TRANSLATION:
			case self::TYPE_JUKEBOX_POPUP:
				$this->message = $this->getString();
				$count = $this->getUnsignedVarInt();
				if ($count > self::PARAMETERS_LIMIT) {
					throw new PacketDecodeException("Too many translation parameters count: $count");
				}
				for ($i = 0; $i < $count; ++$i) {
					$this->parameters[] = $this->getString();
				}
				break;
		}

		if ($this->getProtocol() >= ProtocolInfo::PROTOCOL_137) {
			$this->xboxUserId = $this->getString();
			if ($this->getProtocol() >= ProtocolInfo::PROTOCOL_223) {
				$this->platformChatId = $this->getString();
				if ($this->getProtocol() >= ProtocolInfo::PROTOCOL_685) {
					$this->filteredMessage = $this->getString();
				}
			}
		}
	}

	protected function encodePayload() : void
	{
		if ($this->getProtocol() >= ProtocolInfo::PROTOCOL_975) {
			$this->putBool($this->needsTranslation);
			$category = match ($this->type) {
				self::TYPE_RAW,
				self::TYPE_TIP,
				self::TYPE_SYSTEM,
				self::TYPE_JSON_WHISPER,
				self::TYPE_JSON,
				self::TYPE_JSON_ANNOUNCEMENT => self::CATEGORY_MESSAGE_ONLY,
				self::TYPE_CHAT,
				self::TYPE_WHISPER,
				self::TYPE_ANNOUNCEMENT => self::CATEGORY_AUTHORED_MESSAGE,
				self::TYPE_TRANSLATION,
				self::TYPE_POPUP,
				self::TYPE_JUKEBOX_POPUP => self::CATEGORY_MESSAGE_WITH_PARAMETERS,
				default => throw new \LogicException("Invalid TextPacket type: " . $this->type)
			};
			$this->putByte($category);
			$this->putByte($this->type);

			switch ($this->type) {
				case self::TYPE_CHAT:
				case self::TYPE_WHISPER:
				case self::TYPE_ANNOUNCEMENT:
					$this->putString($this->sourceName);
					$this->putString($this->message);
					break;
				case self::TYPE_RAW:
				case self::TYPE_TIP:
				case self::TYPE_SYSTEM:
				case self::TYPE_JSON_WHISPER:
				case self::TYPE_JSON:
				case self::TYPE_JSON_ANNOUNCEMENT:
					$this->putString($this->message);
					break;
				case self::TYPE_TRANSLATION:
				case self::TYPE_POPUP:
				case self::TYPE_JUKEBOX_POPUP:
					$this->putString($this->message);
					$this->putUnsignedVarInt(count($this->parameters));
					foreach ($this->parameters as $p) {
						$this->putString($p);
					}
					break;
			}

			$this->putString($this->xboxUserId);
			$this->putString($this->platformChatId);
			$this->writeOptional($this->filteredMessage !== "" ? $this->filteredMessage : null, $this->putString(...));
			return;
		}

		$this->putByte(ConstantTranslator::getInstance()->toNetworkId(TextPacket::class, $this->type, $this->getProtocol()));
		if ($this->getProtocol() >= ProtocolInfo::PROTOCOL_137) {
			$this->putBool($this->needsTranslation);
		}

		switch ($this->type) {
			case self::TYPE_CHAT:
			case self::TYPE_WHISPER:
			// no break
			case self::TYPE_ANNOUNCEMENT:
				$this->putString($this->sourceName);
				if ($this->getProtocol() >= ProtocolInfo::PROTOCOL_223 && $this->getProtocol() < ProtocolInfo::PROTOCOL_291) {
					$this->putString($this->sourceThirdPartyName);
					$this->putVarInt($this->sourcePlatform);
				}
				// no break
			case self::TYPE_RAW:
			case self::TYPE_TIP:
			case self::TYPE_SYSTEM:
			case self::TYPE_JSON_WHISPER:
			case self::TYPE_JSON:
			case self::TYPE_JSON_ANNOUNCEMENT:
				$this->putString($this->message);
				break;
			case self::TYPE_POPUP:
				if ($this->getProtocol() < ProtocolInfo::PROTOCOL_137) {
					$this->putString($this->sourceName);
					$this->putString($this->message);
					break;
				}
				// no break
			case self::TYPE_TRANSLATION:
			case self::TYPE_JUKEBOX_POPUP:
				$this->putString($this->message);
				$this->putUnsignedVarInt(count($this->parameters));
				foreach ($this->parameters as $p) {
					$this->putString($p);
				}
				break;
		}

		if ($this->getProtocol() >= ProtocolInfo::PROTOCOL_137) {
			$this->putString($this->xboxUserId);
			if ($this->getProtocol() >= ProtocolInfo::PROTOCOL_223) {
				$this->putString($this->platformChatId);
				if ($this->getProtocol() >= ProtocolInfo::PROTOCOL_685) {
					$this->putString($this->filteredMessage);
				}
			}
		}
	}

	public function handle(NetworkSession $session) : bool
	{
		return $session->handleText($this);
	}
}
