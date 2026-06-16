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

namespace pocketmine\network\mcpe\convert;

use pocketmine\network\mcpe\protocol\types\GameMode as ProtocolGameMode;
use pocketmine\Player;
use pocketmine\utils\SingletonTrait;

class TypeConverter
{
	use SingletonTrait;

	private const DAMAGE_TAG = "Damage"; //TAG_Int
	private const DAMAGE_TAG_CONFLICT_RESOLUTION = "___Damage_ProtocolCollisionResolution___";
	private const PM_ID_TAG = "___Id___";
	private const PM_META_TAG = "___Meta___";

	private const RECIPE_INPUT_WILDCARD_META = 0x7fff;

	public function __construct()
	{
		//NOOP
	}

	/**
	 * Returns a client-friendly gamemode of the specified real gamemode
	 * This function takes care of handling gamemodes known to MCPE (as of 1.1.0.3, that includes Survival, Creative and Adventure)
	 *
	 * @internal
	 */
	public function coreGameModeToProtocol(int $gamemode) : int
	{
		return match($gamemode) {
			Player::SURVIVAL => ProtocolGameMode::SURVIVAL,
			//TODO: native spectator support
			Player::CREATIVE, Player::SPECTATOR => ProtocolGameMode::CREATIVE,
			Player::ADVENTURE => ProtocolGameMode::ADVENTURE,
		};
	}

	public function protocolGameModeToCore(int $gameMode) : ?int
	{
		return match($gameMode) {
			ProtocolGameMode::SURVIVAL => Player::SURVIVAL,
			ProtocolGameMode::CREATIVE => Player::CREATIVE,
			ProtocolGameMode::ADVENTURE => Player::ADVENTURE,
			ProtocolGameMode::SURVIVAL_VIEWER, ProtocolGameMode::CREATIVE_VIEWER, ProtocolGameMode::SPECTATOR => Player::SPECTATOR,
			//TODO: native spectator support
			default => null,
		};
	}

	//TODO: release ItemStack convert

}
