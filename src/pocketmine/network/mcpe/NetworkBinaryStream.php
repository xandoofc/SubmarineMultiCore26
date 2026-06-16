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

namespace pocketmine\network\mcpe;

use pocketmine\block\BlockIds;
use pocketmine\entity\Attribute;
use pocketmine\entity\Entity;
use pocketmine\entity\Skin;
use pocketmine\item\Durable;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\math\Vector2;
use pocketmine\math\Vector3;
use pocketmine\nbt\LittleEndianNBTStream;
use pocketmine\nbt\NetworkLittleEndianNBTStream;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\LongTag;
use pocketmine\nbt\tag\NamedTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\network\mcpe\convert\EntityMetadataTranslator;
use pocketmine\network\mcpe\convert\ItemTranslator;
use pocketmine\network\mcpe\convert\LegacyItemIdToStringIdMap;
use pocketmine\network\mcpe\convert\RuntimeBlockMapping;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\network\mcpe\protocol\types\command\CommandOriginData;
use pocketmine\network\mcpe\protocol\types\entity\AttributeModifier;
use pocketmine\network\mcpe\protocol\types\EntityLink;
use pocketmine\network\mcpe\protocol\types\GameRuleType;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStackWrapper;
use pocketmine\network\mcpe\protocol\types\skin\PersonaPieceTintColor;
use pocketmine\network\mcpe\protocol\types\skin\PersonaSkinPiece;
use pocketmine\network\mcpe\protocol\types\skin\SerializedSkin;
use pocketmine\network\mcpe\protocol\types\skin\SkinAnimation;
use pocketmine\network\mcpe\protocol\types\skin\SkinImage;
use pocketmine\network\mcpe\protocol\types\StructureEditorData;
use pocketmine\network\mcpe\protocol\types\StructureSettings;
use pocketmine\utils\BinaryDataException;
use pocketmine\utils\BinaryStream;
use pocketmine\utils\Color;
use pocketmine\utils\UUID;
use SplFixedArray;
use UnexpectedValueException;

use function assert;
use function chr;
use function count;
use function json_decode;
use function json_encode;
use function ord;
use function strlen;

class NetworkBinaryStream extends BinaryStream
{
	private const DAMAGE_TAG = "Damage"; //TAG_Int
	private const DAMAGE_TAG_CONFLICT_RESOLUTION = "___Damage_ProtocolCollisionResolution___";
	private const PM_ID_TAG = "___Id___";
	private const PM_META_TAG = "___Meta___";

	public function getString() : string
	{
		return $this->get($this->getUnsignedVarInt());
	}

	public function putString(string $v) : void
	{
		$this->putUnsignedVarInt(strlen($v));
		$this->put($v);
	}

	public function getUUID() : UUID
	{
		//This is actually two little-endian longs: UUID Most followed by UUID Least
		$part1 = $this->getLInt();
		$part0 = $this->getLInt();
		$part3 = $this->getLInt();
		$part2 = $this->getLInt();

		return new UUID($part0, $part1, $part2, $part3);
	}

	public function putUUID(UUID $uuid) : void
	{
		$this->putLInt($uuid->getPart(1));
		$this->putLInt($uuid->getPart(0));
		$this->putLInt($uuid->getPart(3));
		$this->putLInt($uuid->getPart(2));
	}

	public function getSkin(int $playerProtocol) : Skin
	{
		$skinId = $this->getString();
		if ($playerProtocol >= ProtocolInfo::PROTOCOL_428) {
			$skinPlayFabId = $this->getString();
		}
		$skinResourcePatch = $this->getString();
		$skinData = $this->getSkinImage();
		$animationCount = $this->getLInt();
		if ($animationCount > 128) {
			throw new UnexpectedValueException("Too many skin animations: $animationCount");
		}
		$animations = [];
		for ($i = 0; $i < $animationCount; ++$i) {
			$animations[] = new SkinAnimation(
				$this->getSkinImage(),
				$this->getLInt(),
				$this->getLFloat(),
				($playerProtocol >= ProtocolInfo::PROTOCOL_419 ? $this->getLInt() : 0)
			);
		}
		$capeData = $this->getSkinImage();
		$geometryData = $this->getString();
		if ($playerProtocol >= ProtocolInfo::PROTOCOL_465) {
			$geometryDataVersion = $this->getString();
		}
		$animationData = $this->getString();
		if ($playerProtocol < ProtocolInfo::PROTOCOL_465) {
			$premium = $this->getBool();
			$persona = $this->getBool();
			$capeOnClassic = $this->getBool();
		}
		$capeId = $this->getString();
		$fullSkinId = $this->getString();

		if ($playerProtocol === ProtocolInfo::PROTOCOL_390 || $playerProtocol >= ProtocolInfo::PROTOCOL_407) {
			$armSize = $this->getString();
			$skinColor = Color::fromHexString($this->getString());
			$personaPieceCount = $this->getLInt();
			if ($personaPieceCount > 128) {
				throw new UnexpectedValueException("Too many persona pieces: $personaPieceCount");
			}
			$personaPieces = [];
			for ($i = 0; $i < $personaPieceCount; ++$i) {
				$personaPieces[] = new PersonaSkinPiece(
					$this->getString(),
					$this->getString(),
					$this->getString(),
					$this->getBool(),
					$this->getString()
				);
			}
			$pieceTintColorCount = $this->getLInt();
			if ($pieceTintColorCount > 128) {
				throw new UnexpectedValueException("Too many piece tint colors: $pieceTintColorCount");
			}
			$pieceTintColors = [];
			for ($i = 0; $i < $pieceTintColorCount; ++$i) {
				$pieceType = $this->getString();
				$colorCount = $this->getLInt();
				$colors = [];
				for ($j = 0; $j < $colorCount; ++$j) {
					$colors[] = $this->getString();
				}
				$pieceTintColors[] = new PersonaPieceTintColor(
					$pieceType,
					$colors
				);
			}
			$personaPieces = SplFixedArray::fromArray($personaPieces);
			$pieceTintColors = SplFixedArray::fromArray($pieceTintColors);
			if ($playerProtocol >= ProtocolInfo::PROTOCOL_465) {
				$premium = $this->getBool();
				$persona = $this->getBool();
				$capeOnClassic = $this->getBool();
				$isPrimaryUser = $this->getBool();
				if ($playerProtocol >= ProtocolInfo::PROTOCOL_568) {
					$override = $this->getBool();
				}
			}
		} else {
			$armSize = "wide";
			$skinColor = new Color(0, 0, 0);
			$personaPieces = SplFixedArray::fromArray([]);
			$pieceTintColors = SplFixedArray::fromArray([]);
		}

		return (new SerializedSkin($skinId, $skinPlayFabId ?? "", $skinData, $capeId, $capeData, $skinResourcePatch, $geometryData, $geometryDataVersion ?? "", $animationData, $animations, $premium ?? false, $persona ?? false, $capeOnClassic ?? false, $fullSkinId, $armSize, $skinColor, $personaPieces, $pieceTintColors, $isPrimaryUser ?? true, $override ?? true))->toSkin();
	}

	public function putSkin(Skin $skin, int $playerProtocol) : void
	{
		$skin = $skin->getSerializedSkin();
		$this->putString($skin->getSkinId());
		if ($playerProtocol >= ProtocolInfo::PROTOCOL_428) {
			$this->putString($skin->getPlayFabId());
		}
		$this->putString($skin->getResourcePatch());
		$this->putSkinImage($skin->getSkinImage());
		$this->putLInt(count($skin->getAnimationFrames()));
		foreach ($skin->getAnimationFrames() as $animation) {
			$this->putSkinImage($animation->getImage());
			$this->putLInt($animation->getType());
			$this->putLFloat($animation->getFrames());
			if ($playerProtocol >= ProtocolInfo::PROTOCOL_419) {
				$this->putLInt($animation->getExpressionType());
			}
		}
		$this->putSkinImage($skin->getCapeImage());
		$this->putString($skin->getGeometryData());
		if ($playerProtocol >= ProtocolInfo::PROTOCOL_465) {
			$this->putString($skin->getGeometryDataEngineVersion());
		}
		$this->putString($skin->getAnimationData());
		if ($playerProtocol < ProtocolInfo::PROTOCOL_465) {
			$this->putBool($skin->isPremiumSkin());
			$this->putBool($skin->isPersonaSkin());
			$this->putBool($skin->isCapeOnClassicSkin());
		}
		$this->putString($skin->getCapeId());
		$this->putString($skin->getFullSkinId());
		if ($playerProtocol === ProtocolInfo::PROTOCOL_390 || $playerProtocol >= ProtocolInfo::PROTOCOL_407) {
			$this->putString($skin->getArmSize());
			$this->putString($skin->getSkinColor()->toHexString());
			$this->putLInt(count($skin->getPersonaPieces()));
			foreach ($skin->getPersonaPieces() as $piece) {
				$this->putString($piece->getPieceId());
				$this->putString($piece->getPieceType());
				$this->putString($piece->getPackId());
				$this->putBool($piece->isDefaultPiece());
				$this->putString($piece->getProductId());
			}
			$this->putLInt(count($skin->getPieceTintColors()));
			foreach ($skin->getPieceTintColors() as $tint) {
				$this->putString($tint->getPieceType());
				$this->putLInt(count($tint->getColors()));
				foreach ($tint->getColors() as $color) {
					$this->putString($color);
				}
			}
			if ($playerProtocol >= ProtocolInfo::PROTOCOL_465) {
				$this->putBool($skin->isPremiumSkin());
				$this->putBool($skin->isPersonaSkin());
				$this->putBool($skin->isCapeOnClassicSkin());
				$this->putBool($skin->isPrimaryUser());
				if ($playerProtocol >= ProtocolInfo::PROTOCOL_568) {
					$this->putBool($skin->isOverride());
				}
			}
		}
	}

	private function getSkinImage() : SkinImage
	{
		$width = $this->getLInt();
		$height = $this->getLInt();
		$data = $this->getString();
		return new SkinImage($height, $width, $data);
	}

	private function putSkinImage(SkinImage $image) : void
	{
		$this->putLInt($image->getWidth());
		$this->putLInt($image->getHeight());
		$this->putString($image->getData());
	}

	public function getSlot(int $playerProtocol, bool $withStackId = true) : ItemStackWrapper
	{
		if ($playerProtocol >= ProtocolInfo::PROTOCOL_431) {
			return $this->getItemStack($playerProtocol, $withStackId);
		}

		$id = $this->getVarInt();
		if ($id === 0) {
			return new ItemStackWrapper(0, ItemFactory::get(0, 0, 0));
		}

		$auxValue = $this->getVarInt();
		$data = $auxValue >> 8;
		if ($playerProtocol < ProtocolInfo::PROTOCOL_137 && $data === 0x7fff) {
			$data = -1;
		}
		$cnt = $auxValue & 0xff;

		if ($playerProtocol >= ProtocolInfo::PROTOCOL_419) {
			[$id, $data] = ItemTranslator::getInstance($playerProtocol)->fromNetworkId($id, $data);
		}

		$nbtLen = $this->getLShort();

		/** @var CompoundTag|null $nbt */
		$nbt = null;
		if ($playerProtocol >= ProtocolInfo::PROTOCOL_332) {
			if ($nbtLen === 0xffff) {
				$c = $this->getByte();
				if ($c !== 1) {
					throw new UnexpectedValueException("Unexpected NBT count $c");
				}
				$decodedNBT = (new NetworkLittleEndianNBTStream())->read($this->buffer, false, $this->offset, 512);
				if (!($decodedNBT instanceof CompoundTag)) {
					throw new UnexpectedValueException("Unexpected root tag type for itemstack");
				}
				$nbt = $decodedNBT;
			} elseif ($nbtLen !== 0) {
				throw new UnexpectedValueException("Unexpected fake NBT length $nbtLen");
			}
		} elseif ($nbtLen > 0) {
			$decodedNBT = (new LittleEndianNBTStream())->read($this->get($nbtLen));
			if (!($decodedNBT instanceof CompoundTag)) {
				throw new UnexpectedValueException("Unexpected root tag type for itemstack");
			}
			if ($playerProtocol < ProtocolInfo::PROTOCOL_137 && $id === ItemIds::FILLED_MAP && $decodedNBT->hasTag("map_uuid", StringTag::class)) {
				$decodedNBT->setLong("map_uuid", (int) $decodedNBT->getString("map_uuid"), true);
			}
			$nbt = $decodedNBT;
		}

		//TODO
		$canPlaceOn = $this->getVarInt();
		if ($canPlaceOn > 128) {
			throw new UnexpectedValueException("Too many canPlaceOn: $canPlaceOn");
		}
		for ($i = 0; $i < $canPlaceOn; ++$i) {
			$this->getString();
		}

		//TODO
		$canDestroy = $this->getVarInt();
		if ($canDestroy > 128) {
			throw new UnexpectedValueException("Too many canDestroy: $canDestroy");
		}
		for ($i = 0; $i < $canDestroy; ++$i) {
			$this->getString();
		}

		if ($id === ItemIds::SHIELD) {
			$this->getVarLong(); //"blocking tick" (ffs mojang)
		}

		if ($nbt !== null) {
			if ($nbt->hasTag(self::PM_ID_TAG, IntTag::class)) {
				$id = $nbt->getInt(self::PM_ID_TAG);
				$nbt->removeTag(self::PM_ID_TAG);
				if ($nbt->count() === 0) {
					$nbt = null;
				}
			}
			if ($nbt->hasTag(self::DAMAGE_TAG, IntTag::class)) {
				$data = $nbt->getInt(self::DAMAGE_TAG);
				$nbt->removeTag(self::DAMAGE_TAG);
				if ($nbt->count() === 0) {
					$nbt = null;
					goto end;
				}
			}
			if (($conflicted = $nbt->getTag(self::DAMAGE_TAG_CONFLICT_RESOLUTION)) !== null) {
				$nbt->removeTag(self::DAMAGE_TAG_CONFLICT_RESOLUTION);
				$conflicted->setName(self::DAMAGE_TAG);
				$nbt->setTag($conflicted);
			}
			if (($metaTag = $nbt->getTag(self::PM_META_TAG)) instanceof IntTag) {
				$data = $metaTag->getValue();
				$nbt->removeTag(self::PM_META_TAG);
				if ($nbt->count() === 0) {
					$nbt = null;
				}
			}
		}

		end:

		return ItemStackWrapper::legacy(ItemFactory::get($id, $data, $cnt, $nbt));
	}

	public function putSlot(ItemStackWrapper|Item $itemStackWrapper, int $playerProtocol, bool $withStackId = true, bool $saveOriginalID = true) : void
	{
		if ($itemStackWrapper instanceof Item) {
			$itemStackWrapper = ItemStackWrapper::legacy($itemStackWrapper);
		}

		if ($playerProtocol >= ProtocolInfo::PROTOCOL_431) {
			$this->putItemStack($itemStackWrapper, $playerProtocol, $withStackId, $saveOriginalID);
			return;
		}

		$item = $itemStackWrapper->getItemStack();
		if ($item->isNull()) {
			$this->putVarInt(0);
			return;
		}

		$id = $item->getId();
		$damage = $item->getDamage();

		$item->checkCompoundTag();

		$nbt = null;
		if ($item->hasCompoundTag()) {
			$nbt = clone $item->getNamedTag();
		}

		$protocolItem = $item->getItemProtocol($playerProtocol);
		if ($protocolItem !== null) {
			if ($nbt === null) {
				$nbt = new CompoundTag();
			}
			$nbt->setInt(self::PM_ID_TAG, $item->getId());
			$nbt->setInt(self::PM_META_TAG, $item->getDamage());

			[$id, $damage] = [$protocolItem->getId(), $protocolItem->getDamage()];
		}

		$isValidItemId = LegacyItemIdToStringIdMap::getInstance($playerProtocol)->legacyToString($id) !== null;
		if (!$isValidItemId) {
			[$id, $damage] = [Item::INFO_UPDATE, 0];
		}

		if ($playerProtocol >= ProtocolInfo::PROTOCOL_419) {
			$itemTranslator = ItemTranslator::getInstance($playerProtocol);
			$idMeta = $itemTranslator->toNetworkIdQuiet($id, $damage);

			if ($idMeta === null) {
				//Display unmapped items as INFO_UPDATE, but stick something in their NBT to make sure they don't stack with
				//other unmapped items.
				[$id, $damage] = $itemTranslator->toNetworkId(ItemIds::INFO_UPDATE, 0);
				if ($nbt === null) {
					$nbt = new CompoundTag();
				}
				$nbt->setInt(self::PM_ID_TAG, $item->getId());
				$nbt->setInt(self::PM_META_TAG, $item->getDamage());
			} else {
				[$id, $damage] = $idMeta;
				if ($item instanceof Durable && $item->getDamage() > 0) {
					if ($nbt !== null) {
						if (($existing = $nbt->getTag(self::DAMAGE_TAG)) !== null) {
							$nbt->removeTag(self::DAMAGE_TAG);
							$existing->setName(self::DAMAGE_TAG_CONFLICT_RESOLUTION);
							$nbt->setTag($existing);
						}
					} else {
						$nbt = new CompoundTag();
					}
					$nbt->setInt(self::DAMAGE_TAG, $item->getDamage());
				}
			}
		} else {
			if ($item instanceof Durable && $item->getDamage() > 0) {
				if ($nbt !== null) {
					if (($existing = $nbt->getTag(self::DAMAGE_TAG)) !== null) {
						$nbt->removeTag(self::DAMAGE_TAG);
						$existing->setName(self::DAMAGE_TAG_CONFLICT_RESOLUTION);
						$nbt->setTag($existing);
					}
				} else {
					$nbt = new CompoundTag();
				}
				$nbt->setInt(self::DAMAGE_TAG, $item->getDamage());
			}
		}

		$this->putVarInt($id);
		$auxValue = (($damage & 0x7fff) << 8) | $item->getCount();
		$this->putVarInt($auxValue);

		if ($nbt !== null) {
			if ($playerProtocol >= ProtocolInfo::PROTOCOL_332) {
				$this->putLShort(0xffff);
				$this->putByte(1); //TODO: some kind of count field? always 1 as of 1.9.0
				$this->put((new NetworkLittleEndianNBTStream())->write($nbt));
			} else {
				if ($playerProtocol < ProtocolInfo::PROTOCOL_137 && $item->getId() === ItemIds::FILLED_MAP && $item->getNamedTag()->hasTag("map_uuid", LongTag::class)) {
					$tag = $item->getNamedTag();
					$uuid = $tag->getLong("map_uuid");
					$nbt->setString("map_uuid", (string) $uuid, true);
				}
				$nbt = (new LittleEndianNBTStream())->write($nbt);
				$this->putLShort(strlen($nbt));
				$this->put($nbt);
			}
		} else {
			$this->putLShort(0);
		}

		$this->putVarInt(0); //CanPlaceOn entry count (TODO)
		$this->putVarInt(0); //CanDestroy entry count (TODO)

		if ($item->getId() === ItemIds::SHIELD) {
			$this->putVarLong(0); //"blocking tick" (ffs mojang)
		}
	}

	public function getItemStack(int $playerProtocol, bool $withStackId = true) : ItemStackWrapper
	{
		$netId = $this->getVarInt();
		if ($netId === 0) {
			return new ItemStackWrapper(0, ItemFactory::get(0, 0, 0));
		}

		$cnt = $this->getLShort();
		$netData = $this->getUnsignedVarInt();

		[$id, $meta] = ItemTranslator::getInstance($playerProtocol)->fromNetworkId($netId, $netData);

		$stackId = 0;
		if ($withStackId) {
			$hasNetId = $this->getBool();
			$stackId = $hasNetId ? $this->readServerItemStackId() : 0;
		}

		$this->getVarInt(); //blockRuntimeId

		$extraData = new NetworkBinaryStream($this->getString());
		return (static function () use ($extraData, $netId, $id, $meta, $cnt, $stackId) : ItemStackWrapper {
			$nbtLen = $extraData->getLShort();

			/** @var CompoundTag|null $nbt */
			$nbt = null;
			if ($nbtLen === 0xffff) {
				$nbtDataVersion = $extraData->getByte();
				if ($nbtDataVersion !== 1) {
					throw new UnexpectedValueException("Unexpected NBT data version $nbtDataVersion");
				}
				$decodedNBT = (new LittleEndianNBTStream())->read($extraData->buffer, false, $extraData->offset, 512);
				if (!($decodedNBT instanceof CompoundTag)) {
					throw new UnexpectedValueException("Unexpected root tag type for itemstack");
				}
				$nbt = $decodedNBT;
			} elseif ($nbtLen !== 0) {
				throw new UnexpectedValueException("Unexpected fake NBT length $nbtLen");
			}

			//TODO
			$canPlaceOn = $extraData->getLInt();
			if ($canPlaceOn > 128) {
				throw new UnexpectedValueException("Too many canPlaceOn: $canPlaceOn");
			}
			for ($i = 0; $i < $canPlaceOn; ++$i) {
				$extraData->get($extraData->getLShort());
			}

			//TODO
			$canDestroy = $extraData->getLInt();
			if ($canDestroy > 128) {
				throw new UnexpectedValueException("Too many canDestroy: $canDestroy");
			}
			for ($i = 0; $i < $canDestroy; ++$i) {
				$extraData->get($extraData->getLShort());
			}

			if ($id === ItemIds::SHIELD) {
				$extraData->getLLong(); //"blocking tick" (ffs mojang)
			}

			if (!$extraData->feof()) {
				throw new UnexpectedValueException("Unexpected trailing extradata for network item $netId");
			}

			if ($nbt !== null) {
				if ($nbt->hasTag(self::PM_ID_TAG, IntTag::class)) {
					$id = $nbt->getInt(self::PM_ID_TAG);
					$nbt->removeTag(self::PM_ID_TAG);
					if ($nbt->count() === 0) {
						$nbt = null;
					}
				}
				if ($nbt->hasTag(self::DAMAGE_TAG, IntTag::class)) {
					$meta = $nbt->getInt(self::DAMAGE_TAG);
					$nbt->removeTag(self::DAMAGE_TAG);
					if (($conflicted = $nbt->getTag(self::DAMAGE_TAG_CONFLICT_RESOLUTION)) !== null) {
						$nbt->removeTag(self::DAMAGE_TAG_CONFLICT_RESOLUTION);
						$conflicted->setName(self::DAMAGE_TAG);
						$nbt->setTag($conflicted);
					} elseif ($nbt->count() === 0) {
						$nbt = null;
					}
				} elseif (($metaTag = $nbt->getTag(self::PM_META_TAG)) instanceof IntTag) {
					//TODO HACK: This foul-smelling code ensures that we can correctly deserialize an item when the
					//client sends it back to us, because as of 1.16.220, blockitems quietly discard their metadata
					//client-side. Aside from being very annoying, this also breaks various server-side behaviours.
					$meta = $metaTag->getValue();
					$nbt->removeTag(self::PM_META_TAG);
					if ($nbt->count() === 0) {
						$nbt = null;
					}
				}
			}
			return new ItemStackWrapper($stackId, ItemFactory::get($id, $meta, $cnt, $nbt));
		})();
	}

	public function putItemStack(ItemStackWrapper|Item $itemStackWrapper, int $playerProtocol, bool $withStackId = true, bool $saveOriginalID = true) : void
	{
		if ($itemStackWrapper instanceof Item) {
			$itemStackWrapper = ItemStackWrapper::legacy($itemStackWrapper);
		}

		$item = $itemStackWrapper->getItemStack();
		if ($item->isNull()) {
			$this->putVarInt(0);
			return;
		}

		$id = $item->getId();
		$coreData = $item->getDamage();

		$isBlockItem = $item->getId() < 256;

		$nbt = null;
		if ($item->hasCompoundTag()) {
			$nbt = clone $item->getNamedTag();
		}

		$protocolItem = $item->getItemProtocol($playerProtocol);
		if ($protocolItem !== null) {
			if ($nbt === null) {
				$nbt = new CompoundTag();
			}
			$nbt->setInt(self::PM_ID_TAG, $item->getId());
			$nbt->setInt(self::PM_META_TAG, $item->getDamage());

			[$id, $coreData] = [$protocolItem->getId(), $protocolItem->getDamage()];
		}

		$isValidItemId = LegacyItemIdToStringIdMap::getInstance($playerProtocol)->legacyToString($id) !== null;
		if (!$isValidItemId) {
			[$id, $coreData] = [Item::INFO_UPDATE, 0];
		}

		$itemTranslator = ItemTranslator::getInstance($playerProtocol);
		$idMeta = $itemTranslator->toNetworkIdQuiet($id, $coreData);
		if ($idMeta === null) {
			//Display unmapped items as INFO_UPDATE, but stick something in their NBT to make sure they don't stack with
			//other unmapped items.
			[$netId, $netData] = $itemTranslator->toNetworkId(ItemIds::INFO_UPDATE, 0);
			if ($nbt === null) {
				$nbt = new CompoundTag();
			}
			$nbt->setInt(self::PM_ID_TAG, $id);
			$nbt->setInt(self::PM_META_TAG, $coreData);
		} else {
			[$netId, $netData] = $idMeta;

			if ($item instanceof Durable && $coreData > 0) {
				if ($nbt !== null) {
					if (($existing = $nbt->getTag(self::DAMAGE_TAG)) !== null) {
						$nbt->removeTag(self::DAMAGE_TAG);
						$existing->setName(self::DAMAGE_TAG_CONFLICT_RESOLUTION);
						$nbt->setTag($existing);
					}
				} else {
					$nbt = new CompoundTag();
				}
				$nbt->setInt(self::DAMAGE_TAG, $coreData);
			} elseif ($isBlockItem && $coreData !== 0 && $saveOriginalID) {
				//TODO HACK: This foul-smelling code ensures that we can correctly deserialize an item when the
				//client sends it back to us, because as of 1.16.220, blockitems quietly discard their metadata
				//client-side. Aside from being very annoying, this also breaks various server-side behaviours.
				if ($nbt === null) {
					$nbt = new CompoundTag();
				}
				$nbt->setInt(self::PM_META_TAG, $coreData);
			}
		}

		$this->putVarInt($netId);
		$this->putLShort($item->getCount());
		$this->putUnsignedVarInt($netData);

		if ($withStackId) {
			$hasNetId = $itemStackWrapper->getStackId() !== 0;
			$this->putBool($hasNetId);
			if ($hasNetId) {
				$this->writeServerItemStackId($itemStackWrapper->getStackId());
			}
		}

		$blockRuntimeId = 0;
		if ($isBlockItem) {
			$block = $item->getBlock();
			if ($block->getId() !== BlockIds::AIR) {
				$blockRuntimeId = RuntimeBlockMapping::getInstance($playerProtocol)->toRuntimeId($block->getFullId());
			}
		}
		$this->putVarInt($blockRuntimeId);

		$this->putString(
			(static function () use ($nbt, $item) : string {
				$extraData = new NetworkBinaryStream();

				if ($nbt !== null) {
					$extraData->putLShort(0xffff);
					$extraData->putByte(1); //TODO: NBT data version (?)
					$extraData->put((new LittleEndianNBTStream())->write($nbt));
				} else {
					$extraData->putLShort(0);
				}

				$extraData->putLInt(0); //CanPlaceOn entry count (TODO)
				$extraData->putLInt(0); //CanDestroy entry count (TODO)

				if ($item->getId() === ItemIds::SHIELD) {
					$extraData->putLLong(0); //"blocking tick" (ffs mojang)
				}
				return $extraData->getBuffer();
			})()
		);
	}

	public function getNetworkItemStackDescriptor(int $playerProtocol) : ItemStackWrapper
	{
		$netId = $this->getLShort();
		$cnt = $this->getLShort();
		$netData = $this->getUnsignedVarInt();

		$stackId = 0;
		if ($this->getBool()) {
			$this->getUnsignedVarInt(); // item stack net ID variant
			$stackId = $this->readServerItemStackId();
		}

		$this->getUnsignedVarInt(); // blockRuntimeId

		$extraData = new NetworkBinaryStream($this->getString());
		if ($netId === 0) {
			return new ItemStackWrapper(0, ItemFactory::get(0, 0, 0));
		}

		[$id, $meta] = ItemTranslator::getInstance($playerProtocol)->fromNetworkId($netId, $netData);
		return (static function () use ($extraData, $netId, $id, $meta, $cnt, $stackId) : ItemStackWrapper {
			$nbtLen = $extraData->getLShort();

			/** @var CompoundTag|null $nbt */
			$nbt = null;
			if ($nbtLen === 0xffff) {
				$nbtDataVersion = $extraData->getByte();
				if ($nbtDataVersion !== 1) {
					throw new UnexpectedValueException("Unexpected NBT data version $nbtDataVersion");
				}
				$decodedNBT = (new LittleEndianNBTStream())->read($extraData->buffer, false, $extraData->offset, 512);
				if (!($decodedNBT instanceof CompoundTag)) {
					throw new UnexpectedValueException("Unexpected root tag type for itemstack");
				}
				$nbt = $decodedNBT;
			} elseif ($nbtLen !== 0) {
				throw new UnexpectedValueException("Unexpected fake NBT length $nbtLen");
			}

			$canPlaceOn = $extraData->getLInt();
			if ($canPlaceOn > 128) {
				throw new UnexpectedValueException("Too many canPlaceOn: $canPlaceOn");
			}
			for ($i = 0; $i < $canPlaceOn; ++$i) {
				$extraData->get($extraData->getLShort());
			}

			$canDestroy = $extraData->getLInt();
			if ($canDestroy > 128) {
				throw new UnexpectedValueException("Too many canDestroy: $canDestroy");
			}
			for ($i = 0; $i < $canDestroy; ++$i) {
				$extraData->get($extraData->getLShort());
			}

			if ($id === ItemIds::SHIELD) {
				$extraData->getLLong();
			}

			if (!$extraData->feof()) {
				throw new UnexpectedValueException("Unexpected trailing extradata for network item $netId");
			}

			if ($nbt !== null) {
				if ($nbt->hasTag(self::PM_ID_TAG, IntTag::class)) {
					$id = $nbt->getInt(self::PM_ID_TAG);
					$nbt->removeTag(self::PM_ID_TAG);
					if ($nbt->count() === 0) {
						$nbt = null;
					}
				}
				if ($nbt !== null && $nbt->hasTag(self::DAMAGE_TAG, IntTag::class)) {
					$meta = $nbt->getInt(self::DAMAGE_TAG);
					$nbt->removeTag(self::DAMAGE_TAG);
					if (($conflicted = $nbt->getTag(self::DAMAGE_TAG_CONFLICT_RESOLUTION)) !== null) {
						$nbt->removeTag(self::DAMAGE_TAG_CONFLICT_RESOLUTION);
						$conflicted->setName(self::DAMAGE_TAG);
						$nbt->setTag($conflicted);
					} elseif ($nbt->count() === 0) {
						$nbt = null;
					}
				} elseif ($nbt !== null && ($metaTag = $nbt->getTag(self::PM_META_TAG)) instanceof IntTag) {
					$meta = $metaTag->getValue();
					$nbt->removeTag(self::PM_META_TAG);
					if ($nbt->count() === 0) {
						$nbt = null;
					}
				}
			}

			return new ItemStackWrapper($stackId, ItemFactory::get($id, $meta, $cnt, $nbt));
		})();
	}

	public function putNetworkItemStackDescriptor(ItemStackWrapper|Item $itemStackWrapper, int $playerProtocol, bool $saveOriginalID = true) : void
	{
		if ($itemStackWrapper instanceof Item) {
			$itemStackWrapper = ItemStackWrapper::legacy($itemStackWrapper);
		}

		$item = $itemStackWrapper->getItemStack();
		if ($item->isNull()) {
			$this->putLShort(0);
			$this->putLShort(0);
			$this->putUnsignedVarInt(0);
			$this->putBool(false);
			$this->putUnsignedVarInt(0);
			$this->putString("");
			return;
		}

		$id = $item->getId();
		$coreData = $item->getDamage();
		$isBlockItem = $item->getId() < 256;

		$nbt = null;
		if ($item->hasCompoundTag()) {
			$nbt = clone $item->getNamedTag();
		}

		$protocolItem = $item->getItemProtocol($playerProtocol);
		if ($protocolItem !== null) {
			if ($nbt === null) {
				$nbt = new CompoundTag();
			}
			$nbt->setInt(self::PM_ID_TAG, $item->getId());
			$nbt->setInt(self::PM_META_TAG, $item->getDamage());
			[$id, $coreData] = [$protocolItem->getId(), $protocolItem->getDamage()];
		}

		if (LegacyItemIdToStringIdMap::getInstance($playerProtocol)->legacyToString($id) === null) {
			[$id, $coreData] = [Item::INFO_UPDATE, 0];
		}

		$itemTranslator = ItemTranslator::getInstance($playerProtocol);
		$idMeta = $itemTranslator->toNetworkIdQuiet($id, $coreData);
		if ($idMeta === null) {
			[$netId, $netData] = $itemTranslator->toNetworkId(ItemIds::INFO_UPDATE, 0);
			if ($nbt === null) {
				$nbt = new CompoundTag();
			}
			$nbt->setInt(self::PM_ID_TAG, $id);
			$nbt->setInt(self::PM_META_TAG, $coreData);
		} else {
			[$netId, $netData] = $idMeta;

			if ($item instanceof Durable && $coreData > 0) {
				if ($nbt !== null) {
					if (($existing = $nbt->getTag(self::DAMAGE_TAG)) !== null) {
						$nbt->removeTag(self::DAMAGE_TAG);
						$existing->setName(self::DAMAGE_TAG_CONFLICT_RESOLUTION);
						$nbt->setTag($existing);
					}
				} else {
					$nbt = new CompoundTag();
				}
				$nbt->setInt(self::DAMAGE_TAG, $coreData);
			} elseif ($isBlockItem && $coreData !== 0 && $saveOriginalID) {
				if ($nbt === null) {
					$nbt = new CompoundTag();
				}
				$nbt->setInt(self::PM_META_TAG, $coreData);
			}
		}

		$this->putLShort($netId);
		$this->putLShort($item->getCount());
		$this->putUnsignedVarInt($netData);

		$hasNetId = $itemStackWrapper->getStackId() !== 0;
		$this->putBool($hasNetId);
		if ($hasNetId) {
			$this->putUnsignedVarInt($itemStackWrapper->getStackId());
			$this->writeServerItemStackId($itemStackWrapper->getStackId());
		}

		$blockRuntimeId = 0;
		if ($isBlockItem) {
			$block = $item->getBlock();
			if ($block->getId() !== BlockIds::AIR) {
				$blockRuntimeId = RuntimeBlockMapping::getInstance($playerProtocol)->toRuntimeId($block->getFullId());
			}
		}
		$this->putUnsignedVarInt($blockRuntimeId);

		$this->putString((static function () use ($nbt, $item) : string {
			$extraData = new NetworkBinaryStream();
			if ($nbt !== null) {
				$extraData->putLShort(0xffff);
				$extraData->putByte(1);
				$extraData->put((new LittleEndianNBTStream())->write($nbt));
			} else {
				$extraData->putLShort(0);
			}
			$extraData->putLInt(0);
			$extraData->putLInt(0);
			if ($item->getId() === ItemIds::SHIELD) {
				$extraData->putLLong(0);
			}
			return $extraData->getBuffer();
		})());
	}

	public function getRecipeIngredient(int $playerProtocol) : Item
	{
		$id = $this->getVarInt();
		if ($id === 0) {
			return ItemFactory::get(ItemIds::AIR, 0, 0);
		}
		$meta = $this->getVarInt();
		if ($playerProtocol >= ProtocolInfo::PROTOCOL_419) {
			[$id, $meta] = ItemTranslator::getInstance($playerProtocol)->fromNetworkId($id, $meta);
		} elseif ($meta === 0x7fff) {
			$meta = -1;
		}
		$count = $this->getVarInt();
		return ItemFactory::get($id, $meta, $count);
	}

	public function putRecipeIngredient(Item $item, int $playerProtocol) : void
	{
		if ($playerProtocol < ProtocolInfo::PROTOCOL_554) {
			if ($item->isNull()) {
				$this->putVarInt(0);
			} else {
				$id = $item->getId();
				$damage = $item->getDamage();
				if ($playerProtocol >= ProtocolInfo::PROTOCOL_419) {
					$itemTranslator = ItemTranslator::getInstance($playerProtocol);
					if ($item->hasAnyDamageValue()) {
						[$id, ] = $itemTranslator->toNetworkId($id, 0);
						$damage = 0x7fff;
					} else {
						[$id, $damage] = $itemTranslator->toNetworkId($id, $damage);
					}
				} else {
					$damage = $damage & 0x7fff;
				}
				$this->putVarInt($id);
				$this->putVarInt($damage);
				$this->putVarInt($item->getCount());
			}
		} else {
			if ($item->isNull()) {
				$this->putByte(0); // internal item descriptor type
				$this->putVarInt(0);
			} else {
				$this->putByte(1); // internal item descriptor type

				$itemTranslator = ItemTranslator::getInstance($playerProtocol);
				if ($item->hasAnyDamageValue()) {
					[$netId,] = $itemTranslator->toNetworkId($item->getId(), 0);
					$netData = 0x7fff;
				} else {
					[$netId, $netData] = $itemTranslator->toNetworkId($item->getId(), $item->getDamage());
				}

				$this->putLShort($netId);
				$this->putLShort($netData);
				$this->putVarInt($item->getCount());
			}
		}
	}

	/**
	 * Decodes entity metadata from the stream.
	 *
	 * @param bool $types Whether to include metadata types along with values in the returned array
	 */
	public function getEntityMetadata(int $playerProtocol, bool $types = true) : array
	{
		$count = $this->getUnsignedVarInt();
		if ($count > 128) {
			throw new UnexpectedValueException("Too many actor metadata: $count");
		}
		$data = [];
		for ($i = 0; $i < $count; ++$i) {
			$key = $this->getUnsignedVarInt();
			$type = $this->getUnsignedVarInt();
			$value = null;
			switch ($type) {
				case Entity::DATA_TYPE_BYTE:
					$value = (ord($this->get(1)));
					break;
				case Entity::DATA_TYPE_SHORT:
					$value = $this->getSignedLShort();
					break;
				case Entity::DATA_TYPE_INT:
					$value = $this->getVarInt();
					break;
				case Entity::DATA_TYPE_FLOAT:
					$value = $this->getLFloat();
					break;
				case Entity::DATA_TYPE_STRING:
					$value = $this->getString();
					break;
				case Entity::DATA_TYPE_SLOT:
					if ($playerProtocol >= ProtocolInfo::PROTOCOL_361) {
						$value = (new NetworkLittleEndianNBTStream())->read($this->buffer, false, $this->offset, 512);
					} else {
						$value = $this->getSlot($playerProtocol);
					}
					break;
				case Entity::DATA_TYPE_POS:
					$value = new Vector3(0, 0, 0);
					$this->getSignedBlockPosition($value->x, $value->y, $value->z);
					break;
				case Entity::DATA_TYPE_LONG:
					$value = $this->getVarLong();
					break;
				case Entity::DATA_TYPE_VECTOR3F:
					$value = $this->getVector3();
					break;
				default:
					throw new UnexpectedValueException("Invalid data type " . $type);
			}
			if ($types) {
				$data[$key] = [$type, $value];
			} else {
				$data[$key] = $value;
			}
		}

		return EntityMetadataTranslator::getInstance()->fromNetworkIds($data, $playerProtocol);
	}

	/**
	 * Writes entity metadata to the packet buffer.
	 */
	public function putEntityMetadata(array $metadata, int $playerProtocol) : void
	{
		$metadata = EntityMetadataTranslator::getInstance()->toNetworkIds($metadata, $playerProtocol);

		$this->putUnsignedVarInt(count($metadata));
		foreach ($metadata as $key => $d) {
			$this->putUnsignedVarInt($key); //data key
			$this->putUnsignedVarInt($d[0]); //data type
			switch ($d[0]) {
				case Entity::DATA_TYPE_BYTE:
					$this->putByte($d[1]);
					break;
				case Entity::DATA_TYPE_SHORT:
					$this->putLShort($d[1]);
					break;
				case Entity::DATA_TYPE_INT:
					$this->putVarInt($d[1]);
					break;
				case Entity::DATA_TYPE_FLOAT:
					$this->putLFloat($d[1]);
					break;
				case Entity::DATA_TYPE_STRING:
					$this->putString($d[1]);
					break;
				case Entity::DATA_TYPE_SLOT:
					if ($playerProtocol >= ProtocolInfo::PROTOCOL_361) {
						($this->buffer .= (new NetworkLittleEndianNBTStream())->write($d[1]->getNamedTag()));
					} else {
						$this->putSlot($d[1], $playerProtocol);
					}
					break;
				case Entity::DATA_TYPE_POS:
					$v = $d[1];
					if ($v !== null) {
						$this->putSignedBlockPosition($v->x, $v->y, $v->z);
					} else {
						$this->putSignedBlockPosition(0, 0, 0);
					}
					break;
				case Entity::DATA_TYPE_LONG:
					$this->putVarLong($d[1]);
					break;
				case Entity::DATA_TYPE_VECTOR3F:
					$this->putVector3Nullable($d[1]);
					break;
				default:
					throw new UnexpectedValueException("Invalid data type " . $d[0]);
			}
		}
	}

	/**
	 * Reads a list of Attributes from the stream.
	 * @return Attribute[]
	 *
	 * @throws UnexpectedValueException if reading an attribute with an unrecognized name
	 */
	public function getAttributeList(int $playerProtocol) : array
	{
		$list = [];
		$count = $this->getUnsignedVarInt();

		for ($i = 0; $i < $count; ++$i) {
			$min = $this->getLFloat();
			$max = $this->getLFloat();
			$current = $this->getLFloat();
			if ($playerProtocol >= ProtocolInfo::PROTOCOL_729) {
				$this->getLFloat(); //default min value
				$this->getLFloat(); //default max value
			}
			$default = $this->getLFloat();
			$name = $this->getString();
			$modifiers = [];
			if ($playerProtocol >= ProtocolInfo::PROTOCOL_544) {
				for ($j = 0, $modifierCount = $this->getUnsignedVarInt(); $j < $modifierCount; $j++) {
					$modifiers[] = AttributeModifier::read($this);
				}
			}

			$attr = Attribute::getAttributeByName($name);
			if ($attr !== null) {
				$attr->setMinValue($min);
				$attr->setMaxValue($max);
				$attr->setValue($current);
				$attr->setDefaultValue($default);
				$attr->setModifiers($modifiers);

				$list[] = $attr;
			} else {
				throw new UnexpectedValueException("Unknown attribute type \"$name\"");
			}
		}

		return $list;
	}

	/**
	 * Writes a list of Attributes to the packet buffer using the standard format.
	 */
	public function putAttributeList(int $playerProtocol, Attribute ...$attributes) : void
	{
		$this->putUnsignedVarInt(count($attributes));
		foreach ($attributes as $attribute) {
			$this->putLFloat($attribute->getMinValue());
			$this->putLFloat($attribute->getMaxValue());
			$this->putLFloat($attribute->getValue());
			if ($playerProtocol >= ProtocolInfo::PROTOCOL_729) {
				$this->putLFloat($attribute->getMinValue()); //default min value
				$this->putLFloat($attribute->getMaxValue()); //default max value
			}
			$this->putLFloat($attribute->getDefaultValue());
			$this->putString($attribute->getName());
			if ($playerProtocol >= ProtocolInfo::PROTOCOL_544) {
				$this->putUnsignedVarInt(count($attribute->getModifiers()));
				foreach ($attribute->getModifiers() as $modifier) {
					$modifier->write($this);
				}
			}
		}
	}

	/**
	 * Reads and returns an EntityUniqueID
	 */
	final public function getEntityUniqueId() : int
	{
		return $this->getVarLong();
	}

	/**
	 * Writes an EntityUniqueID
	 */
	public function putEntityUniqueId(int $eid) : void
	{
		$this->putVarLong($eid);
	}

	/**
	 * Reads and returns an EntityRuntimeID
	 */
	final public function getEntityRuntimeId() : int
	{
		return $this->getUnsignedVarLong();
	}

	/**
	 * Writes an EntityRuntimeID
	 */
	public function putEntityRuntimeId(int $eid) : void
	{
		$this->putUnsignedVarLong($eid);
	}

	/**
	 * Reads an block position with unsigned Y coordinate.
	 *
	 * @param int &$x
	 * @param int &$y
	 * @param int &$z
	 */
	public function getBlockPosition(&$x, &$y, &$z) : void
	{
		$x = $this->getVarInt();
		$y = $this->getUnsignedVarInt();
		$z = $this->getVarInt();
	}

	/**
	 * Writes a block position with unsigned Y coordinate.
	 */
	public function putBlockPosition(int $x, int $y, int $z) : void
	{
		$this->putVarInt($x);
		$this->putUnsignedVarInt($y);
		$this->putVarInt($z);
	}

	/**
	 * Reads a block position with a signed Y coordinate.
	 *
	 * @param int &$x
	 * @param int &$y
	 * @param int &$z
	 */
	public function getSignedBlockPosition(&$x, &$y, &$z) : void
	{
		$x = $this->getVarInt();
		$y = $this->getVarInt();
		$z = $this->getVarInt();
	}

	/**
	 * Writes a block position with a signed Y coordinate.
	 */
	public function putSignedBlockPosition(int $x, int $y, int $z) : void
	{
		$this->putVarInt($x);
		$this->putVarInt($y);
		$this->putVarInt($z);
	}

	/**
	 * Reads a floating-point Vector3 object with coordinates rounded to 4 decimal places.
	 */
	public function getVector3() : Vector3
	{
		return new Vector3(
			$this->getLFloat(),
			$this->getLFloat(),
			$this->getLFloat()
		);
	}

	/**
	 * Reads a floating-point Vector2 object with coordinates rounded to 4 decimal places.
	 *
	 * @throws BinaryDataException
	 */
	public function getVector2() : Vector2
	{
		$x = $this->getLFloat();
		$y = $this->getLFloat();
		return new Vector2($x, $y);
	}

	/**
	 * Writes a floating-point Vector3 object, or 3x zero if null is given.
	 *
	 * Note: ONLY use this where it is reasonable to allow not specifying the vector.
	 * For all other purposes, use the non-nullable version.
	 *
	 * @see NetworkBinaryStream::putVector3()
	 */
	public function putVector3Nullable(?Vector3 $vector) : void
	{
		if ($vector) {
			$this->putVector3($vector);
		} else {
			$this->putLFloat(0.0);
			$this->putLFloat(0.0);
			$this->putLFloat(0.0);
		}
	}

	/**
	 * Writes a floating-point Vector3 object
	 */
	public function putVector3(Vector3 $vector) : void
	{
		$this->putLFloat($vector->x);
		$this->putLFloat($vector->y);
		$this->putLFloat($vector->z);
	}

	/**
	 * Writes a floating-point Vector2 object
	 */
	public function putVector2(Vector2 $vector2) : void
	{
		$this->putLFloat($vector2->x);
		$this->putLFloat($vector2->y);
	}

	public function getByteRotation() : float
	{
		return (float) ((ord($this->get(1))) * (360 / 256));
	}

	public function putByteRotation(float $rotation) : void
	{
		($this->buffer .= chr((int) ($rotation / (360 / 256))));
	}

	/**
	 * Reads gamerules
	 * TODO: implement this properly
	 *
	 * @return array, members are in the structure [name => [type, value, isPlayerModifiable]]
	 */
	public function getGameRules(int $playerProtocol) : array
	{
		$count = $this->getUnsignedVarInt();
		$rules = [];
		for ($i = 0; $i < $count; ++$i) {
			$name = $this->getString();
			if ($playerProtocol >= ProtocolInfo::PROTOCOL_440) {
				$isPlayerModifiable = $this->getBool();
			}
			$type = $this->getUnsignedVarInt();
			$value = null;
			switch ($type) {
				case GameRuleType::BOOL:
					$value = (($this->get(1) !== "\x00"));
					break;
				case GameRuleType::INT:
					$value = $this->getUnsignedVarInt();
					break;
				case GameRuleType::FLOAT:
					$value = $this->getLFloat();
					break;
			}

			$rules[$name] = [$type, $value, $isPlayerModifiable ?? false];
		}

		return $rules;
	}

	/**
	 * Writes a gamerule array, members should be in the structure [name => [type, value, isPlayerModifiable]]
	 * TODO: implement this properly
	 */
	public function putGameRules(array $rules, int $playerProtocol) : void
	{
		$this->putUnsignedVarInt(count($rules));
		foreach ($rules as $name => $rule) {
			$this->putString($name);
			if ($playerProtocol >= ProtocolInfo::PROTOCOL_440) {
				$this->putBool($rule[2] ?? false);
			}
			$this->putUnsignedVarInt($rule[0]);
			switch ($rule[0]) {
				case GameRuleType::BOOL:
					$this->putBool($rule[1]);
					break;
				case GameRuleType::INT:
					$this->putUnsignedVarInt($rule[1]);
					break;
				case GameRuleType::FLOAT:
					$this->putLFloat($rule[1]);
					break;
			}
		}
	}

	protected function getEntityLink(int $playerProtocol) : EntityLink
	{
		$link = new EntityLink();

		$link->fromEntityUniqueId = $this->getEntityUniqueId();
		$link->toEntityUniqueId = $this->getEntityUniqueId();
		$link->type = $this->getByte();
		if ($playerProtocol >= ProtocolInfo::PROTOCOL_137) {
			$link->immediate = $this->getBool();
			if ($playerProtocol >= ProtocolInfo::PROTOCOL_407) {
				$link->causedByRider = $this->getBool();
				if ($playerProtocol >= ProtocolInfo::PROTOCOL_712) {
					$link->vehicleAngularVelocity = $this->getLFloat();
				}
			}
		}

		return $link;
	}

	protected function putEntityLink(EntityLink $link, int $playerProtocol) : void
	{
		$this->putEntityUniqueId($link->fromEntityUniqueId);
		$this->putEntityUniqueId($link->toEntityUniqueId);
		$this->putByte($link->type);
		if ($playerProtocol >= ProtocolInfo::PROTOCOL_137) {
			$this->putBool($link->immediate);
			if ($playerProtocol >= ProtocolInfo::PROTOCOL_407) {
				$this->putBool($link->causedByRider);
				if ($playerProtocol >= ProtocolInfo::PROTOCOL_712) {
					$this->putLFloat($link->vehicleAngularVelocity);
				}
			}
		}
	}

	protected function getCommandOriginData() : CommandOriginData
	{
		$result = new CommandOriginData();

		$result->type = $this->getUnsignedVarInt();
		$result->uuid = $this->getUUID();
		$result->requestId = $this->getString();

		if ($result->type === CommandOriginData::ORIGIN_DEV_CONSOLE || $result->type === CommandOriginData::ORIGIN_TEST) {
			$result->playerActorUniqueId = $this->getVarLong();
		}

		return $result;
	}

	protected function putCommandOriginData(CommandOriginData $data) : void
	{
		$this->putUnsignedVarInt($data->type);
		$this->putUUID($data->uuid);
		$this->putString($data->requestId);

		if ($data->type === CommandOriginData::ORIGIN_DEV_CONSOLE || $data->type === CommandOriginData::ORIGIN_TEST) {
			$this->putVarLong($data->playerActorUniqueId);
		}
	}

	protected function getStructureSettings() : StructureSettings
	{
		$result = new StructureSettings();

		$result->paletteName = $this->getString();

		$result->ignoreEntities = $this->getBool();
		$result->ignoreBlocks = $this->getBool();

		$this->getBlockPosition($result->structureSizeX, $result->structureSizeY, $result->structureSizeZ);
		$this->getBlockPosition($result->structureOffsetX, $result->structureOffsetY, $result->structureOffsetZ);

		$result->lastTouchedByPlayerID = $this->getEntityUniqueId();
		$result->rotation = $this->getByte();
		$result->mirror = $this->getByte();
		$result->integrityValue = $this->getFloat();
		$result->integritySeed = $this->getInt();

		return $result;
	}

	protected function putStructureSettings(StructureSettings $structureSettings) : void
	{
		$this->putString($structureSettings->paletteName);

		$this->putBool($structureSettings->ignoreEntities);
		$this->putBool($structureSettings->ignoreBlocks);

		$this->putBlockPosition($structureSettings->structureSizeX, $structureSettings->structureSizeY, $structureSettings->structureSizeZ);
		$this->putBlockPosition($structureSettings->structureOffsetX, $structureSettings->structureOffsetY, $structureSettings->structureOffsetZ);

		$this->putEntityUniqueId($structureSettings->lastTouchedByPlayerID);
		$this->putByte($structureSettings->rotation);
		$this->putByte($structureSettings->mirror);
		$this->putFloat($structureSettings->integrityValue);
		$this->putInt($structureSettings->integritySeed);
	}

	protected function getStructureEditorData(int $protocolVersion) : StructureEditorData
	{
		$result = new StructureEditorData();

		$result->structureName = $this->getString();
		if ($protocolVersion >= ProtocolInfo::PROTOCOL_776) {
			$result->filteredStructureName = $this->getString();
		}
		$result->structureDataField = $this->getString();

		$result->includePlayers = $this->getBool();
		$result->showBoundingBox = $this->getBool();

		$result->structureBlockType = $this->getVarInt();
		$result->structureSettings = $this->getStructureSettings();
		$result->structureRedstoneSaveMode = $this->getVarInt();

		return $result;
	}

	protected function putStructureEditorData(StructureEditorData $structureEditorData, int $protocolVersion) : void
	{
		$this->putString($structureEditorData->structureName);
		if ($protocolVersion >= ProtocolInfo::PROTOCOL_776) {
			$this->putString($structureEditorData->filteredStructureName);
		}
		$this->putString($structureEditorData->structureDataField);

		$this->putBool($structureEditorData->includePlayers);
		$this->putBool($structureEditorData->showBoundingBox);

		$this->putVarInt($structureEditorData->structureBlockType);
		$this->putStructureSettings($structureEditorData->structureSettings);
		$this->putVarInt($structureEditorData->structureRedstoneSaveMode);
	}

	public function getNbtRoot() : NamedTag
	{
		$offset = $this->getOffset();
		try {
			$result = (new NetworkLittleEndianNBTStream())->read($this->getBuffer(), false, $offset, 512);
			assert($result instanceof NamedTag, "doMultiple is false so we should definitely have a NamedTag here");
			return $result;
		} finally {
			$this->setOffset($offset);
		}
	}

	public function getNbtCompoundRoot() : CompoundTag
	{
		$root = $this->getNbtRoot();
		if (!($root instanceof CompoundTag)) {
			throw new UnexpectedValueException("Expected TAG_Compound root");
		}
		return $root;
	}

	public function readRecipeNetId() : int
	{
		return $this->getUnsignedVarInt();
	}

	public function writeRecipeNetId(int $id) : void
	{
		$this->putUnsignedVarInt($id);
	}

	public function readCreativeItemNetId() : int
	{
		return $this->getUnsignedVarInt();
	}

	public function writeCreativeItemNetId(int $id) : void
	{
		$this->putUnsignedVarInt($id);
	}

	/**
	 * This is a union of ItemStackRequestId, LegacyItemStackRequestId, and ServerItemStackId, used in serverbound
	 * packets to allow the client to refer to server known items, or items which may have been modified by a previous
	 * as-yet unacknowledged request from the client.
	 *
	 * - Server itemstack ID is positive
	 * - InventoryTransaction "legacy" request ID is negative and even
	 * - ItemStackRequest request ID is negative and odd
	 * - 0 refers to an empty itemstack (air)
	 */
	public function readItemStackNetIdVariant() : int
	{
		return $this->getVarInt();
	}

	/**
	 * This is a union of ItemStackRequestId, LegacyItemStackRequestId, and ServerItemStackId, used in serverbound
	 * packets to allow the client to refer to server known items, or items which may have been modified by a previous
	 * as-yet unacknowledged request from the client.
	 */
	public function writeItemStackNetIdVariant(int $id) : void
	{
		$this->putVarInt($id);
	}

	public function readItemStackRequestId() : int
	{
		return $this->getVarInt();
	}

	public function writeItemStackRequestId(int $id) : void
	{
		$this->putVarInt($id);
	}

	public function readLegacyItemStackRequestId() : int
	{
		return $this->getVarInt();
	}

	public function writeLegacyItemStackRequestId(int $id) : void
	{
		$this->putVarInt($id);
	}

	public function readServerItemStackId() : int
	{
		return $this->getVarInt();
	}

	public function writeServerItemStackId(int $id) : void
	{
		$this->putVarInt($id);
	}

	/**
	 * @phpstan-template T
	 * @phpstan-param \Closure() : T $reader
	 * @phpstan-return T|null
	 */
	public function readOptional(\Closure $reader) : mixed
	{
		if ($this->getBool()) {
			return $reader();
		}
		return null;
	}

	/**
	 * @phpstan-template T
	 * @phpstan-param T|null $value
	 * @phpstan-param \Closure(T) : void $writer
	 */
	public function writeOptional(mixed $value, \Closure $writer) : void
	{
		if ($value !== null) {
			$this->putBool(true);
			$writer($value);
		} else {
			$this->putBool(false);
		}
	}
	protected function prepareGeometryDataForOld(?string $skinGeometryData) : ?string
	{
		if (!empty($skinGeometryData)) {
			if (($tempData = @json_decode($skinGeometryData, true))) {
				unset($tempData["format_version"]);
				return json_encode($tempData);
			}
		}

		return $skinGeometryData;
	}
}
