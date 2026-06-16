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

use Generator;
use InvalidArgumentException;
use pocketmine\network\mcpe\compression\NetworkCompression;
use pocketmine\network\mcpe\convert\PacketIdTranslator;
use pocketmine\network\mcpe\NetworkBinaryStream;
use pocketmine\network\mcpe\NetworkSession;
use pocketmine\timings\Timings;
use pocketmine\utils\Binary;

use function assert;
use function get_class;
use function strlen;

class BatchPacket extends DataPacket
{
	public const NETWORK_ID = 0xfe;

	public string $payload = "";
	public bool $compressionEnabled = true;
	protected int $compressionLevel = 7;

	public function canBeBatched() : bool
	{
		return false;
	}

	public function canBeSentBeforeLogin() : bool
	{
		return true;
	}

	protected function decodeHeader() : void
	{
		$pid = $this->getByte();
		assert($pid === static::NETWORK_ID);
	}

	protected function decodePayload() : void
	{
		$data = $this->getRemaining();
		try {
			$this->payload = NetworkCompression::decompress($data);
		} catch (\ErrorException $e) { //zlib decode error
			$this->payload = "";
		}
	}

	protected function encodeHeader() : void
	{
		$this->putByte(static::NETWORK_ID);
	}

	protected function encodePayload() : void
	{
		$this->put(NetworkCompression::compress($this->payload, $this->getProtocol(), $this->compressionLevel));
	}

	public function addPacket(DataPacket $packet) : void
	{
		if (!$packet->canBeBatched()) {
			throw new InvalidArgumentException(get_class($packet) . " cannot be put inside a BatchPacket");
		}

		if (PacketIdTranslator::getInstance()->toNetworkId($this->getProtocol(), $packet->pid()) === null) {
			return;
		}

		if (!$packet->isEncoded) {
			$timings = Timings::getEncodeDataPacketTimings($packet);
			$timings->startTiming();
			try {
				$packet->encode();
			} finally {
				$timings->stopTiming();
			}
		}

		$this->payload .= Binary::writeUnsignedVarInt(strlen($packet->buffer)) . $packet->buffer;
	}

	/**
	 * @return Generator
	 */
	public function getPackets()
	{
		$stream = new NetworkBinaryStream($this->payload);
		while (!$stream->feof()) {
			yield $stream->getString();
		}
	}

	public function getCompressionLevel() : int
	{
		return $this->compressionLevel;
	}

	public function setCompressionLevel(int $level)
	{
		$this->compressionLevel = $level;
	}

	public function handle(NetworkSession $session) : bool
	{
		if ($this->payload === "") {
			return false;
		}

		$count = 0;
		foreach ($this->getPackets() as $buf) {
			if (++$count > 4096) {
				throw new \UnexpectedValueException("Too many packets in a single batch!");
			}

			if (isset($buf[0])) {
				$pk = PacketPool::getPacket($buf, $session->getProtocol());
				$pk->setProtocol($session->getProtocol());

				if (!$pk->canBeBatched()) {
					\GlobalLogger::get()->critical("Received invalid " . get_class($pk) . " inside BatchPacket");
					return false;
				}

				if ($pk->buffer === "\x21\x04\x00") {
					--$count;
				}

				$session->handleDataPacket($pk);
			}
		}

		return true;
	}
}
