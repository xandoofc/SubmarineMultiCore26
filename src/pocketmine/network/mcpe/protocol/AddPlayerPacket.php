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
use pocketmine\network\mcpe\protocol\types\AbilitiesData;
use pocketmine\network\mcpe\protocol\types\DeviceOS;
use pocketmine\network\mcpe\protocol\types\entity\PropertySyncData;
use pocketmine\network\mcpe\protocol\types\EntityLink;
use pocketmine\network\mcpe\protocol\types\GameMode;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStackWrapper;
use pocketmine\utils\UUID;

use function count;
use function is_null;

class AddPlayerPacket extends DataPacket
{
	public const NETWORK_ID = ProtocolInfo::ADD_PLAYER_PACKET;

	/** @var UUID */
	public $uuid;
	/** @var string */
	public $username;
	/** @var string */
	public $thirdPartyName = "";
	/** @var int */
	public $platform = 0;
	/** @var int|null */
	public $entityUniqueId = null; //TODO
	/** @var int */
	public $entityRuntimeId;
	/** @var string */
	public $platformChatId = "";
	/** @var Vector3 */
	public $position;
	/** @var Vector3|null */
	public $motion;
	/** @var float */
	public $pitch = 0.0;
	/** @var float */
	public $yaw = 0.0;
	/** @var float|null */
	public $headYaw = null; //TODO
	/** @var ItemStackWrapper */
	public $item;
	/** @var int */
	public $gameMode = GameMode::SURVIVAL;
	/** @var array */
	public $metadata = [];
	/** @var PropertySyncData */
	public $syncedProperties = null;
	/** @var AbilitiesData */
	public $abilitiesData;

	//TODO: adventure settings stuff
	public $uvarint1 = 0;
	public $uvarint2 = 0;
	public $uvarint3 = 0;
	public $uvarint4 = 0;
	public $uvarint5 = 0;

	public $long1 = 0;

	/** @var EntityLink[] */
	public $links = [];

	/** @var string */
	public $deviceId = ""; //TODO: fill player's device ID (???)
	/** @var int */
	public $buildPlatform = DeviceOS::UNKNOWN;

	protected function decodePayload() : void
	{
		$this->uuid = $this->getUUID();
		$this->username = $this->getString();
		if ($this->getProtocol() >= ProtocolInfo::PROTOCOL_223 && $this->getProtocol() < ProtocolInfo::PROTOCOL_291) {
			$this->thirdPartyName = $this->getString();
			$this->platform = $this->getVarInt();
		}
		if ($this->getProtocol() < ProtocolInfo::PROTOCOL_534) {
			$this->entityUniqueId = $this->getEntityUniqueId();
		}
		$this->entityRuntimeId = $this->getEntityRuntimeId();
		if ($this->getProtocol() >= ProtocolInfo::PROTOCOL_223) {
			$this->platformChatId = $this->getString();
		}
		$this->position = $this->getVector3();
		$this->motion = $this->getVector3();
		$this->pitch = $this->getLFloat();
		$this->yaw = $this->getLFloat();
		$this->headYaw = $this->getLFloat();
		$this->item = $this->getSlot($this->getProtocol());
		if ($this->getProtocol() >= ProtocolInfo::PROTOCOL_503) {
			$this->gameMode = $this->getVarInt();
		}
		$this->metadata = $this->getEntityMetadata($this->getProtocol());

		if ($this->getProtocol() >= ProtocolInfo::PROTOCOL_137) {
			if ($this->getProtocol() >= ProtocolInfo::PROTOCOL_534) {
				if ($this->getProtocol() >= ProtocolInfo::PROTOCOL_557) {
					$this->syncedProperties = PropertySyncData::read($this);
				}
				$this->abilitiesData = AbilitiesData::decode($this, $this->getProtocol());
			} else {
				$this->uvarint1 = $this->getUnsignedVarInt();
				$this->uvarint2 = $this->getUnsignedVarInt();
				$this->uvarint3 = $this->getUnsignedVarInt();
				$this->uvarint4 = $this->getUnsignedVarInt();
				$this->uvarint5 = $this->getUnsignedVarInt();

				$this->long1 = $this->getLLong();
			}

			$linkCount = $this->getUnsignedVarInt();
			for ($i = 0; $i < $linkCount; ++$i) {
				$this->links[$i] = $this->getEntityLink($this->getProtocol());
			}

			if ($this->getProtocol() >= ProtocolInfo::PROTOCOL_282) {
				$this->deviceId = $this->getString();
				if ($this->getProtocol() >= ProtocolInfo::PROTOCOL_388) {
					$this->buildPlatform = $this->getLInt();
				}
			}
		}
	}

	protected function encodePayload() : void
	{
		$this->putUUID($this->uuid);
		$this->putString($this->username);
		if ($this->getProtocol() >= ProtocolInfo::PROTOCOL_223 && $this->getProtocol() < ProtocolInfo::PROTOCOL_291) {
			$this->putString($this->thirdPartyName);
			$this->putVarInt($this->platform);
		}
		if ($this->getProtocol() < ProtocolInfo::PROTOCOL_534) {
			$this->putEntityUniqueId($this->entityUniqueId ?? $this->entityRuntimeId);
		}
		$this->putEntityRuntimeId($this->entityRuntimeId);
		if ($this->getProtocol() >= ProtocolInfo::PROTOCOL_223) {
			$this->putString($this->platformChatId);
		}
		$this->putVector3($this->position);
		$this->putVector3Nullable($this->motion);
		$this->putLFloat($this->pitch);
		$this->putLFloat($this->yaw);
		$this->putLFloat($this->headYaw ?? $this->yaw);
		$this->putSlot($this->item, $this->getProtocol());
		if ($this->getProtocol() >= ProtocolInfo::PROTOCOL_503) {
			$this->putVarInt($this->gameMode);
		}
		$this->putEntityMetadata($this->metadata, $this->getProtocol());

		if ($this->getProtocol() >= ProtocolInfo::PROTOCOL_137) {
			if ($this->getProtocol() >= ProtocolInfo::PROTOCOL_534) {
				if ($this->getProtocol() >= ProtocolInfo::PROTOCOL_557) {
					if (is_null($this->syncedProperties)) {
						$this->syncedProperties = new PropertySyncData([], []);
					}
					$this->syncedProperties->write($this);
				}
				$this->abilitiesData->encode($this, $this->getProtocol());
			} else {
				$this->putUnsignedVarInt($this->uvarint1);
				$this->putUnsignedVarInt($this->uvarint2);
				$this->putUnsignedVarInt($this->uvarint3);
				$this->putUnsignedVarInt($this->uvarint4);
				$this->putUnsignedVarInt($this->uvarint5);
				$this->putLLong($this->long1);
			}

			$this->putUnsignedVarInt(count($this->links));
			foreach ($this->links as $link) {
				$this->putEntityLink($link, $this->getProtocol());
			}

			if ($this->getProtocol() >= ProtocolInfo::PROTOCOL_282) {
				$this->putString($this->deviceId);
				if ($this->getProtocol() >= ProtocolInfo::PROTOCOL_388) {
					$this->putLInt($this->buildPlatform);
				}
			}
		}
	}

	public function mustBeDecoded() : bool
	{
		return false;
	}

	public function handle(NetworkSession $session) : bool
	{
		return $session->handleAddPlayer($this);
	}
}
