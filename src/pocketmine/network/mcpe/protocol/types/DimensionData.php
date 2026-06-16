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

namespace pocketmine\network\mcpe\protocol\types;

use pocketmine\network\mcpe\NetworkBinaryStream;
use pocketmine\network\mcpe\protocol\ProtocolInfo;

final class DimensionData
{
	public function __construct(
		private int $maxHeight,
		private int $minHeight,
		private int $generator,
		private int $dimensionType = 0
	) {
	}

	public function getMaxHeight() : int
	{
		return $this->maxHeight;
	}

	public function getMinHeight() : int
	{
		return $this->minHeight;
	}

	public function getGenerator() : int
	{
		return $this->generator;
	}

	public function getDimensionType() : int
	{
		return $this->dimensionType;
	}

	public static function read(NetworkBinaryStream $in, int $protocol) : self
	{
		$maxHeight = $in->getVarInt();
		$minHeight = $in->getVarInt();
		$generator = $in->getVarInt();
		$dimensionType = $protocol >= ProtocolInfo::PROTOCOL_975 ? $in->getVarInt() : 0;

		return new self($maxHeight, $minHeight, $generator, $dimensionType);
	}

	public function write(NetworkBinaryStream $out, int $protocol) : void
	{
		$out->putVarInt($this->maxHeight);
		$out->putVarInt($this->minHeight);
		$out->putVarInt($this->generator);
		if ($protocol >= ProtocolInfo::PROTOCOL_975) {
			$out->putVarInt($this->dimensionType);
		}
	}
}
