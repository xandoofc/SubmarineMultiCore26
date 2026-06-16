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

namespace pocketmine;

use BadMethodCallException;
use pocketmine\block\Bed;
use pocketmine\block\Block;
use pocketmine\block\BlockFactory;
use pocketmine\block\ItemFrame;
use pocketmine\block\UnknownBlock;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\entity\Attribute;
use pocketmine\entity\Effect;
use pocketmine\entity\EffectInstance;
use pocketmine\entity\Entity;
use pocketmine\entity\Human;
use pocketmine\entity\Living;
use pocketmine\entity\object\ItemEntity;
use pocketmine\entity\passive\AbstractHorse;
use pocketmine\entity\projectile\Arrow;
use pocketmine\entity\projectile\FishingHook;
use pocketmine\entity\Skin;
use pocketmine\entity\vehicle\Boat;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\inventory\InventoryCloseEvent;
use pocketmine\event\player\PlayerAnimationEvent;
use pocketmine\event\player\PlayerBedEnterEvent;
use pocketmine\event\player\PlayerBedLeaveEvent;
use pocketmine\event\player\PlayerBlockPickEvent;
use pocketmine\event\player\PlayerChangeSkinEvent;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerCommandPreprocessEvent;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerDropItemEvent;
use pocketmine\event\player\PlayerEditBookEvent;
use pocketmine\event\player\PlayerEntityPickEvent;
use pocketmine\event\player\PlayerExhaustEvent;
use pocketmine\event\player\PlayerGameModeChangeEvent;
use pocketmine\event\player\PlayerInteractEntityEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerItemConsumeEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerJumpEvent;
use pocketmine\event\player\PlayerKickEvent;
use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\event\player\PlayerMissSwingEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerPreLoginEvent;
use pocketmine\event\player\PlayerPreMoveEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerResourcePackOfferEvent;
use pocketmine\event\player\PlayerRespawnEvent;
use pocketmine\event\player\PlayerToggleCrawlEvent;
use pocketmine\event\player\PlayerToggleFlightEvent;
use pocketmine\event\player\PlayerToggleGlideEvent;
use pocketmine\event\player\PlayerToggleSneakEvent;
use pocketmine\event\player\PlayerToggleSprintEvent;
use pocketmine\event\player\PlayerToggleSwimEvent;
use pocketmine\event\player\PlayerTransferEvent;
use pocketmine\event\server\DataPacketSendEvent;
use pocketmine\form\Form;
use pocketmine\form\FormValidationException;
use pocketmine\inventory\BaseInventory;
use pocketmine\inventory\CraftingGrid;
use pocketmine\inventory\CraftingManager;
use pocketmine\inventory\CreativeInventory;
use pocketmine\inventory\Inventory;
use pocketmine\inventory\InventoryHolder;
use pocketmine\inventory\PlayerCursorInventory;
use pocketmine\inventory\PlayerUIInventory;
use pocketmine\inventory\transaction\action\ContainerSlotChangeAction;
use pocketmine\inventory\transaction\action\InventoryAction;
use pocketmine\inventory\transaction\action\SlotChangeAction;
use pocketmine\inventory\transaction\CraftingTransaction;
use pocketmine\inventory\transaction\InventoryTransaction;
use pocketmine\inventory\transaction\TransactionValidationException;
use pocketmine\item\Consumable;
use pocketmine\item\Durable;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\enchantment\MeleeWeaponEnchantment;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\item\MaybeConsumable;
use pocketmine\item\Releasable;
use pocketmine\item\WritableBook;
use pocketmine\item\WrittenBook;
use pocketmine\lang\TextContainer;
use pocketmine\lang\TranslationContainer;
use pocketmine\level\ChunkListener;
use pocketmine\level\ChunkLoader;
use pocketmine\level\format\Chunk;
use pocketmine\level\GameRules;
use pocketmine\level\Level;
use pocketmine\level\Location;
use pocketmine\level\particle\PunchBlockParticle;
use pocketmine\level\Position;
use pocketmine\maps\MapData;
use pocketmine\maps\MapManager;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Vector3;
use pocketmine\nbt\NetworkLittleEndianNBTStream;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\DoubleTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\network\mcpe\auth\ProcessLoginTask;
use pocketmine\network\mcpe\cache\MapCache;
use pocketmine\network\mcpe\cache\StaticPacketCache;
use pocketmine\network\mcpe\compression\CompressBatchPromise;
use pocketmine\network\mcpe\compression\DecompressionException;
use pocketmine\network\mcpe\compression\NetworkCompression;
use pocketmine\network\mcpe\convert\GlobalItemTypeDictionary;
use pocketmine\network\mcpe\convert\LegacyItemIdToStringIdMap;
use pocketmine\network\mcpe\convert\PacketIdTranslator;
use pocketmine\network\mcpe\convert\ProtocolConvertor;
use pocketmine\network\mcpe\convert\RuntimeBlockMapping;
use pocketmine\network\mcpe\convert\TypeConverter;
use pocketmine\network\mcpe\encryption\DecryptionException;
use pocketmine\network\mcpe\encryption\EncryptionContext;
use pocketmine\network\mcpe\encryption\PrepareEncryptionTask;
use pocketmine\network\mcpe\PacketRateLimiter;
use pocketmine\network\mcpe\PacketSender;
use pocketmine\network\mcpe\PlayerNetworkSessionAdapter;
use pocketmine\network\mcpe\protocol\ActorEventPacket;
use pocketmine\network\mcpe\protocol\AdventureSettingsPacket;
use pocketmine\network\mcpe\protocol\AnimatePacket;
use pocketmine\network\mcpe\protocol\AvailableActorIdentifiersPacket;
use pocketmine\network\mcpe\protocol\AvailableCommandsPacket;
use pocketmine\network\mcpe\protocol\BatchPacket;
use pocketmine\network\mcpe\protocol\BiomeDefinitionListPacket;
use pocketmine\network\mcpe\protocol\BlockActorDataPacket;
use pocketmine\network\mcpe\protocol\BookEditPacket;
use pocketmine\network\mcpe\protocol\ChangeDimensionPacket;
use pocketmine\network\mcpe\protocol\ChunkRadiusUpdatedPacket;
use pocketmine\network\mcpe\protocol\ClientboundCloseFormPacket;
use pocketmine\network\mcpe\protocol\CommandStepPacket;
use pocketmine\network\mcpe\protocol\CompletedUsingItemPacket;
use pocketmine\network\mcpe\protocol\ContainerClosePacket;
use pocketmine\network\mcpe\protocol\ContainerOpenPacket;
use pocketmine\network\mcpe\protocol\ContainerSetSlotPacket;
use pocketmine\network\mcpe\protocol\CraftingEventPacket;
use pocketmine\network\mcpe\protocol\CreativeContentPacket;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\DeathInfoPacket;
use pocketmine\network\mcpe\protocol\DisconnectPacket;
use pocketmine\network\mcpe\protocol\DropItemPacket;
use pocketmine\network\mcpe\protocol\EmotePacket;
use pocketmine\network\mcpe\protocol\InteractPacket;
use pocketmine\network\mcpe\protocol\InventoryContentPacket;
use pocketmine\network\mcpe\protocol\InventorySlotPacket;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\network\mcpe\protocol\ItemFrameDropItemPacket;
use pocketmine\network\mcpe\protocol\ItemRegistryPacket;
use pocketmine\network\mcpe\protocol\ItemStackRequestPacket;
use pocketmine\network\mcpe\protocol\ItemStackResponsePacket;
use pocketmine\network\mcpe\protocol\LevelEventPacket;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\network\mcpe\protocol\LoginPacket;
use pocketmine\network\mcpe\protocol\MapInfoRequestPacket;
use pocketmine\network\mcpe\protocol\MobEffectPacket;
use pocketmine\network\mcpe\protocol\MobEquipmentPacket;
use pocketmine\network\mcpe\protocol\ModalFormRequestPacket;
use pocketmine\network\mcpe\protocol\MovePlayerPacket;
use pocketmine\network\mcpe\protocol\NetworkChunkPublisherUpdatePacket;
use pocketmine\network\mcpe\protocol\NetworkSettingsPacket;
use pocketmine\network\mcpe\protocol\PacketDecodeException;
use pocketmine\network\mcpe\protocol\PacketPool;
use pocketmine\network\mcpe\protocol\PlayerActionPacket;
use pocketmine\network\mcpe\protocol\PlayerListPacket;
use pocketmine\network\mcpe\protocol\PlayStatusPacket;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\network\mcpe\protocol\RequestNetworkSettingsPacket;
use pocketmine\network\mcpe\protocol\ResourcePackChunkDataPacket;
use pocketmine\network\mcpe\protocol\ResourcePackChunkRequestPacket;
use pocketmine\network\mcpe\protocol\ResourcePackClientResponsePacket;
use pocketmine\network\mcpe\protocol\ResourcePackDataInfoPacket;
use pocketmine\network\mcpe\protocol\ResourcePacksInfoPacket;
use pocketmine\network\mcpe\protocol\ResourcePackStackPacket;
use pocketmine\network\mcpe\protocol\RespawnPacket;
use pocketmine\network\mcpe\protocol\serializer\PacketBatch;
use pocketmine\network\mcpe\protocol\ServerToClientHandshakePacket;
use pocketmine\network\mcpe\protocol\SetActorDataPacket;
use pocketmine\network\mcpe\protocol\SetPlayerGameTypePacket;
use pocketmine\network\mcpe\protocol\SetSpawnPositionPacket;
use pocketmine\network\mcpe\protocol\SetTitlePacket;
use pocketmine\network\mcpe\protocol\StartGamePacket;
use pocketmine\network\mcpe\protocol\TextPacket;
use pocketmine\network\mcpe\protocol\ToastRequestPacket;
use pocketmine\network\mcpe\protocol\TransferPacket;
use pocketmine\network\mcpe\protocol\types\AbilitiesData;
use pocketmine\network\mcpe\protocol\types\AbilitiesLayer;
use pocketmine\network\mcpe\protocol\types\command\CommandData;
use pocketmine\network\mcpe\protocol\types\command\CommandEnum;
use pocketmine\network\mcpe\protocol\types\command\CommandPermissions;
use pocketmine\network\mcpe\protocol\types\CompressionAlgorithm;
use pocketmine\network\mcpe\protocol\types\DimensionIds;
use pocketmine\network\mcpe\protocol\types\Experiments;
use pocketmine\network\mcpe\protocol\types\GameMode;
use pocketmine\network\mcpe\protocol\types\inventory\ContainerIds;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStackWrapper;
use pocketmine\network\mcpe\protocol\types\inventory\MismatchTransactionData;
use pocketmine\network\mcpe\protocol\types\inventory\NetworkInventoryAction;
use pocketmine\network\mcpe\protocol\types\inventory\NormalTransactionData;
use pocketmine\network\mcpe\protocol\types\inventory\ReleaseItemTransactionData;
use pocketmine\network\mcpe\protocol\types\inventory\UIInventorySlotOffset;
use pocketmine\network\mcpe\protocol\types\inventory\UseItemOnEntityTransactionData;
use pocketmine\network\mcpe\protocol\types\inventory\UseItemTransactionData;
use pocketmine\network\mcpe\protocol\types\inventory\WindowTypes;
use pocketmine\network\mcpe\protocol\types\NetworkPermissions;
use pocketmine\network\mcpe\protocol\types\PlayerMovementSettings;
use pocketmine\network\mcpe\protocol\types\PlayerPermissions;
use pocketmine\network\mcpe\protocol\types\resourcepacks\ResourcePackInfoEntry;
use pocketmine\network\mcpe\protocol\types\resourcepacks\ResourcePackStackEntry;
use pocketmine\network\mcpe\protocol\types\resourcepacks\ResourcePackType;
use pocketmine\network\mcpe\protocol\types\ServerAuthMovementMode;
use pocketmine\network\mcpe\protocol\types\skin\PersonaPieceTintColor;
use pocketmine\network\mcpe\protocol\types\skin\PersonaSkinPiece;
use pocketmine\network\mcpe\protocol\types\skin\SerializedSkin;
use pocketmine\network\mcpe\protocol\types\skin\SkinAnimation;
use pocketmine\network\mcpe\protocol\types\skin\SkinImage;
use pocketmine\network\mcpe\protocol\types\SpawnSettings;
use pocketmine\network\mcpe\protocol\UnknownPacket;
use pocketmine\network\mcpe\protocol\UpdateAbilitiesPacket;
use pocketmine\network\mcpe\protocol\UpdateAdventureSettingsPacket;
use pocketmine\network\mcpe\protocol\UpdateAttributesPacket;
use pocketmine\network\mcpe\protocol\UpdateBlockPacket;
use pocketmine\network\PacketHandlingException;
use pocketmine\permission\PermissibleBase;
use pocketmine\permission\Permission;
use pocketmine\permission\PermissionAttachment;
use pocketmine\permission\PermissionAttachmentInfo;
use pocketmine\permission\PermissionManager;
use pocketmine\plugin\Plugin;
use pocketmine\resourcepacks\ResourcePack;
use pocketmine\tile\Spawnable;
use pocketmine\tile\Tile;
use pocketmine\timings\Timings;
use pocketmine\utils\BinaryDataException;
use pocketmine\utils\BinaryStream;
use pocketmine\utils\Color;
use pocketmine\utils\TextFormat;
use pocketmine\utils\UUID;
use SplFixedArray;
use SplQueue;
use UnexpectedValueException;
use function abs;
use function array_map;
use function array_values;
use function assert;
use function base64_decode;
use function base64_encode;
use function ceil;
use function chr;
use function count;
use function explode;
use function floor;
use function fmod;
use function get_class;
use function implode;
use function in_array;
use function intval;
use function is_countable;
use function is_infinite;
use function is_int;
use function is_nan;
use function is_string;
use function json_encode;
use function json_last_error_msg;
use function max;
use function mb_strlen;
use function microtime;
use function min;
use function ord;
use function preg_match;
use function round;
use function spl_object_hash;
use function sprintf;
use function sqrt;
use function str_repeat;
use function str_replace;
use function strlen;
use function strpos;
use function strtolower;
use function substr;
use function time;
use function trim;
use function ucfirst;
use const M_PI;
use const M_SQRT3;
use const PHP_INT_MAX;

/**
 * Main class that handles networking, recovery, and packet sending to the server part
 */
class Player extends Human implements CommandSender, ChunkLoader, ChunkListener, IPlayer
{
	public const SURVIVAL = 0;
	public const CREATIVE = 1;
	public const ADVENTURE = 2;
	public const SPECTATOR = 3;
	public const VIEW = Player::SPECTATOR;

	private const MOVES_PER_TICK = 2;
	private const MOVE_BACKLOG_SIZE = 100 * self::MOVES_PER_TICK; //100 ticks backlog (5 seconds)

	private const RESOURCE_PACK_CHUNK_SIZE = 128 * 1024; //128KB

	public const BEACON_WINDOW_ID = 4;

	private const MAX_INPUT_JSON = 15;

	/** Max length of a chat message (UTF-8 codepoints, not bytes) */
	private const MAX_CHAT_CHAR_LENGTH = 512;
	/**
	 * Max length of a chat message in bytes. This is a theoretical maximum (if every character was 4 bytes).
	 * Since mb_strlen() is O(n), it gets very slow with large messages. Checking byte length with strlen() is O(1) and
	 * is a useful heuristic to filter out oversized messages.
	 */
	private const MAX_CHAT_BYTE_LENGTH = self::MAX_CHAT_CHAR_LENGTH * 4;

	private const INCOMING_PACKET_BATCH_PER_TICK = 2; //usually max 1 per tick, but transactions arrive separately
	private const INCOMING_PACKET_BATCH_BUFFER_TICKS = 100; //enough to account for a 5-second lag spike

	/**
	 * Validates the given username.
	 */
	public static function isValidUserName(?string $name) : bool
	{
		if ($name === null) {
			return false;
		}

		$lname = strtolower($name);
		$len = strlen($name);
		return $lname !== "rcon" && $lname !== "console" && $len >= 1 && $len <= 16 && preg_match("/[^A-Za-z0-9_ ]/", $name) === 0 && trim($lname) !== "" && trim($lname) === $lname;
	}

	protected PacketSender $sender;

	protected ?PlayerNetworkSessionAdapter $sessionAdapter = null;

	/** @var bool */
	protected $enableCompression = true;

	/** @var string[]  */
	protected $sendBuffer = [];

	protected SplQueue $batchQueue;

	/** @var ?EncryptionContext */
	protected $cipher = null;

	protected bool $connected = true;
	/** @var string */
	protected $ip;
	/** @var int */
	protected $port;
	/** @var int */
	protected $sessionId;
	/** @var int */
	protected $raknetProtocol;

	/**
	 * @var int
	 * Last measurement of player's latency in milliseconds.
	 */
	protected $lastPingMeasure = 1;

	/** @var int */
	protected $lastEatingSound = 0;

	/** @var float */
	public $connectTime = 0;

	/** @var bool */
	public $loggedIn = false;
	/** @var bool */
	public $loginProcessed = false;
	/** @var bool */
	public $joined = false;

	/** @var bool */
	private $seenLoginPacket = false;
	/** @var bool */
	public $awaitingEncryptionHandshake = false;
	/** @var bool */
	private $resourcePacksDone = false;
	/** @var bool */
	private $haveAllPacks = false;

	/** @var bool */
	public $spawned = false;

	/** @var bool */
	protected $xboxAuthenticated = false;

	/** @var string */
	protected $username = "";
	/** @var string */
	protected $iusername = "";
	/** @var string */
	protected $displayName = "";
	/** @var int */
	protected $protocolVersion = ProtocolInfo::CURRENT_PROTOCOL;
	/** @var int */
	protected $chunkProtocolVersion = ProtocolInfo::CURRENT_PROTOCOL;
	/** @var int */
	protected $craftingProtocolVersion = ProtocolInfo::CURRENT_PROTOCOL;
	/** @var int */
	protected $mapProtocolVersion = ProtocolInfo::CURRENT_PROTOCOL;
	/** @var string */
	protected $vanillaVersion = "";
	/** @var int */
	protected $randomClientId;
	/** @var string */
	protected $serverAddress;
	/** @var string */
	protected $xuid = "";
	private int $protocolTraceIn = 0;
	private int $protocolTraceOut = 0;
	protected int $windowCnt = ContainerIds::FIRST;
	/** @var int[] */
	protected array $windows = [];
	/** @var Inventory[] */
	protected array $windowIndex = [];
	/** @var bool[] */
	protected array  $permanentWindows = [];

	protected CreativeInventory $creativeInventory;
	protected PlayerCursorInventory $cursorInventory;
	protected PlayerUIInventory $uiInventory;
	protected CraftingGrid $craftingGrid;
	protected ?CraftingTransaction $craftingTransaction = null;

	/** @var bool[][] uuid => [chunk index => hasSent] */
	protected $downloadedChunks = [];

	/** @var bool */
	protected $forceAsyncCompression = true;

	/** @var Vector3 */
	protected $speed = null;

	/** @var int */
	protected $messageCounter = 2;
	/** @var bool */
	protected $removeFormat = true;

	protected int $removeBlockCounter = 0;
	protected int $removeBlockLast = 0;

	protected int $useItemCounter = 0;
	protected int $useItemLast = 0;

	/** @var bool */
	protected $playedBefore;
	/** @var int */
	protected $gamemode;
	/** @var bool[] chunkHash => bool (true = sent, false = needs sending) */
	public array $usedChunks = [];
	/**
	 * @var true[] chunkHash => dummy
	 * @phpstan-var array<int, true>
	 */
	protected array $loadQueue = [];
	protected int $nextChunkOrderRun = 5;
	protected bool $doOrderChunks = true;
	protected int $chunkHack = 0;

	protected int $viewDistance = -1;
	protected int $spawnThreshold;
	protected int $spawnChunkLoadCount = 0;
	protected int $chunksPerTick;

	/** @var bool[] map: raw UUID (string) => bool */
	protected array $hiddenPlayers = [];

	protected float $moveRateLimit = 10 * self::MOVES_PER_TICK;
	protected ?float $lastMovementProcess = null;
	protected bool $forceMoveSync = false;

	/** @var int */
	protected $inAirTicks = 0;
	protected float $stepHeight = 0.6;
	/** @var bool */
	protected $allowMovementCheats = false;

	/** @var Vector3|null */
	protected $sleeping = null;
	/** @var Position|null */
	private $spawnPosition = null;
	/** @var Position|null */
	private $deathPosition = null;

	private bool $respawnLocked = false;

	//TODO: Abilities
	/** @var bool */
	protected $autoJump = true;
	/** @var bool */
	protected $allowFlight = false;
	/** @var bool */
	protected $flying = false;
	protected float $flightSpeed = 0.05;

	/** @var bool */
	protected $muted = false;

	private ?PermissibleBase $perm;

	protected ?int $lineHeight = null;
	protected string $locale = "en_US";
	protected string $deviceModel;
	protected int $deviceOS;
	protected int $currentInputMode;
	protected int $defaultInputMode;
	protected string $deviceId;

	protected int $startAction = -1;
	/** @var int[] ID => ticks map */
	protected array $usedItemsCooldown = [];

	protected int $formIdCounter = 0;
	protected array $forms = [];

	protected float $lastRightClickTime = 0.0;
	protected ?UseItemTransactionData $lastRightClickData = null;

	protected ?FishingHook $fishingHook = null;

	protected int $commandPermission = CommandPermissions::NORMAL;
	protected bool $keepExperience = false;

	protected ?int $closingWindowId = null;
	protected int $currentWindowType = WindowTypes::CONTAINER;

	protected PacketRateLimiter $packetBatchLimiter;

	/** @var InventoryAction[] */
	protected array $faultyOldTransactionActions = [];

	/**
	 * @return TranslationContainer|string
	 */
	public function getLeaveMessage()
	{
		if ($this->joined) {
			return new TranslationContainer(TextFormat::YELLOW . "%multiplayer.player.left", [
				$this->getDisplayName()
			]);
		}

		return "";
	}

	/**
	 * This might disappear in the future. Please use getUniqueId() instead.
	 * @return int
	 * @deprecated
	 */
	public function getClientId()
	{
		return $this->randomClientId;
	}

	public function isBanned() : bool
	{
		return $this->server->getNameBans()->isBanned($this->username);
	}

	public function setBanned(bool $value)
	{
		if ($value) {
			$this->server->getNameBans()->addBan($this->getName(), null, null, null);
			$this->kick("You have been banned");
		} else {
			$this->server->getNameBans()->remove($this->getName());
		}
	}

	public function isWhitelisted() : bool
	{
		return $this->server->isWhitelisted($this->username);
	}

	public function setWhitelisted(bool $value)
	{
		if ($value) {
			$this->server->addWhitelist($this->username);
		} else {
			$this->server->removeWhitelist($this->username);
		}
	}

	public function isAuthenticated() : bool
	{
		//return $this->xuid !== "";
		return $this->xboxAuthenticated;
	}

	/**
	 * If the player is logged into Xbox Live, returns their Xbox user ID (XUID) as a string. Returns an empty string if
	 * the player is not logged into Xbox Live.
	 */
	public function getXuid() : string
	{
		return $this->xuid;
	}

	/**
	 * Returns the player's UUID. This should be the preferred method to identify a player.
	 * It does not change if the player changes their username.
	 *
	 * All players will have a UUID, regardless of whether they are logged into Xbox Live or not. However, note that
	 * non-XBL players can fake their UUIDs.
	 *
	 * WARNING: DO NOT trust this before PlayerLoginEvent. Before PlayerLoginEvent, the player hasn't yet been
	 * authenticated, and any of their data might be faked.
	 */
	public function getUniqueId() : ?UUID
	{
		return parent::getUniqueId();
	}

	public function getPlayer()
	{
		return $this;
	}

	public function getFirstPlayed() : int
	{
		return $this->namedtag->getLong("firstPlayed", 0, true);
	}

	public function getLastPlayed() : int
	{
		return $this->namedtag->getLong("lastPlayed", 0, true);
	}

	public function hasPlayedBefore() : bool
	{
		return $this->playedBefore;
	}

	/**
	 * @return void
	 */
	public function setAllowFlight(bool $value)
	{
		$this->allowFlight = $value;
		$this->sendAbilities();
	}

	public function getAllowFlight() : bool
	{
		return $this->allowFlight;
	}

	/**
	 * @return void
	 */
	public function setFlying(bool $value)
	{
		if ($this->flying !== $value) {
			$this->flying = $value;
			$this->resetFallDistance();
			$this->sendAbilities();
		}
	}

	public function isFlying() : bool
	{
		return $this->flying;
	}

	public function setFlightSpeed(float $flightSpeed) : void
	{
		if ($this->flightSpeed !== $flightSpeed && $flightSpeed >= 0) {
			$this->flightSpeed = $flightSpeed;
			$this->sendAbilities();
		}
	}

	public function getFlightSpeed() : float
	{
		return $this->flightSpeed;
	}

	public function toggleFlight(bool $fly) : bool
	{
		if ($fly === $this->flying) {
			return true;
		}

		$ev = new PlayerToggleFlightEvent($this, $fly);
		$ev->call();
		if ($ev->isCancelled()) {
			$this->sendData($this);
			return false;
		}
		$this->setFlying($fly);
		return true;
	}

	public function setMuted(bool $value) : void
	{
		$this->muted = $value;
		$this->sendAbilities();
	}

	public function isMuted() : bool
	{
		return $this->muted;
	}

	public function setAutoJump(bool $value) : void
	{
		$this->autoJump = $value;
		$this->sendAbilities();
	}

	public function hasAutoJump() : bool
	{
		return $this->autoJump;
	}

	public function getFishingHook() : ?FishingHook
	{
		return $this->fishingHook;
	}

	public function setFishingHook(?FishingHook $fishingHook) : void
	{
		$this->fishingHook = $fishingHook;
	}

	public function allowMovementCheats() : bool
	{
		return $this->allowMovementCheats;
	}

	/**
	 * @return void
	 */
	public function setAllowMovementCheats(bool $value = true)
	{
		$this->allowMovementCheats = $value;
	}

	public function spawnTo(Player $player) : void
	{
		if ($this->isAlive() && $player->isAlive() && $player->canSee($this) && !$this->isSpectator()) {
			parent::spawnTo($player);
		}
	}

	/**
	 * @return Server
	 */
	public function getServer()
	{
		return $this->server;
	}

	public function getRemoveFormat() : bool
	{
		return $this->removeFormat;
	}

	/**
	 * @return void
	 */
	public function setRemoveFormat(bool $remove = true)
	{
		$this->removeFormat = $remove;
	}

	public function getScreenLineHeight() : int
	{
		return $this->lineHeight ?? 7;
	}

	public function setScreenLineHeight(int $height = null)
	{
		if ($height !== null && $height < 1) {
			$this->server->getLogger()->critical("Line height must be at least 1 from " . $this->getName());
			return;
		}
		$this->lineHeight = $height;
	}

	public function canSee(Player $player) : bool
	{
		return !isset($this->hiddenPlayers[$player->getRawUniqueId()]);
	}

	/**
	 * @return void
	 */
	public function hidePlayer(Player $player)
	{
		if ($player === $this) {
			return;
		}
		$this->hiddenPlayers[$player->getRawUniqueId()] = true;
		$player->despawnFrom($this);
	}

	/**
	 * @return void
	 */
	public function showPlayer(Player $player)
	{
		if ($player === $this) {
			return;
		}
		unset($this->hiddenPlayers[$player->getRawUniqueId()]);
		if ($player->isOnline()) {
			$player->spawnTo($this);
		}
	}

	public function getNextChunkOrderRun() : int
	{
		return $this->nextChunkOrderRun;
	}

	public function setNextChunkOrderRun(int $nextChunkOrderRun) : void
	{
		$this->nextChunkOrderRun = $nextChunkOrderRun;
	}

	public function isOrderChunks() : bool
	{
		return $this->doOrderChunks;
	}

	public function setOrderChunks(bool $value) : void
	{
		$this->doOrderChunks = $value;
	}

	public function canCollideWith(Entity $entity) : bool
	{
		return false;
	}

	public function canBeCollidedWith() : bool
	{
		return !$this->isSpectator() && parent::canBeCollidedWith();
	}

	public function resetFallDistance() : void
	{
		parent::resetFallDistance();
		$this->inAirTicks = 0;
	}

	public function getViewDistance() : int
	{
		return $this->viewDistance;
	}

	/**
	 * @return void
	 */
	public function setViewDistance(int $distance)
	{
		if(!$this->constructed){
			return;
		}

		$this->viewDistance = $this->server->getAllowedViewDistance($distance);

		$this->spawnThreshold = (int) (min($this->viewDistance, $this->server->getProperty("chunk-sending.spawn-radius", 4)) ** 2 * M_PI);

		$this->nextChunkOrderRun = 0;

		$pk = new ChunkRadiusUpdatedPacket();
		$pk->radius = $this->viewDistance;
		$this->dataPacket($pk);

		$this->server->getLogger()->debug("Setting view distance for " . $this->getName() . " to " . $this->viewDistance . " (requested " . $distance . ")");
	}

	public function isOnline() : bool
	{
		return $this->connected && $this->loggedIn;
	}

	public function isOp() : bool
	{
		return $this->server->isOp($this->getName());
	}

	/**
	 * @return void
	 */
	public function setOp(bool $value)
	{
		if ($value === $this->isOp()) {
			return;
		}

		if ($value) {
			$this->server->addOp($this->getName());
		} else {
			$this->server->removeOp($this->getName());
		}

		$this->sendAbilities();
	}

	/**
	 * @param Permission|string $name
	 */
	public function isPermissionSet($name) : bool
	{
		return $this->perm->isPermissionSet($name);
	}

	public function hasPermission(Permission|string $name) : bool
	{
		if ($this->closed) {
			$this->server->getLogger()->critical("Trying to get permissions of closed player from " . $this->getName());
			return false;
		}
		return $this->perm->hasPermission($name);
	}

	public function addAttachment(Plugin $plugin, string $name = null, bool $value = null) : PermissionAttachment
	{
		return $this->perm->addAttachment($plugin, $name, $value);
	}

	/**
	 * @return void
	 */
	public function removeAttachment(PermissionAttachment $attachment)
	{
		$this->perm->removeAttachment($attachment);
	}

	public function recalculatePermissions()
	{
		$permManager = PermissionManager::getInstance();
		$permManager->unsubscribeFromPermission(Server::BROADCAST_CHANNEL_USERS, $this);
		$permManager->unsubscribeFromPermission(Server::BROADCAST_CHANNEL_ADMINISTRATIVE, $this);

		if ($this->perm === null) {
			return;
		}

		$this->perm->recalculatePermissions();

		if ($this->spawned) {
			if ($this->hasPermission(Server::BROADCAST_CHANNEL_USERS)) {
				$permManager->subscribeToPermission(Server::BROADCAST_CHANNEL_USERS, $this);
			}
			if ($this->hasPermission(Server::BROADCAST_CHANNEL_ADMINISTRATIVE)) {
				$permManager->subscribeToPermission(Server::BROADCAST_CHANNEL_ADMINISTRATIVE, $this);
			}

			$this->sendCommandData();
		}
	}

	/**
	 * @return PermissionAttachmentInfo[]
	 */
	public function getEffectivePermissions() : array
	{
		return $this->perm->getEffectivePermissions();
	}

	/**
	 * @return void
	 */
	public function sendCommandData()
	{
		if ($this->server->commandFix) {
			return;
		}
		$pk = new AvailableCommandsPacket();
		foreach ($this->server->getCommandMap()->getCommands() as $command) {
			if (!$command->testPermissionSilent($this) || isset($pk->commandData[$command->getName()]) || $command->getName() === "help" || !$command->testPermissionSilent($this)) {
				continue;
			}

			if ($this->getProtocolVersion() >= ProtocolInfo::PROTOCOL_137) {
				$data = $command->getCommandData();

				$lname = strtolower($command->getLabel());
				$aliases = $command->getAliases();
				$aliasObj = null;
				if (count($aliases) > 0) {
					if (!in_array($lname, $aliases, true)) {
						//work around a client bug which makes the original name not show when aliases are used
						$aliases[] = $lname;
					}
					$aliasObj = new CommandEnum(ucfirst($command->getLabel()) . "Aliases", array_values($aliases));
				}

				$description = $data->getDescription();

				$pk->commandData[$command->getLabel()] = new CommandData(
					$lname, //TODO: commands containing uppercase letters in the name crash 1.9.0 client
					$this->server->getLanguage()->translateString($description),
					$data->getFlags(),
					$data->getPermission(),
					$aliasObj,
					$data->getOverloads(),
					$data->getChainedSubCommandData()
				);
			} else {
				$pk->jsonCommandData[$command->getLabel()] = $command->getJsonCommandData();
			}
		}

		$this->sendDataPacket($pk);
	}

	public function __construct(PacketSender $sender, string $ip, int $port, int $sessionId, int $raknetProtocol)
	{
		$this->sender = $sender;
		$this->perm = new PermissibleBase($this);
		$this->namedtag = new CompoundTag();
		$this->server = Server::getInstance();
		$this->ip = $ip;
		$this->port = $port;
		$this->sessionId = $sessionId;
		$this->raknetProtocol = $raknetProtocol;
		if ($this->raknetProtocol >= 11) { // MCBE_RAKNET_PROTOCOL_VERSION_WITHOUT_ZLIB_COMPRESSION
			$this->enableCompression = false;
			$this->protocolVersion = ProtocolInfo::PROTOCOL_567;
		}
		$this->chunksPerTick = (int) $this->server->getProperty("chunk-sending.per-tick", 4);
		$this->spawnThreshold = (int) (($this->server->getProperty("chunk-sending.spawn-radius", 4) ** 2) * M_PI);
		$this->gamemode = $this->server->getGamemode();
		$this->setLevel($this->server->getDefaultLevel());
		$this->boundingBox = new AxisAlignedBB(0, 0, 0, 0, 0, 0);

		$this->connectTime = time();

		$this->packetBatchLimiter = new PacketRateLimiter("Packet Batches", self::INCOMING_PACKET_BATCH_PER_TICK, self::INCOMING_PACKET_BATCH_BUFFER_TICKS);

		$this->allowMovementCheats = (bool) $this->server->getProperty("player.anti-cheat.allow-movement-cheats", false);

		$this->batchQueue = new SplQueue();

		$this->sessionAdapter = new PlayerNetworkSessionAdapter($this->server, $this);
	}

	public function hasNetworkCompression() : bool
	{
		return $this->enableCompression;
	}

	public function isConnected() : bool
	{
		return $this->connected;
	}

	/**
	 * Gets the username
	 */
	public function getName() : string
	{
		return $this->username;
	}

	public function getLowerCaseName() : string
	{
		return $this->iusername;
	}

	/**
	 * Returns the "friendly" display name of this player to use in the chat.
	 */
	public function getDisplayName() : string
	{
		return $this->displayName;
	}

	/**
	 * @return void
	 */
	public function setDisplayName(string $name)
	{
		$this->displayName = $name;
		if ($this->constructed) {
			$this->server->updatePlayerListData($this->getUniqueId(), $this->getId(), $this->getDisplayName(), $this->getSkin(), $this->getXuid());
		}
	}

	/**
	 * Returns the player's locale, e.g. en_US.
	 */
	public function getLocale() : string
	{
		return $this->locale;
	}

	/**
	 * Sets player locale, e.g. en_US
	 */
	public function setLocale(string $locale) : void
	{
		$this->locale = $locale;
	}

	/**
	 * Called when a player changes their skin.
	 * Plugin developers should not use this, use setSkin() and sendSkin() instead.
	 */
	public function changeSkin(Skin $skin, string $newSkinName, string $oldSkinName) : bool
	{
		$ev = new PlayerChangeSkinEvent($this, $this->getSkin(), $skin);
		$ev->call();

		if ($ev->isCancelled()) {
			$this->sendSkin([$this]);
			return true;
		}

		$this->setSkin($ev->getNewSkin());
		$this->sendSkin($this->server->getOnlinePlayers());
		return true;
	}

	/**
	 * {@inheritdoc}
	 *
	 * If null is given, will additionally send the skin to the player itself as well as its viewers.
	 */
	public function sendSkin(?array $targets = null) : void
	{
		parent::sendSkin($targets ?? $this->server->getOnlinePlayers());
	}

	/**
	 * Gets the player IP address
	 */
	public function getAddress() : string
	{
		return $this->ip;
	}

	public function getPort() : int
	{
		return $this->port;
	}

	public function getRaknetProtocol() : int
	{
		return $this->raknetProtocol;
	}

	/**
	 * Returns the last measured latency for this player, in milliseconds. This is measured automatically and reported
	 * back by the network interface.
	 */
	public function getPing() : int
	{
		return $this->lastPingMeasure;
	}

	/**
	 * Updates the player's last ping measurement.
	 *
	 * @return void
	 * @internal Plugins should not use this method.
	 */
	public function updatePing(int $pingMS)
	{
		$this->lastPingMeasure = $pingMS;
	}

	/**
	 * @deprecated
	 */
	public function getNextPosition() : Position
	{
		return $this->getPosition();
	}

	public function getInAirTicks() : int
	{
		return $this->inAirTicks;
	}

	/**
	 * Returns whether the player is currently using an item (right-click and hold).
	 */
	public function isUsingItem() : bool
	{
		return $this->getGenericFlag(self::DATA_FLAG_ACTION) && $this->startAction > -1;
	}

	/**
	 * @return void
	 */
	public function setUsingItem(bool $value)
	{
		$this->startAction = $value ? $this->server->getTick() : -1;
		$this->setGenericFlag(self::DATA_FLAG_ACTION, $value);
	}

	/**
	 * Returns how long the player has been using their currently-held item for. Used for determining arrow shoot force
	 * for bows.
	 */
	public function getItemUseDuration() : int
	{
		return $this->startAction === -1 ? -1 : ($this->server->getTick() - $this->startAction);
	}

	/**
	 * Returns whether the player has a cooldown period left before it can use the given item again.
	 */
	public function hasItemCooldown(Item $item) : bool
	{
		$this->checkItemCooldowns();
		return isset($this->usedItemsCooldown[$item->getId()]);
	}

	/**
	 * Resets the player's cooldown time for the given item back to the maximum.
	 */
	public function resetItemCooldown(Item $item) : void
	{
		$ticks = $item->getCooldownTicks();
		if ($ticks > 0) {
			$this->usedItemsCooldown[$item->getId()] = $this->server->getTick() + $ticks;
		}
	}

	protected function checkItemCooldowns() : void
	{
		$serverTick = $this->server->getTick();
		foreach ($this->usedItemsCooldown as $itemId => $cooldownUntil) {
			if ($cooldownUntil <= $serverTick) {
				unset($this->usedItemsCooldown[$itemId]);
			}
		}
	}

	protected function switchLevel(Level $targetLevel) : bool
	{
		$oldLevel = $this->level;
		if (parent::switchLevel($targetLevel)) {
			if ($oldLevel !== null) {
				foreach ($this->usedChunks as $index => $d) {
					Level::getXZ($index, $X, $Z);
					$this->unloadChunk($X, $Z, $oldLevel);
				}
			}

			$this->usedChunks = [];
			$this->loadQueue = [];
			$this->level->sendTime($this);
			$this->level->sendDifficulty($this);

			if ($targetLevel->getDimension() !== $oldLevel->getDimension()) {
				$pk = new ChangeDimensionPacket();
				$pk->dimension = $targetLevel->getDimension();
				$pk->position = $this->asVector3();
				$this->sendDataPacket($pk);

				// fast world loading
				$pk1 = new PlayerActionPacket();
				$pk1->entityRuntimeId = $this->getId();
				$pk1->action = PlayerActionPacket::ACTION_DIMENSION_CHANGE_ACK;
				[$pk1->x, $pk1->y, $pk1->z] = [(int) $this->x, (int) $this->y, (int) $this->z];
				[$pk1->rx, $pk1->ry, $pk1->rz] = [(int) $this->x, (int) $this->y, (int) $this->z];
				$pk1->face = 0;
				$this->dataPacket($pk1);
			}

			return true;
		}

		return false;
	}

	public function getCommandPermission() : int
	{
		return $this->commandPermission;
	}

	public function setCommandPermission(int $commandPermission) : void
	{
		$this->commandPermission = $commandPermission;
	}

	public function getMaxInPortalTime() : int
	{
		return $this->isCreative() ? 0 : 80;
	}

	public function getPortalCooldown() : int
	{
		return 10;
	}

	protected function unloadChunk(int $x, int $z, ?Level $level = null) : void
	{
		$level = $level ?? $this->level;
		$index = Level::chunkHash($x, $z);
		if (isset($this->usedChunks[$index])) {
			foreach ($level->getChunkEntities($x, $z) as $entity) {
				if ($entity !== $this) {
					$entity->despawnFrom($this);
				}
			}

			unset($this->usedChunks[$index]);
		}
		$level->unregisterChunkLoader($this, $x, $z);
		unset($this->loadQueue[$index]);
	}

	public function sendChunk(int $x, int $z, string $buffer) : void
	{
		if (!$this->isConnected()) {
			return;
		}

		if ($this->getProtocolVersion() < ProtocolInfo::PROTOCOL_137) {
			$this->chunkHack = 2;
		}

		$this->usedChunks[Level::chunkHash($x, $z)] = true;
		$this->sendEncoded($buffer);

		if ($this->spawned) {
			foreach ($this->level->getChunkEntities($x, $z) as $entity) {
				if ($entity !== $this && !$entity->isClosed() && $entity->isAlive()) {
					$entity->spawnTo($this);
				}
			}
		}

		if ($this->spawnChunkLoadCount !== -1 && ++$this->spawnChunkLoadCount >= $this->spawnThreshold) {
			$this->sendPlayStatus(PlayStatusPacket::PLAYER_SPAWN);
			if ($this->getProtocolVersion() < ProtocolInfo::PROTOCOL_274) {
				$this->doFirstSpawn();
			}

			$this->spawnChunkLoadCount = -1;
		}
	}

	/**
	 * Requests chunks from the world to be sent, up to a set limit every tick. This operates on the results of the most recent chunk
	 * order.
	 */
	protected function sendNextChunk() : void
	{
		if (!$this->connected) {
			return;
		}

		Timings::$playerChunkSend->startTiming();

		$level = $this->getLevel();

		$count = 0;
		foreach ($this->loadQueue as $index => $distance) {
			if ($count >= $this->chunksPerTick) {
				break;
			}

			Level::getXZ($index, $X, $Z);
			assert(is_int($X) && is_int($Z));

			++$count;

			$this->usedChunks[$index] = false;
			$level->registerChunkLoader($this, $X, $Z, false);

			if (!$level->populateChunk($X, $Z)) {
				continue;
			}

			unset($this->loadQueue[$index]);
			$level->requestChunk($X, $Z, $this);
		}

		Timings::$playerChunkSend->stopTiming();
	}

	public function doFirstSpawn() : void
	{
		if ($this->spawned || !$this->constructed) {
			return; //avoid player spawning twice (this can only happen on 3.x with a custom malicious client)
		}
		$this->spawned = true;
		$this->setImmobile(false);

		$this->inventory->sendContents($this);
		$this->armorInventory->sendContents($this);
		$this->inventory->sendHeldItem($this);

		if ($this->hasPermission(Server::BROADCAST_CHANNEL_USERS)) {
			PermissionManager::getInstance()->subscribeToPermission(Server::BROADCAST_CHANNEL_USERS, $this);
		}
		if ($this->hasPermission(Server::BROADCAST_CHANNEL_ADMINISTRATIVE)) {
			PermissionManager::getInstance()->subscribeToPermission(Server::BROADCAST_CHANNEL_ADMINISTRATIVE, $this);
		}

		$ev = new PlayerJoinEvent(
			$this,
			new TranslationContainer(TextFormat::YELLOW . "%multiplayer.player.joined", [
				$this->getDisplayName()
			])
		);
		$ev->call();
		if (strlen(trim((string) $ev->getJoinMessage())) > 0) {
			$this->server->broadcastMessage($ev->getJoinMessage());
		}

		$this->noDamageTicks = 60;

		foreach ($this->usedChunks as $index => $hasSent) {
			if (!$hasSent) {
				continue; //this will happen when the chunk is ready to send
			}
			Level::getXZ($index, $chunkX, $chunkZ);
			foreach ($this->level->getChunkEntities($chunkX, $chunkZ) as $entity) {
				if ($entity !== $this && !$entity->closed && $entity->isAlive() && !$entity->isFlaggedForDespawn()) {
					$entity->spawnTo($this);
				}
			}
		}

		$this->spawnToAll();

		if ($this->getHealth() <= 0) {
			$this->actuallyRespawn();
		}

		$this->joined = true;
		$this->forceAsyncCompression = false;
	}

	/**
	 * @return void
	 */
	protected function sendRespawnPacket(Vector3 $pos, int $respawnState = RespawnPacket::SEARCHING_FOR_SPAWN)
	{
		$pk = new RespawnPacket();
		$pk->position = $pos->add(0, $this->baseOffset, 0);
		$pk->respawnState = $respawnState;
		$pk->entityRuntimeId = $this->getId();

		$this->dataPacket($pk);
	}

	public function getDeathPosition() : ?Position
	{
		if ($this->deathPosition !== null && !$this->deathPosition->isValid()) {
			$this->deathPosition = null;
		}
		return $this->deathPosition;
	}

	public function setDeathPosition(?Vector3 $pos) : void
	{
		if ($pos !== null) {
			if ($pos instanceof Position && $pos->level !== null) {
				$level = $pos->level;
			} else {
				$level = $this->getLevel();
			}

			$this->deathPosition = new Position((int) $pos->x, (int) $pos->y, (int) $pos->z, $level);
		} else {
			$this->deathPosition = null;
		}

		if ($this->deathPosition !== null && $this->deathPosition->level === $this->level) {
			$this->getDataPropertyManager()->setBlockPos(self::DATA_PLAYER_DEATH_POSITION, $this->deathPosition->asVector3());
			//TODO: this should be updated when dimensions are implemented
			$this->getDataPropertyManager()->setInt(self::DATA_PLAYER_DEATH_DIMENSION, $this->deathPosition->level->getDimension());
			$this->getDataPropertyManager()->setByte(self::DATA_PLAYER_HAS_DIED, 1);
		} else {
			$this->getDataPropertyManager()->setBlockPos(self::DATA_PLAYER_DEATH_POSITION, new Vector3(0, 0, 0));
			$this->getDataPropertyManager()->setInt(self::DATA_PLAYER_DEATH_DIMENSION, DimensionIds::OVERWORLD);
			$this->getDataPropertyManager()->setByte(self::DATA_PLAYER_HAS_DIED, 0);
		}
	}

	public function getSpawn() : Position
	{
		if ($this->hasValidSpawnPosition()) {
			return $this->spawnPosition;
		} else {
			$level = $this->server->getDefaultLevel();

			return $level->getSpawnLocation();
		}
	}

	public function hasValidSpawnPosition() : bool
	{
		return $this->spawnPosition !== null && $this->spawnPosition->isValid();
	}

	/**
	 * Sets the spawnpoint of the player (and the compass direction) to a Vector3, or set it on another world with a
	 * Position object
	 *
	 * @param Vector3|Position $pos
	 *
	 * @return void
	 */
	public function setSpawn(Vector3 $pos)
	{
		if (!($pos instanceof Position)) {
			$level = $this->level;
		} else {
			$level = $pos->getLevel();
		}
		$this->spawnPosition = new Position($pos->x, $pos->y, $pos->z, $level);
		$pk = new SetSpawnPositionPacket();
		$pk->x = $pk->x2 = $this->spawnPosition->getFloorX();
		$pk->y = $pk->y2 = $this->spawnPosition->getFloorY();
		$pk->z = $pk->z2 = $this->spawnPosition->getFloorZ();
		$pk->dimension = $this->spawnPosition->getLevel()->getDimension();
		$pk->spawnType = SetSpawnPositionPacket::TYPE_PLAYER_SPAWN;
		$pk->spawnForced = false;
		$this->dataPacket($pk);
	}

	public function isSleeping() : bool
	{
		return $this->sleeping !== null;
	}

	public function sleepOn(Vector3 $pos) : bool
	{
		if (!$this->isOnline()) {
			return false;
		}

		$pos = $pos->floor();
		$b = $this->level->getBlock($pos);

		$ev = new PlayerBedEnterEvent($this, $b);
		$ev->call();
		if ($ev->isCancelled()) {
			return false;
		}

		if ($b instanceof Bed) {
			$b->setOccupied();
		}

		$this->sleeping = clone $pos;

		$this->propertyManager->setBlockPos(self::DATA_PLAYER_BED_POSITION, $pos);
		$this->setPlayerFlag(self::DATA_PLAYER_FLAG_SLEEP, true);

		$this->setSpawn($pos);

		$this->level->setSleepTicks(60);
		$this->updateBoundingBox(0.2, 0.2);

		return true;
	}

	/**
	 * @return void
	 */
	public function stopSleep()
	{
		if ($this->sleeping instanceof Vector3) {
			$b = $this->level->getBlock($this->sleeping);
			if ($b instanceof Bed) {
				$b->setOccupied(false);
			}
			(new PlayerBedLeaveEvent($this, $b))->call();

			$this->sleeping = null;
			$this->updateBoundingBox(1.8, 0.6);
			$this->propertyManager->setBlockPos(self::DATA_PLAYER_BED_POSITION, null);
			$this->setPlayerFlag(self::DATA_PLAYER_FLAG_SLEEP, false);

			$this->level->setSleepTicks(0);

			$pk = new AnimatePacket();
			$pk->entityRuntimeId = $this->id;
			$pk->action = AnimatePacket::ACTION_STOP_SLEEP;
			$this->dataPacket($pk);
		}
	}

	public function setSneaking(bool $value = true) : void
	{
		parent::setSneaking($value);

		if ($value) {
			$this->updateBoundingBox(1.65, 0.6);
		} else {
			$this->updateBoundingBox(1.8, 0.6);
		}
	}

	public function setGliding(bool $value = true) : void
	{
		parent::setGliding($value);

		if ($value) {
			$this->updateBoundingBox(0.6, 0.6);
		} else {
			$this->updateBoundingBox(1.8, 0.6);
		}
	}

	public function setSwimming(bool $value = true) : void
	{
		parent::setSwimming($value);

		if ($value) {
			$this->updateBoundingBox(0.6, 0.6);
		} else {
			$this->updateBoundingBox(1.8, 0.6);
		}
	}

	public function setCrawling(bool $value = true) : void
	{
		parent::setCrawling($value);

		if($value){
			$this->updateBoundingBox(0.625, 0.6);
		}else{
			$this->updateBoundingBox(1.8, 0.6);
		}
	}

	public function getGamemode() : int
	{
		return $this->gamemode;
	}

	/**
	 * Sets the gamemode, and if needed, kicks the Player.
	 *
	 * @param bool $client if the client made this change in their GUI
	 */
	public function setGamemode(int $gm, bool $client = false) : bool
	{
		if ($gm < 0 || $gm > 3 || $this->gamemode === $gm) {
			return false;
		}

		$ev = new PlayerGameModeChangeEvent($this, $gm);
		$ev->call();
		if ($ev->isCancelled()) {
			if ($client) { //gamemode change by client in the GUI
				$this->sendGamemode();
			}
			return false;
		}

		$this->gamemode = $gm;

		$this->allowFlight = $this->isCreative();
		if ($this->isSpectator()) {
			$this->setGenericFlag(self::DATA_FLAG_HAS_COLLISION, false);
			$this->setFlying(true);
			$this->keepMovement = true;
			$this->onGround = false;

			//TODO: HACK! this syncs the onground flag with the client so that flying works properly
			//this is a yucky hack but we don't have any other options :(
			$this->sendPosition($this, null, null, MovePlayerPacket::MODE_TELEPORT);

			$this->despawnFromAll();
		} else {
			$this->setGenericFlag(self::DATA_FLAG_HAS_COLLISION, true);
			$this->keepMovement = $this->allowMovementCheats;
			$this->checkGroundState(0, 0, 0, 0, 0, 0);
			if ($this->isSurvival()) {
				$this->setFlying(false);
			}
			$this->spawnToAll();
		}

		$this->namedtag->setInt("playerGameType", $this->gamemode);
		if (!$client) { //Gamemode changed by server, do not send for client changes
			$this->sendGamemode();
		} else {
			Command::broadcastCommandMessage($this, new TranslationContainer("commands.gamemode.success.self", [Server::getGamemodeString($gm)]));
		}

		$this->inventory->sendContents($this);
		$this->inventory->sendHeldItem($this->hasSpawned);

		$this->sendAbilitiesAndAdventureSettings(); //TODO: we might be able to do this with the abilities packet alone
		$this->inventory->sendCreativeContents();

		return true;
	}

	/**
	 * @return void
	 * @internal
	 * Sends the player's gamemode to the client.
	 */
	public function sendGamemode()
	{
		$pk = new SetPlayerGameTypePacket();
		$pk->gamemode = TypeConverter::getInstance()->coreGameModeToProtocol($this->gamemode);
		$this->dataPacket($pk);
	}

	public function sendAbilities() : void
	{
		if ($this->getProtocolVersion() >= ProtocolInfo::PROTOCOL_534) {
			//ALL of these need to be set for the base layer, otherwise the client will cry
			$boolAbilities = [
				AbilitiesLayer::ABILITY_ALLOW_FLIGHT => $this->getAllowFlight(),
				AbilitiesLayer::ABILITY_FLYING => $this->isFlying(),
				AbilitiesLayer::ABILITY_NO_CLIP => $this->isSpectator(),
				AbilitiesLayer::ABILITY_OPERATOR => ($this->isOp() && !$this->isSpectator()),
				AbilitiesLayer::ABILITY_TELEPORT => $this->hasPermission("pocketmine.command.teleport"),
				AbilitiesLayer::ABILITY_INVULNERABLE => $this->isCreative(),
				AbilitiesLayer::ABILITY_MUTED => $this->muted,
				AbilitiesLayer::ABILITY_WORLD_BUILDER => false,
				AbilitiesLayer::ABILITY_INFINITE_RESOURCES => !$this->hasFiniteResources(),
				AbilitiesLayer::ABILITY_LIGHTNING => false,
				AbilitiesLayer::ABILITY_BUILD => !$this->isSpectator(),
				AbilitiesLayer::ABILITY_MINE => !$this->isSpectator(),
				AbilitiesLayer::ABILITY_DOORS_AND_SWITCHES => !$this->isSpectator(),
				AbilitiesLayer::ABILITY_OPEN_CONTAINERS => !$this->isSpectator(),
				AbilitiesLayer::ABILITY_ATTACK_PLAYERS => !$this->isSpectator(),
				AbilitiesLayer::ABILITY_ATTACK_MOBS => !$this->isSpectator(),
			];

			$layers = [
				//TODO: dynamic flying speed! FINALLY!!!!!!!!!!!!!!!!!
				new AbilitiesLayer(AbilitiesLayer::LAYER_BASE, $boolAbilities, 0.05, 1, 0.1),
			];

			if ($this->getProtocolVersion() >= ProtocolInfo::PROTOCOL_582) {
				if ($this->isSpectator()) {
					//TODO: HACK! In 1.19.80, the client starts falling in our faux spectator mode when it clips into a
					//block. We can't seem to prevent this short of forcing the player to always fly when block collision is
					//disabled. Also, for some reason the client always reads flight state from this layer if present, even
					//though the player isn't in spectator mode.

					$layers[] = new AbilitiesLayer(AbilitiesLayer::LAYER_SPECTATOR, [
						AbilitiesLayer::ABILITY_FLYING => true,
					], null, null, null);
				}
			}

			$this->sendDataPacket(UpdateAbilitiesPacket::create(new AbilitiesData(
				(($this->isOp() && !$this->isSpectator()) ? AdventureSettingsPacket::PERMISSION_OPERATOR : AdventureSettingsPacket::PERMISSION_NORMAL),
				(($this->isOp() && !$this->isSpectator()) ? PlayerPermissions::OPERATOR : PlayerPermissions::MEMBER),
				$this->getId(),
				$layers
			)));
		} else {
			$pk = new AdventureSettingsPacket();

			if ($this->getProtocolVersion() >= ProtocolInfo::PROTOCOL_137) {
				$pk->setFlag(AdventureSettingsPacket::BUILD, !$this->isSpectator());
				$pk->setFlag(AdventureSettingsPacket::MINE, !$this->isSpectator());
				$pk->setFlag(AdventureSettingsPacket::DOORS_AND_SWITCHES, !$this->isSpectator());
				$pk->setFlag(AdventureSettingsPacket::OPEN_CONTAINERS, !$this->isSpectator());
				$pk->setFlag(AdventureSettingsPacket::ATTACK_PLAYERS, !$this->isSpectator());
				$pk->setFlag(AdventureSettingsPacket::ATTACK_MOBS, !$this->isSpectator());
				$pk->setFlag(AdventureSettingsPacket::OPERATOR, ($this->isOp() && !$this->isSpectator()));
				$pk->setFlag(AdventureSettingsPacket::TELEPORT, $this->hasPermission("pocketmine.command.teleport"));
			}
			$pk->setFlag(AdventureSettingsPacket::WORLD_IMMUTABLE, $this->isSpectator());
			$pk->setFlag(AdventureSettingsPacket::NO_PVP, $this->isSpectator());
			$pk->setFlag(AdventureSettingsPacket::AUTO_JUMP, $this->autoJump);
			$pk->setFlag(AdventureSettingsPacket::ALLOW_FLIGHT, $this->allowFlight);
			$pk->setFlag(AdventureSettingsPacket::NO_CLIP, $this->isSpectator());
			$pk->setFlag(AdventureSettingsPacket::FLYING, $this->flying);

			$pk->commandPermission = (($this->isOp() && !$this->isSpectator()) ? AdventureSettingsPacket::PERMISSION_OPERATOR : AdventureSettingsPacket::PERMISSION_NORMAL);
			$pk->playerPermission = (($this->isOp() && !$this->isSpectator()) ? PlayerPermissions::OPERATOR : PlayerPermissions::MEMBER);
			$pk->entityUniqueId = $this->getId();

			$this->dataPacket($pk);
		}
	}

	public function sendAdventureSettings() : void
	{
		if ($this->getProtocolVersion() >= ProtocolInfo::PROTOCOL_534) {
			$pk = new UpdateAdventureSettingsPacket();
			$pk->noAttackingMobs = false;
			$pk->noAttackingPlayers = false;
			$pk->worldImmutable = false;
			$pk->showNameTags = true;
			$pk->autoJump = $this->hasAutoJump();

			$this->dataPacket($pk);
		} else {
			$this->sendAbilities();
		}
	}

	public function sendAbilitiesAndAdventureSettings() : void
	{
		$this->sendAbilities();
		if ($this->getProtocolVersion() >= ProtocolInfo::PROTOCOL_534) {
			$this->sendAdventureSettings();
		}
	}

	/**
	 * NOTE: Because Survival and Adventure Mode share some similar behaviour, this method will also return true if the player is
	 * in Adventure Mode. Supply the $literal parameter as true to force a literal Survival Mode check.
	 *
	 * @param bool $literal whether a literal check should be performed
	 */
	public function isSurvival(bool $literal = false) : bool
	{
		if ($literal) {
			return $this->gamemode === Player::SURVIVAL;
		} else {
			return ($this->gamemode & 0x01) === 0;
		}
	}

	/**
	 * NOTE: Because Creative and Spectator Mode share some similar behaviour, this method will also return true if the player is
	 * in Spectator Mode. Supply the $literal parameter as true to force a literal Creative Mode check.
	 *
	 * @param bool $literal whether a literal check should be performed
	 */
	public function isCreative(bool $literal = false) : bool
	{
		if ($literal) {
			return $this->gamemode === Player::CREATIVE;
		} else {
			return ($this->gamemode & 0x01) === 1;
		}
	}

	/**
	 * NOTE: Because Adventure and Spectator Mode share some similar behaviour, this method will also return true if the player is
	 * in Spectator Mode. Supply the $literal parameter as true to force a literal Adventure Mode check.
	 *
	 * @param bool $literal whether a literal check should be performed
	 */
	public function isAdventure(bool $literal = false) : bool
	{
		if ($literal) {
			return $this->gamemode === Player::ADVENTURE;
		} else {
			return ($this->gamemode & 0x02) > 0;
		}
	}

	public function isSpectator() : bool
	{
		return $this->gamemode === Player::SPECTATOR;
	}

	/**
	 * TODO: make this a dynamic ability instead of being hardcoded
	 */
	public function hasFiniteResources() : bool
	{
		return $this->gamemode === Player::SURVIVAL || $this->gamemode === Player::ADVENTURE;
	}

	public function isFireProof() : bool
	{
		return $this->isCreative();
	}

	public function getDrops() : array
	{
		if (!$this->isCreative()) {
			return parent::getDrops();
		}

		return [];
	}

	public function getXpDropAmount() : int
	{
		if (!$this->server->keepExperience && !$this->isCreative() && !$this->keepExperience) {
			return parent::getXpDropAmount();
		}

		return 0;
	}

	protected function checkGroundState(float $wantedX, float $wantedY, float $wantedZ, float $dx, float $dy, float $dz) : void
	{
		if ($this->gamemode === GameMode::SPECTATOR) {
			$this->onGround = false;
		} else {
			$bb = clone $this->boundingBox;
			$bb->minY = $this->y - 0.2;
			$bb->maxY = $this->y + 0.2;

			//we're already at the new position at this point; check if there are blocks we might have landed on between
			//the old and new positions (running down stairs necessitates this)
			$bb = $bb->addCoord(-$dx, -$dy, -$dz);

			$this->onGround = $this->isCollided = count($this->getLevel()->getCollisionBlocks($bb, true)) > 0;
		}
	}

	public function canBeMovedByCurrents() : bool
	{
		return false; //currently has no server-side movement
	}

	/**
	 * @return void
	 */
	protected function checkNearEntities()
	{
		foreach ($this->level->getNearbyEntities($this->boundingBox->expandedCopy(1, 0.5, 1), $this) as $entity) {
			$entity->scheduleUpdate();

			if (!$entity->isAlive() || $entity->isFlaggedForDespawn()) {
				continue;
			}

			$entity->onCollideWithPlayer($this);
		}
	}

	/**
	 * Attempts to move the player to the given coordinates. Unless you have some particularly specialized logic, you
	 * probably want to use teleport() instead of this.
	 *
	 * This is used for processing movements sent by the player over network.
	 *
	 * @param Vector3 $newPos Coordinates of the player's feet, centered horizontally at the base of their bounding box.
	 */
	public function handleMovement(Vector3 $newPos) : void
	{
		Timings::$playerMove->startTiming();
		try {
			$this->actuallyHandleMovement($newPos);
		} finally {
			Timings::$playerMove->stopTiming();
		}
	}

	private function actuallyHandleMovement(Vector3 $newPos) : void
	{
		$this->moveRateLimit--;
		if ($this->moveRateLimit < 0) {
			return;
		}

		$oldPos = $this->asLocation();
		$distanceSquared = $newPos->distanceSquared($oldPos);

		$revert = false;

		if ($distanceSquared > 100) {
			//TODO: this is probably too big if we process every movement
			/* !!! BEWARE YE WHO ENTER HERE !!!
			 *
			 * This is NOT an anti-cheat check. It is a safety check.
			 * Without it hackers can teleport with freedom on their own and cause lots of undesirable behaviour, like
			 * freezes, lag spikes and memory exhaustion due to sync chunk loading and collision checks across large distances.
			 * Not only that, but high-latency players can trigger such behaviour innocently.
			 *
			 * If you must tamper with this code, be aware that this can cause very nasty results. Do not waste our time
			 * asking for help if you suffer the consequences of messing with this.
			 */
			$this->server->getLogger()->debug($this->getName() . " moved too fast, reverting movement");
			$this->server->getLogger()->debug("Old position: " . $this->asVector3() . ", new position: " . $newPos);
			$revert = true;
		} elseif (!$this->level->isInLoadedTerrain($newPos)) {
			$revert = true;
			$this->nextChunkOrderRun = 0;
		}

		$ev = new PlayerPreMoveEvent($this, $this->asLocation(), Location::fromObject($newPos, $this->level));
		if ($revert) {
			$ev->setCancelled();
		}
		$ev->call();

		if ($ev->isCancelled()) {
			$revert = true;
		}

		$newPos = $ev->getTo();

		if (!$revert && $distanceSquared != 0) {
			$dx = $newPos->x - $this->x;
			$dy = $newPos->y - $this->y;
			$dz = $newPos->z - $this->z;

			//the client likes to clip into blocks like stairs, but we do full server-side prediction of that without
			//help from the client's position changes, so we deduct the expected clip height from the moved distance.
			$expectedClipDistance = $this->ySize * (1 - self::STEP_CLIP_MULTIPLIER);
			$dy -= $expectedClipDistance;

			$this->move($dx, $dy, $dz);

			$diff = $this->distanceSquared($newPos);

			if ($diff > 0) {
				$this->setPosition($newPos);
			}
		}

		if ($revert) {
			$this->revertMovement($oldPos);
		}
	}

	/**
	 * Fires movement events and synchronizes player movement, every tick.
	 */
	protected function processMostRecentMovements() : void
	{
		$now = microtime(true);
		$multiplier = $this->lastMovementProcess !== null ? ($now - $this->lastMovementProcess) * 20 : 1;
		$exceededRateLimit = $this->moveRateLimit < 0;
		$this->moveRateLimit = min(self::MOVE_BACKLOG_SIZE, max(0, $this->moveRateLimit) + self::MOVES_PER_TICK * $multiplier);
		$this->lastMovementProcess = $now;

		$from = new Location($this->lastX, $this->lastY, $this->lastZ, $this->lastYaw, $this->lastPitch, $this->level);
		$to = $this->getLocation();
		$this->speed = $to->subtractVector($from);

		$delta = (($this->lastX - $to->x) ** 2) + (($this->lastY - $to->y) ** 2) + (($this->lastZ - $to->z) ** 2);
		$deltaAngle = abs($this->lastYaw - $to->yaw) + abs($this->lastPitch - $to->pitch);

		if ($delta > 0.0001 || $deltaAngle > 1.0) {
			$ev = new PlayerMoveEvent($this, $from, $to);

			$ev->call();

			if ($ev->isCancelled()) {
				$this->revertMovement($from);
				return;
			}

			if ($to->distanceSquared($ev->getTo()) > 0.01) { //If plugins modify the destination
				$this->teleport($ev->getTo());
				return;
			}

			$this->lastX = $to->x;
			$this->lastY = $to->y;
			$this->lastZ = $to->z;

			$this->lastYaw = $to->yaw;
			$this->lastPitch = $to->pitch;
			$this->broadcastMovement();

			$distance = sqrt((($from->x - $to->x) ** 2) + (($from->z - $to->z) ** 2));
			if ($this->isSprinting()) {
				$this->exhaust(0.1 * $distance, PlayerExhaustEvent::CAUSE_SPRINTING);
			} elseif ($this->isSwimming()) {
				$this->exhaust(0.015 * $distance, PlayerExhaustEvent::CAUSE_SWIMMING);
			} else {
				$this->exhaust(0.01 * $distance, PlayerExhaustEvent::CAUSE_WALKING);
			}

			if ($this->isOnGround() && $this->isGliding()) {
				$this->toggleGlide(false);
			}

			if ($this->nextChunkOrderRun > 20) {
				$this->nextChunkOrderRun = 20;
			}
		}

		if ($exceededRateLimit) { //client and server positions will be out of sync if this happens
			$this->server->getLogger()->debug("Player " . $this->getName() . " exceeded movement rate limit, forcing to last accepted position");
			$this->sendPosition($this, $this->yaw, $this->pitch, MovePlayerPacket::MODE_RESET);
		}
	}

	protected function revertMovement(Location $from) : void
	{
		$this->lastX = $from->x;
		$this->lastY = $from->y;
		$this->lastZ = $from->z;

		$this->lastYaw = $from->yaw;
		$this->lastPitch = $from->pitch;

		$this->setPosition($from);
		$this->sendPosition($from, $from->yaw, $from->pitch, MovePlayerPacket::MODE_RESET);
	}

	public function fall(float $fallDistance) : void
	{
		if (!$this->flying) {
			parent::fall($fallDistance);
		}
	}

	public function setSkin(Skin $skin) : void
	{
		parent::setSkin($skin);
		if ($this->spawned) {
			$this->server->updatePlayerListData($this->getUniqueId(), $this->getId(), $this->getDisplayName(), $this->getSkin(), $this->getXuid());
		}
	}

	public function jump() : void
	{
		(new PlayerJumpEvent($this))->call();
		parent::jump();
	}

	/**
	 * Performs actions associated with the attack action (left-click) without a target entity.
	 * Under normal circumstances, this will just play the no-damage attack sound and the arm-swing animation.
	 */
	public function missSwing() : void
	{
		$ev = new PlayerMissSwingEvent($this);
		$ev->call();
		if (!$ev->isCancelled()) {
			$pk = new LevelSoundEventPacket();
			$pk->sound = LevelSoundEventPacket::SOUND_ATTACK_NODAMAGE;
			$pk->position = $this;
			$pk->entityType = "minecraft:player";
			$pk->extraData = -1;
			$pk->isBabyMob = false;
			$pk->disableRelativeVolume = false;
			$this->sendDataPacket($pk);

			$this->server->broadcastPacket($this->getViewers(), $pk);

			$pk = new ActorEventPacket();
			$pk->entityRuntimeId = $this->id;
			$pk->event = ActorEventPacket::ARM_SWING;
			$pk->data = 0;
			$this->server->broadcastPacket($this->getViewers(), $pk);
		}
	}

	public function setMotion(Vector3 $motion) : bool
	{
		if (parent::setMotion($motion)) {
			$this->broadcastMotion();

			return true;
		}
		return false;
	}

	protected function updateMovement(bool $teleport = false) : void
	{

	}

	protected function tryChangeMovement() : void
	{

	}

	public function getSpeed() : Vector3
	{
		return $this->speed ?? new Vector3(0, 0, 0);
	}

	/**
	 * @return void
	 */
	public function sendAttributes(bool $sendAll = false)
	{
		$entries = $sendAll ? $this->attributeMap->getAll() : $this->attributeMap->needSend();
		if (count($entries) > 0) {
			$pk = new UpdateAttributesPacket();
			$pk->entityRuntimeId = $this->id;
			$pk->entries = $entries;
			$this->dataPacket($pk);
			foreach ($entries as $entry) {
				$entry->markSynchronized();
			}
		}
	}

	public function onUpdate(int $currentTick) : bool
	{
		if (!$this->loggedIn) {
			return false;
		}

		$tickDiff = $currentTick - $this->lastUpdate;

		if ($tickDiff <= 0) {
			return true;
		}

		$this->messageCounter = 2;

		$this->lastUpdate = $currentTick;

		$this->sendAttributes();

		if (!$this->isAlive() && $this->spawned) {
			if (!$this->isKilled) {
				$this->isKilled = true;
				$this->kill();
			} else {
				$this->onDeathUpdate($tickDiff);
			}
			return true;
		}

		$this->timings->startTiming();

		if ($this->spawned) {
			if ($this->getInventory() !== null) {
				$this->inventory->getItemInHand()->onUpdate($this);
			}
			if ($this->getOffHandInventory() !== null) {
				$this->offHandInventory->getItemInOffHand()->onUpdate($this);
			}
			$this->speed = new Vector3(0, 0, 0);

			Timings::$playerMove->startTiming();
			$this->processMostRecentMovements();
			$this->motion->x = $this->motion->y = $this->motion->z = 0; //TODO: HACK! (Fixes player knockback being messed up)
			if ($this->onGround) {
				$this->inAirTicks = 0;
			} else {
				$this->inAirTicks += $tickDiff;
			}
			Timings::$playerMove->stopTiming();

			Timings::$entityBaseTick->startTiming();
			$this->entityBaseTick($tickDiff);
			Timings::$entityBaseTick->stopTiming();

			if (!$this->isSpectator() && $this->isAlive()) {
				Timings::$playerCheckNearEntities->startTiming();
				$this->checkNearEntities();
				Timings::$playerCheckNearEntities->stopTiming();
			}

			if ($this->getProtocolVersion() < ProtocolInfo::PROTOCOL_137 && $this->chunkHack > 0) {
				--$this->chunkHack;
				if ($this->chunkHack === 0) {
					$pk = new ChunkRadiusUpdatedPacket();
					$pk->radius = $this->viewDistance;
					$this->sendDataPacket($pk);
				} elseif ($this->chunkHack === 1) {
					$pk = new ChunkRadiusUpdatedPacket();
					$pk->radius = $this->viewDistance + 1;
					$this->sendDataPacket($pk);
				}
			}
		}

		$this->timings->stopTiming();

		return true;
	}

	protected function doFoodTick(int $tickDiff = 1) : void
	{
		if ($this->isSurvival()) {
			parent::doFoodTick($tickDiff);
		}
	}

	public function exhaust(float $amount, int $cause = PlayerExhaustEvent::CAUSE_CUSTOM) : float
	{
		if ($this->isSurvival()) {
			return parent::exhaust($amount, $cause);
		}

		return 0.0;
	}

	public function isHungry() : bool
	{
		return $this->isSurvival() && parent::isHungry();
	}

	public function canEat() : bool{
		return $this->isCreative() || parent::canEat();
	}

	public function canBreathe() : bool
	{
		return $this->isCreative() || parent::canBreathe();
	}

	protected function sendEffectAdd(EffectInstance $effect, bool $replacesOldEffect) : void
	{
		$pk = new MobEffectPacket();
		$pk->entityRuntimeId = $this->getId();
		$pk->eventId = $replacesOldEffect ? MobEffectPacket::EVENT_MODIFY : MobEffectPacket::EVENT_ADD;
		$pk->effectId = $effect->getId();
		$pk->amplifier = $effect->getAmplifier();
		$pk->particles = $effect->isVisible();
		$pk->duration = $effect->getDuration();

		$this->dataPacket($pk);
	}

	protected function sendEffectRemove(EffectInstance $effect) : void
	{
		$pk = new MobEffectPacket();
		$pk->entityRuntimeId = $this->getId();
		$pk->eventId = MobEffectPacket::EVENT_REMOVE;
		$pk->effectId = $effect->getId();

		$this->dataPacket($pk);
	}

	protected function selectChunks() : \Generator
	{
		$radius = $this->server->getAllowedViewDistance($this->viewDistance);
		$radiusSquared = $radius ** 2;

		$centerX = $this->getFloorX() >> Chunk::COORD_BIT_SIZE;
		$centerZ = $this->getFloorZ() >> Chunk::COORD_BIT_SIZE;

		for ($x = 0; $x < $radius; ++$x) {
			for ($z = 0; $z <= $x; ++$z) {
				if (($x ** 2 + $z ** 2) > $radiusSquared) {
					break; //skip to next band
				}

				//If the chunk is in the radius, others at the same offsets in different quadrants are also guaranteed to be.

				/* Top right quadrant */
				yield Level::chunkHash($centerX + $x, $centerZ + $z);
				/* Top left quadrant */
				yield Level::chunkHash($centerX - $x - 1, $centerZ + $z);
				/* Bottom right quadrant */
				yield Level::chunkHash($centerX + $x, $centerZ - $z - 1);
				/* Bottom left quadrant */
				yield Level::chunkHash($centerX - $x - 1, $centerZ - $z - 1);

				if ($x !== $z) {
					/* Top right quadrant mirror */
					yield Level::chunkHash($centerX + $z, $centerZ + $x);
					/* Top left quadrant mirror */
					yield Level::chunkHash($centerX - $z - 1, $centerZ + $x);
					/* Bottom right quadrant mirror */
					yield Level::chunkHash($centerX + $z, $centerZ - $x - 1);
					/* Bottom left quadrant mirror */
					yield Level::chunkHash($centerX - $z - 1, $centerZ - $x - 1);
				}
			}
		}
	}

	protected function orderChunks() : void
	{
		if (!$this->connected || $this->viewDistance === -1 || !$this->doOrderChunks) {
			return;
		}

		Timings::$playerChunkOrder->startTiming();

		$newOrder = [];
		$unloadChunks = $this->usedChunks;

		foreach ($this->selectChunks() as $hash) {
			if (!isset($this->usedChunks[$hash]) || $this->usedChunks[$hash] === false) {
				$newOrder[$hash] = true;
			}
			unset($unloadChunks[$hash]);
		}

		foreach ($unloadChunks as $index => $bool) {
			Level::getXZ($index, $X, $Z);
			$this->unloadChunk($X, $Z);
		}

		$this->loadQueue = $newOrder;
		if ($this->getProtocolVersion() >= ProtocolInfo::PROTOCOL_313) {
			if (count($this->loadQueue) > 0 || count($unloadChunks) > 0) {
				$pk = new NetworkChunkPublisherUpdatePacket();
				$pk->x = $this->getFloorX();
				$pk->y = $this->getFloorY();
				$pk->z = $this->getFloorZ();
				$pk->radius = $this->viewDistance * 16; //blocks, not chunks >.>
				$this->sendDataPacket($pk);
			}
		}

		Timings::$playerChunkOrder->stopTiming();
	}

	public function getUsedChunks() : array
	{
		return $this->usedChunks;
	}

	/**
	 * Ticks the chunk-requesting mechanism.
	 */
	public function checkNetwork() : void
	{
		if (!$this->isOnline()) {
			return;
		}

		if ($this->nextChunkOrderRun !== PHP_INT_MAX && $this->nextChunkOrderRun-- <= 0) {
			$this->nextChunkOrderRun = PHP_INT_MAX;
			$this->orderChunks();
		}

		if (count($this->loadQueue) > 0 || !$this->spawned) {
			$this->sendNextChunk();
		}

		$this->flushSendBuffer();
	}

	/**
	 * Returns whether the player can interact with the specified position. This checks distance and direction.
	 *
	 * @param float $maxDiff defaults to half of the 3D diagonal width of a block
	 */
	public function canInteract(Vector3 $pos, float $maxDistance, float $maxDiff = M_SQRT3 / 2) : bool
	{
		$eyePos = $this->getPosition()->add(0, $this->getEyeHeight(), 0);
		if ($eyePos->distanceSquared($pos) > $maxDistance ** 2) {
			return false;
		}

		$dV = $this->getDirectionVector();
		$eyeDot = $dV->dot($eyePos);
		$targetDot = $dV->dot($pos);
		return ($targetDot - $eyeDot) >= -$maxDiff;
	}

	protected function initHumanData() : void
	{
		$this->setNameTag($this->username);
	}

	protected function initEntity() : void
	{
		parent::initEntity();
		$this->addDefaultWindows();

		if (($level = $this->server->getLevelByName($this->namedtag->getString("SpawnLevel", ""))) instanceof Level) {
			$this->spawnPosition = new Position($this->namedtag->getInt("SpawnX"), $this->namedtag->getInt("SpawnY"), $this->namedtag->getInt("SpawnZ"), $level);
		}
		if (($level = $this->server->getLevelByName($this->namedtag->getString("DeathLevel", ""))) instanceof Level) {
			$this->deathPosition = new Position($this->namedtag->getInt("DeathPositionX"), $this->namedtag->getInt("DeathPositionY"), $this->namedtag->getInt("DeathPositionZ"), $level);
		}
	}

	public function handleRequestNetworkSettings(RequestNetworkSettingsPacket $packet) : bool
	{
		if ($this->enableCompression) {
			return false;
		}

		$this->protocolVersion = $packet->getProtocolVersion();
		if ($this->getProtocolVersion() < ProtocolInfo::PROTOCOL_554 || !in_array($this->protocolVersion, ProtocolInfo::ACCEPTED_PROTOCOLS, true)) {
			$packet = new PlayStatusPacket();
			$packet->status = ($this->protocolVersion < ProtocolInfo::CURRENT_PROTOCOL ? PlayStatusPacket::LOGIN_FAILED_CLIENT : PlayStatusPacket::LOGIN_FAILED_SERVER);
			$packet->setProtocol($this->protocolVersion);
			$this->sendDataPacket($packet);

			$this->flushSendBuffer(true);

			//This pocketmine disconnect message will only be seen by the console (PlayStatusPacket causes the messages to be shown for the client)
			$this->close("", $this->server->getLanguage()->translateString("pocketmine.disconnect.incompatibleProtocol", [$this->protocolVersion ?? "unknown"]), false);

			return true;
		}

		//TODO: we're filling in the defaults to get pre-1.19.30 behaviour back for now, but we should explore the new options in the future
		$settings = new NetworkSettingsPacket();
		$settings->compressionThreshold = NetworkSettingsPacket::COMPRESS_EVERYTHING;
		$settings->compressionAlgorithm = CompressionAlgorithm::ZLIB;
		$settings->enableClientThrottling = false;
		$settings->clientThrottleThreshold = 0;
		$settings->clientThrottleScalar = 0;
		$this->sendDataPacket($settings);

		$this->flushSendBuffer(true);

		$this->enableCompression = true;

		return true;
	}

	public function handleLogin(LoginPacket $packet) : bool
	{
		if ($this->seenLoginPacket || $this->loggedIn) {
			return false;
		}
		$this->seenLoginPacket = true;

		$this->protocolVersion = $packet->protocol;
		if (!$packet->isValidProtocol) {
			if ($packet->protocol < ProtocolInfo::CURRENT_PROTOCOL) {
				$this->sendPlayStatus(PlayStatusPacket::LOGIN_FAILED_CLIENT, true);
			} else {
				$this->sendPlayStatus(PlayStatusPacket::LOGIN_FAILED_SERVER, true);
			}

			//This pocketmine disconnect message will only be seen by the console (PlayStatusPacket causes the messages to be shown for the client)
			$this->close("", $this->server->getLanguage()->translateString("pocketmine.disconnect.incompatibleProtocol", [$packet->protocol ?? "unknown"]), false);

			return true;
		}

		if (!self::isValidUserName($packet->username)) {
			$this->close("", "disconnectionScreen.invalidName");

			return true;
		}

		if ((UUID::fromString($packet->clientUUID)->getVersion()) !== 3) {
			$this->close("", $this->server->getLanguage()->translateString("pocketmine.disconnect.invalidSession", ["%pocketmine.disconnect.invalidSession.badSignature"]));

			return true;
		}

		$this->protocolVersion = $packet->protocol;

		$protocolConvertor = ProtocolConvertor::getInstance();
		$this->chunkProtocolVersion = $protocolConvertor->getChunkProtocol($packet->protocol);
		$this->craftingProtocolVersion = $protocolConvertor->getCratingProtocol($packet->protocol);
		$this->mapProtocolVersion = $protocolConvertor->getMapProtocol($packet->protocol);

		$this->username = TextFormat::clean($packet->username);
		if ($this->server->deleteSpacesForNickname) {
			$this->username = str_replace(" ", $this->server->replaceSpacesNickname, $this->username);
		}
		$this->displayName = $this->username;
		$this->iusername = strtolower($this->username);

		if ($packet->locale !== null) {
			$this->locale = $packet->locale;
		}

		$this->deviceOS = $packet->clientData["DeviceOS"] ?? 0;
		$this->deviceModel = $packet->clientData["DeviceModel"] ?? "";
		$this->deviceId = $packet->clientData["DeviceId"] ?? "";
		$this->currentInputMode = $packet->clientData["CurrentInputMode"] ?? -1;
		$this->defaultInputMode = $packet->clientData["DefaultInputMode"] ?? -1;

		$this->vanillaVersion = $packet->clientData["GameVersion"] ?? "";

		if (count($this->server->getOnlinePlayers()) >= $this->server->getMaxPlayers() && $this->kick("disconnectionScreen.serverFull", false)) {
			return true;
		}

		$this->randomClientId = $packet->clientId;
		$this->serverAddress = $packet->serverAddress;

		$this->uuid = UUID::fromString($packet->clientUUID);
		$this->rawUUID = $this->uuid->toBinary();

		if ($this->getProtocolVersion() >= ProtocolInfo::PROTOCOL_370) {
			$animations = [];
			foreach ($packet->clientData["AnimatedImageData"] as $animation) {
				$animations[] = new SkinAnimation(
					new SkinImage(
						$animation["ImageHeight"],
						$animation["ImageWidth"],
						base64_decode(
							$animation["Image"],
							true
						)
					),
					$animation["Type"],
					$animation["Frames"],
					($this->getProtocolVersion() >= ProtocolInfo::PROTOCOL_419) ? $animation["AnimationExpression"] : 0
				);
			}

			if ($this->getProtocolVersion() === ProtocolInfo::PROTOCOL_390 || $this->getProtocolVersion() >= ProtocolInfo::PROTOCOL_407) {
				$personaPieces = [];
				foreach ($packet->clientData["PersonaPieces"] as $piece) {
					$personaPieces[] = new PersonaSkinPiece(
						$piece["PieceId"],
						$piece["PieceType"],
						$piece["PackId"],
						$piece["IsDefault"],
						$piece["ProductId"]
					);
				}

				$pieceTintColors = [];
				foreach ($packet->clientData["PieceTintColors"] as $tintColor) {
					$pieceTintColors[] = new PersonaPieceTintColor($tintColor["PieceType"], $tintColor["Colors"]);
				}

				$armSize = $packet->clientData["ArmSize"] ?? "wide";
				$skinColor = isset($packet->clientData["SkinColor"]) ? Color::fromHexString($packet->clientData["SkinColor"]) : new Color(0, 0, 0);
				$personaPieces = SplFixedArray::fromArray($personaPieces);
				$pieceTintColors = SplFixedArray::fromArray($pieceTintColors);
			} else {
				$armSize = "wide";
				$skinColor = new Color(0, 0, 0);
				$personaPieces = SplFixedArray::fromArray([]);
				$pieceTintColors = SplFixedArray::fromArray([]);
			}

			$serializedSkin = new SerializedSkin(
				$packet->clientData["SkinId"],
				$packet->clientData["PlayFabId"] ?? "",
				new SkinImage(
					$packet->clientData["SkinImageHeight"],
					$packet->clientData["SkinImageWidth"],
					base64_decode($packet->clientData["SkinData"], true)
				),
				$packet->clientData["CapeId"] ?? "",
				new SkinImage(
					$packet->clientData["CapeImageHeight"],
					$packet->clientData["CapeImageWidth"],
					base64_decode($packet->clientData["CapeData"] ?? "", true)
				),
				base64_decode($packet->clientData["SkinResourcePatch"] ?? "", true),
				base64_decode($packet->clientData["SkinGeometryData"] ?? "", true),
				base64_decode($packet->clientData["SkinGeometryDataEngineVersion"] ?? "", true),
				base64_decode($packet->clientData["AnimationData"] ?? "", true),
				$animations,
				$packet->clientData["PremiumSkin"] ?? false,
				$packet->clientData["PersonaSkin"] ?? false,
				$packet->clientData["CapeOnClassicSkin"] ?? false,
				null,
				$armSize,
				$skinColor,
				$personaPieces,
				$pieceTintColors,
				true, //assume this is true? there's no field for it ...
				$packet->clientData["OverrideSkin"] ?? true,
			);
			$skin = $serializedSkin->toSkin();
		} else {
			$skin = new Skin(
				$packet->clientData["SkinId"],
				base64_decode($packet->clientData["SkinData"], true),
				base64_decode($packet->clientData["CapeData"] ?? "", true),
				$packet->clientData["SkinGeometryName"] ?? "",
				base64_decode($packet->clientData["SkinGeometry"] ?? "", true)
			);
		}

		if (!$skin->isValid()) {
			$this->close("", "disconnectionScreen.invalidSkin");

			return true;
		}

		$this->setSkin($skin);

		$ev = new PlayerPreLoginEvent($this, "Plugin reason");
		$ev->call();
		if ($ev->isCancelled()) {
			$this->close("", $ev->getKickMessage());

			return true;
		}

		if (!$this->server->isWhitelisted($this->username) && $this->kick("Server is white-listed", false)) {
			return true;
		}

		if (
			($this->isBanned() || $this->server->getIPBans()->isBanned($this->getAddress())) &&
			$this->kick("You are banned", false)
		) {
			return true;
		}

		if (!$packet->skipVerification) {
			$this->server->getAsyncPool()->submitTask(new ProcessLoginTask($this, $packet));
		} else {
			$this->onVerifyCompleted($packet, null, true);
		}

		return true;
	}

	public function sendPlayStatus(int $status, bool $immediate = false) : void
	{
		$pk = new PlayStatusPacket();
		$pk->status = $status;
		$this->sendDataPacket($pk, false, $immediate);
	}

	public function onVerifyCompleted(LoginPacket $packet, ?string $error, bool $signedByMojang) : void
	{
		if ($this->closed) {
			return;
		}

		if ($error !== null) {
			$this->close("", $this->server->getLanguage()->translateString("pocketmine.disconnect.invalidSession", [$error]));

			return;
		}

		if ($this->getProtocolVersion() >= ProtocolInfo::PROTOCOL_137) {
			$xuid = $packet->xuid;
			if (($xuid === "" || !is_string($xuid)) && $this->getProtocolVersion() >= ProtocolInfo::PROTOCOL_975) {
				$xuid = (string) (
					$packet->clientData["PlatformOnlineId"] ??
					$packet->clientData["PlatformOfflineId"] ??
					$packet->clientData["PlayFabId"] ??
					""
				);
			}

			if (!$signedByMojang && $xuid !== "") {
				$this->server->getLogger()->warning($this->getName() . " has an XUID, but their login keychain is not signed by Mojang");
				if (!$packet->skipVerification) {
					$xuid = "";
				}
			}

			if ($xuid === "" || !is_string($xuid)) {
				if ($signedByMojang) {
					$this->server->getLogger()->error($this->getName() . " should have an XUID, but none found");
				}

				if ($packet->skipVerification && $this->getProtocolVersion() >= ProtocolInfo::PROTOCOL_975) {
					$this->xuid = str_replace("-", "", $packet->clientUUID);
					$this->xboxAuthenticated = true;
					$this->server->getLogger()->warning($this->getName() . " connected on protocol " . $this->getProtocolVersion() . " using fallback XUID handling");
					goto xuid_done;
				}

				if ($this->server->requiresAuthentication() && $this->kick("disconnectionScreen.notAuthenticated", false)) { //use kick to allow plugins to cancel this
					return;
				}

				$this->xboxAuthenticated = false;
				$this->server->getLogger()->debug($this->getName() . " is NOT logged into Xbox Live");
			} else {
				$this->server->getLogger()->debug($this->getName() . " is logged into Xbox Live");
				$this->xuid = $xuid;
				$this->xboxAuthenticated = true;
			}

			xuid_done:
		} else {
			$this->xboxAuthenticated = $signedByMojang;
			if (!$this->xboxAuthenticated) {
				if ($this->server->requiresAuthentication() && $this->kick("disconnectionScreen.notAuthenticated", false)) { //use kick to allow plugins to cancel this
					return;
				}

				$this->server->getLogger()->debug($this->getName() . " is NOT logged into Xbox Live");
			} else {
				$this->server->getLogger()->debug($this->getName() . " is logged into Xbox Live");
			}
		}

		$identityPublicKey = base64_decode($packet->identityPublicKey, true);

		if ($identityPublicKey === false || $identityPublicKey === "") {
			if ($packet->skipVerification) {
				$this->server->getLogger()->warning($this->getName() . " logged in without an identity public key; continuing without encryption handshake");
				$this->processLogin();
				return;
			}

			//if this is invalid it should have borked VerifyLoginTask
			$this->close("", "We should never have reached here if the key is invalid");
			return;
		}

		if (EncryptionContext::$ENABLED) {
			$this->server->getAsyncPool()->submitTask(new PrepareEncryptionTask(
				$identityPublicKey,
				function (string $encryptionKey, string $handshakeJwt, string $publicServerKey, string $serverToken) : void {
					if (!$this->connected) {
						return;
					}

					$pk = new ServerToClientHandshakePacket();
					$pk->publicKey = $publicServerKey;
					$pk->serverToken = $serverToken;
					$pk->jwt = $handshakeJwt;
					$this->sendDataPacket($pk, false, true); //make sure this gets sent before encryption is enabled

					$this->awaitingEncryptionHandshake = true;

					if ($this->getProtocolVersion() < ProtocolInfo::PROTOCOL_431) {
						$this->cipher = EncryptionContext::cfb8($encryptionKey);
					} else {
						$this->cipher = EncryptionContext::fakeGCM($encryptionKey);
					}

					$this->server->getLogger()->debug("Enabled encryption for " . $this->username);
				}
			));
		} else {
			$this->processLogin();
		}
	}

	/**
	 * @internal
	 */
	public function onEncryptionHandshake() : bool
	{
		if (!$this->awaitingEncryptionHandshake) {
			return false;
		}
		$this->awaitingEncryptionHandshake = false;

		$this->server->getLogger()->debug("Encryption handshake completed for " . $this->username);

		$this->processLogin();
		return true;
	}

	protected function processLogin() : void
	{
		foreach ($this->server->getLoggedInPlayers() as $p) {
			if ($p !== $this && ($p->iusername === $this->iusername || $this->getUniqueId()->equals($p->getUniqueId()))) {
				$this->close($this->getLeaveMessage(), "Logged in from another location!");

				return;
			}
		}

		if ($this->loggedIn) {
			return; //спасает от одновременного входа игроков с одним ником
		}

		$this->namedtag = $this->server->getOfflinePlayerData($this->username);

		$this->playedBefore = ($this->getLastPlayed() - $this->getFirstPlayed()) > 1; // microtime(true) - microtime(true) may have less than one millisecond difference
		$this->namedtag->setString("NameTag", $this->username);

		$this->gamemode = $this->namedtag->getInt("playerGameType", self::SURVIVAL) & 0x03;
		if ($this->server->getForceGamemode()) {
			$this->gamemode = $this->server->getGamemode();
			$this->namedtag->setInt("playerGameType", $this->gamemode);
		}

		$this->allowFlight = $this->isCreative();
		$this->keepMovement = $this->isSpectator() || $this->allowMovementCheats();

		if (($level = $this->server->getLevelByName($this->namedtag->getString("Level", "", true))) === null) {
			$this->setLevel($this->server->getDefaultLevel());
			$this->namedtag->setString("Level", $this->level->getFolderName());
			$spawnLocation = $this->level->getSafeSpawn();
			$this->namedtag->setTag(new ListTag("Pos", [
				new DoubleTag("", $spawnLocation->x),
				new DoubleTag("", $spawnLocation->y),
				new DoubleTag("", $spawnLocation->z)
			]));
		} else {
			$this->setLevel($level);
		}

		$this->onServerLoginSuccess();
	}

	private function onServerLoginSuccess() : void
	{
		$this->loggedIn = true;
		$this->server->onPlayerLogin($this);

		$this->sendPlayStatus(PlayStatusPacket::LOGIN_SUCCESS);

		$this->server->getLogger()->debug("Initiating resource packs phase for " . $this->getName());

		$packManager = $this->server->getResourcePackManager($this->getProtocolVersion());
		$resourcePacks = $packManager->getResourceStack();
		$keys = [];
		foreach ($resourcePacks as $resourcePack) {
			$key = $packManager->getPackEncryptionKey($resourcePack->getPackId());
			if ($key !== null) {
				$keys[$resourcePack->getPackId()] = $key;
			}
		}
		$event = new PlayerResourcePackOfferEvent($this, $resourcePacks, $keys, $packManager->resourcePacksRequired());
		$event->call();

		$resourcePackEntries = array_map(function (ResourcePack $pack) use ($event) : ResourcePackInfoEntry {
			//TODO: more stuff

			return new ResourcePackInfoEntry(
				$pack->getPackId(),
				$pack->getPackVersion(),
				$pack->getPackSize(),
				$event->getEncryptionKeys()[$pack->getPackId()] ?? "",
				"",
				$pack->getPackId(),
				false,
				false,
				false,
				""
			);
		}, $event->getResourcePacks());
		//TODO: support forcing server packs
		$this->sendDataPacket(ResourcePacksInfoPacket::create(
			resourcePackEntries: $resourcePackEntries,
			behaviorPackEntries: [],
			mustAccept: $event->mustAccept(),
			hasAddons: false,
			hasScripts: false,
			forceServerPacks: false,
			cdnUrls: [],
			worldTemplateId: UUID::fromBinary(str_repeat("\x00", 16), 0),
			worldTemplateVersion: "",
			forceDisableVibrantVisuals: true,
		));
	}

	public function handleResourcePackClientResponse(ResourcePackClientResponsePacket $packet) : bool
	{
		if ($this->resourcePacksDone) {
			return false;
		}
		switch ($packet->status) {
			case ResourcePackClientResponsePacket::STATUS_REFUSED:
				//TODO: add lang strings for this
				$this->close("", "You must accept resource packs to join this server.", true);
				break;
			case ResourcePackClientResponsePacket::STATUS_SEND_PACKS:
				$manager = $this->server->getResourcePackManager($this->getProtocolVersion());
				foreach ($packet->packIds as $uuid) {
					//dirty hack for mojang's dirty hack for versions
					$splitPos = strpos($uuid, "_");
					if ($splitPos !== false) {
						$uuid = substr($uuid, 0, $splitPos);
					}
					$pack = $manager->getPackById($uuid);

					if (!($pack instanceof ResourcePack)) {
						//Client requested a resource pack but we don't have it available on the server
						$this->close("", "disconnectionScreen.resourcePack", true);
						$this->server->getLogger()->debug("Got a resource pack request for unknown pack with UUID " . $uuid . ", available packs: " . implode(", ", $manager->getPackIdList()));

						return false;
					}

					$this->sendDataPacket(ResourcePackDataInfoPacket::create(
						$pack->getPackId(),
						self::RESOURCE_PACK_CHUNK_SIZE,
						(int) ceil($pack->getPackSize() / self::RESOURCE_PACK_CHUNK_SIZE),
						$pack->getPackSize(),
						$pack->getSha256(),
						false,
						ResourcePackType::RESOURCES //TODO: this might be an addon (not behaviour pack), needed to properly support client-side custom items
					));
				}

				$this->server->getLogger()->debug("Player " . $this->getName() . " requested download of " . count($packet->packIds) . " resource packs");
				break;
			case ResourcePackClientResponsePacket::STATUS_HAVE_ALL_PACKS:
				$this->haveAllPacks = true;

				$packManager = $this->server->getResourcePackManager($this->getProtocolVersion());
				$stack = array_map(static function (ResourcePack $pack) : ResourcePackStackEntry {
					return new ResourcePackStackEntry($pack->getPackId(), $pack->getPackVersion(), ""); //TODO: subpacks
				}, $packManager->getResourceStack());

				//we support chemistry blocks by default, the client should already have this installed
				$stack[] = new ResourcePackStackEntry("0fba4063-dba1-4281-9b89-ff9390653530", "1.0.0", "");

				//we don't force here, because it doesn't have user-facing effects
				//but it does have an annoying side-effect when true: it makes
				//the client remove its own non-server-supplied resource packs.
				$this->sendDataPacket(ResourcePackStackPacket::create($stack, [], false, false, ProtocolInfo::MINECRAFT_VERSION_NETWORK, new Experiments([], false), false));
				$this->server->getLogger()->debug("Applying resource pack stack for " . $this->getName());
				break;
			case ResourcePackClientResponsePacket::STATUS_COMPLETED:
				if ($this->haveAllPacks) {
					$this->server->getLogger()->debug("Resource packs sequence completed for " . $this->getName());
					$this->resourcePacksDone = true;
					$this->completeLoginSequence();
				}
				break;
			default:
				return false;
		}

		return true;
	}

	protected function completeLoginSequence() : void
	{
		if ($this->loginProcessed) {
			$this->close("", "Trying to login after logging in");
			$this->server->getNetwork()->blockAddress($this->ip, 1200);
			$this->server->getLogger()->debug("Attempted to complete login sequence while it was already completed from " . $this->getName());
			return;
		}
		$this->loginProcessed = true;

		/** @var float[] $pos */
		$pos = $this->namedtag->getListTag("Pos")->getAllValues();
		$spawnLocation = new Position($pos[0], $pos[1], $pos[2], $this->level);
		$level = $spawnLocation->getLevel();
		//load the spawn chunk so we can see the terrain
		$xSpawnChunk = $spawnLocation->getFloorX() >> Chunk::COORD_BIT_SIZE;
		$zSpawnChunk = $spawnLocation->getFloorZ() >> Chunk::COORD_BIT_SIZE;
		$this->level->registerChunkLoader($this, $xSpawnChunk, $zSpawnChunk, true);
		$this->usedChunks[Level::chunkHash($xSpawnChunk, $zSpawnChunk)] = false;

		parent::__construct($this->level, $this->namedtag);
		$ev = new PlayerLoginEvent($this, "Plugin reason");
		$ev->call();
		if ($ev->isCancelled()) {
			$this->close($this->getLeaveMessage(), $ev->getKickMessage());

			return;
		}

		$this->server->getLogger()->debug("Waiting for safe respawn position to be located for " . $this->getName());

		$safeSpawn = $this->getSpawn();

		Timings::$playerNetworkSendPreSpawnGameData->startTiming();
		try {
			$pk = new StartGamePacket();
			$pk->entityUniqueId = $this->id;
			$pk->entityRuntimeId = $this->id;
			$pk->playerGamemode = TypeConverter::getInstance()->coreGameModeToProtocol($this->gamemode);

			$pk->playerPosition = $this->getOffsetPosition($this);

			$pk->pitch = $this->pitch;
			$pk->yaw = $this->yaw;

			$pk->seed = -1;
			$pk->spawnSettings = new SpawnSettings(SpawnSettings::BIOME_TYPE_DEFAULT, "", $this->level->getDimension()); //TODO: implement this properly
			$pk->gameRules = $this->level->getGameRules()->getRules();
			$pk->worldGamemode = TypeConverter::getInstance()->coreGameModeToProtocol($this->server->getGamemode());
			$pk->difficulty = $this->level->getDifficulty();
			$pk->spawnX = $safeSpawn->getFloorX();
			$pk->spawnY = $safeSpawn->getFloorY();
			$pk->spawnZ = $safeSpawn->getFloorZ();
			$pk->hasAchievementsDisabled = true;
			$pk->time = $this->level->getTime();
			$pk->eduEditionOffer = 0;
			$pk->eduMode = false;
			$pk->rainLevel = 0; //TODO: implement these properly
			$pk->lightningLevel = 0;
			$pk->commandsEnabled = true;
			$pk->levelId = "";
			$pk->worldName = $this->server->getMotd();
			$pk->experiments = new Experiments([], false);

			$pk->playerMovementSettings = new PlayerMovementSettings(ServerAuthMovementMode::SERVER_AUTHORITATIVE_V2, 0, false);
			$pk->serverSoftwareVersion = sprintf("%s %s", VersionInfo::NAME, VersionInfo::VERSION()->getFullVersion(true));
			$pk->playerActorProperties = new CompoundTag("");
			$pk->blockPaletteChecksum = 0; //we don't bother with this (0 skips verification) - the preimage is some dumb stringified NBT, not even actual NBT
			$pk->worldTemplateId = UUID::fromBinary(str_repeat("\x00", 16), 0);
			$pk->networkPermissions = new NetworkPermissions(disableClientSounds: true);
			$pk->vanillaVersion = $this->vanillaVersion;

			if ($this->getProtocolVersion() >= ProtocolInfo::PROTOCOL_282) {
				if ($this->getProtocolVersion() < ProtocolInfo::PROTOCOL_419) {
					$pk->blockEncodePalette = RuntimeBlockMapping::getInstance($this->getProtocolVersion())->getEncodeBedrockKnownStates();
				}

				if ($this->getProtocolVersion() >= ProtocolInfo::PROTOCOL_361 && $this->getProtocolVersion() < ProtocolInfo::PROTOCOL_776) {
					if ($this->getProtocolVersion() >= ProtocolInfo::PROTOCOL_419) {
						$pk->itemTable = GlobalItemTypeDictionary::getInstance($this->getProtocolVersion())->getDictionary()->getEntries();
					} else {
						$pk->legacyItemTable = LegacyItemIdToStringIdMap::getInstance($this->getProtocolVersion())->getStringToLegacyMap();
					}
				}
			}

			$this->dataPacket($pk);

			if ($this->getProtocolVersion() >= ProtocolInfo::PROTOCOL_776) {
				$this->sendDataPacket(ItemRegistryPacket::create(GlobalItemTypeDictionary::getInstance($this->getProtocolVersion())->getDictionary()->getEntries()));
			}

			if ($this->getProtocolVersion() >= ProtocolInfo::PROTOCOL_332) {
				$this->sendDataPacket(StaticPacketCache::getInstance()->getAvailableActorIdentifiers($this->getProtocolVersion()));
				$this->sendDataPacket(StaticPacketCache::getInstance()->getBiomeDefs($this->getProtocolVersion()));
			}

			$this->level->sendTime($this);

			$this->sendAttributes(true);
			$this->setNameTagVisible();
			$this->setNameTagAlwaysVisible();
			$this->setCanClimb();
			$this->setImmobile(); //disable pre-spawn movement

			$this->server->getLogger()->info($this->getServer()->getLanguage()->translateString("pocketmine.player.logIn", [
				TextFormat::AQUA . $this->username . TextFormat::WHITE,
				$this->ip,
				$this->port,
				$this->id,
				$this->level->getName(),
				round($this->x, 4),
				round($this->y, 4),
				round($this->z, 4),
				$this->vanillaVersion,
				$this->getProtocolVersion()
			]));

			if ($this->isOp()) {
				$this->setRemoveFormat(false);
			}

			$this->sendCommandData();
			$this->sendAbilitiesAndAdventureSettings();
			$this->sendPotionEffects($this);
			$this->sendData($this);

			$this->sendAllInventories();
			$this->inventory->sendCreativeContents();
			$this->inventory->sendHeldItem($this);

			$this->queueEncoded(CraftingManager::getCraftingDataPacket($this->getCraftingProtocol()));

			$this->server->addOnlinePlayer($this);
			$this->server->sendFullPlayerListData($this);
		} finally {
			Timings::$playerNetworkSendPreSpawnGameData->stopTiming();
		}
	}

	/**
	 * Sends a chat message as this player. If the message begins with a / (forward-slash) it will be treated
	 * as a command.
	 */
	public function chat(string $message) : bool
	{
		if (!$this->spawned || !$this->isAlive()) {
			return false;
		}

		if ($this->getProtocolVersion() >= ProtocolInfo::PROTOCOL_137) {
			$this->doCloseInventory();
		}

		//Fast length check, to make sure we don't get hung trying to explode MBs of string ...
		$maxTotalLength = $this->messageCounter * (self::MAX_CHAT_BYTE_LENGTH + 1);
		if (strlen($message) > $maxTotalLength) {
			return false;
		}

		$message = $message;
		foreach (explode("\n", $message, $this->messageCounter + 1) as $messagePart) {
			if (trim($messagePart) !== "" && strlen($messagePart) <= self::MAX_CHAT_BYTE_LENGTH && mb_strlen($messagePart, 'UTF-8') <= self::MAX_CHAT_CHAR_LENGTH && $this->messageCounter-- > 0) {
				if (strpos($messagePart, './') === 0) {
					$messagePart = substr($messagePart, 1);
				}

				$ev = new PlayerCommandPreprocessEvent($this, $messagePart);
				$ev->call();

				if ($ev->isCancelled()) {
					break;
				}

				if (strpos($ev->getMessage(), "/") === 0) {
					Timings::$playerCommand->startTiming();
					$this->server->dispatchCommand($ev->getPlayer(), substr($ev->getMessage(), 1));
					Timings::$playerCommand->stopTiming();
				} else {
					$ev = new PlayerChatEvent($this, $ev->getMessage());
					$ev->call();
					if (!$ev->isCancelled()) {
						$this->server->broadcastMessage($this->getServer()->getLanguage()->translateString($ev->getFormat(), [$ev->getPlayer()->getDisplayName(), $ev->getMessage()]), $ev->getRecipients());
					}
				}
			}
		}

		return true;
	}

	public function updateNextPosition(Vector3 $position, float $yaw, float $headYaw, float $pitch) : void
	{
		foreach ([$position->x, $position->y, $position->z, $yaw, $headYaw, $pitch] as $float) {
			if (is_infinite($float) || is_nan($float)) {
				$this->server->getLogger()->debug("Invalid movement from " . $this->getName() . ", contains NAN/INF components");
				return;
			}
		}

		$newPos = $position->round(4)->subtract(0, $this->baseOffset, 0);
		if ($this->forceMoveSync && $newPos->distanceSquared($this) > 1) { //Tolerate up to 1 block to avoid problems with client-sided physics when spawning in blocks
			$this->sendPosition($this, null, null, MovePlayerPacket::MODE_NORMAL);
			$this->server->getLogger()->debug("Got outdated pre-teleport movement from " . $this->getName() . ", received " . $newPos . ", expected " . $this->asVector3());
			//Still getting movements from before teleport, ignore them
			return;
		} elseif ((!$this->isAlive() || !$this->spawned) && $newPos->distanceSquared($this) > 0.01) {
			$this->sendPosition($this, null, null, MovePlayerPacket::MODE_RESET);
			$this->server->getLogger()->debug("Reverted movement of " . $this->getName() . " due to not alive or not spawned, received " . $newPos . ", locked at " . $this->asVector3());
			return;
		}

		// Once we get a movement within a reasonable distance, treat it as a teleport ACK and remove position lock
		$this->forceMoveSync = false;

		$yaw = fmod($yaw, 360);
		$pitch = fmod($pitch, 360);

		if ($yaw < 0) {
			$yaw += 360;
		}

		$this->setRotation($yaw, $pitch);
		$this->handleMovement($newPos);
	}

	public function removeBlock(Vector3 $blockVector) : bool
	{
		if ($this->isSpectator()) {
			return true;
		}

		$time = time();
		if ($time !== $this->removeBlockLast) {
			$this->removeBlockCounter = 0;
		}

		if (++$this->removeBlockCounter > 30) {
			$this->getServer()->getNetwork()->blockAddress($this->getAddress());
			$this->removeBlockLast = $time;
			return true;
		}
		$this->removeBlockLast = $time;

		$this->doCloseInventory();

		if (!$this->isAdventure()) {
			$item = $this->inventory->getItemInHand();
			$oldItem = clone $item;

			if (
				$this->canInteract($blockVector->add(0.5, 0.5, 0.5), $this->isCreative() ? 13 : 6) &&
				$this->level->useBreakOn($blockVector, $item, $this, true)
			) {
				if (!$this->isCreative()) {
					if (!$item->equalsExact($oldItem)) {
						$this->inventory->setItemInHand($item);
						$this->inventory->sendHeldItem($this->hasSpawned);
					}

					$this->exhaust(0.025, PlayerExhaustEvent::CAUSE_MINING);
				}
				return true;
			}
		}

		$this->inventory->sendContents($this);
		$this->inventory->sendHeldItem($this);

		$target = $this->level->getBlock($blockVector);
		$blocks = $target->getAllSides();
		$blocks[] = $target;

		$this->level->sendBlocks([$this], $blocks, UpdateBlockPacket::FLAG_ALL_PRIORITY);

		foreach ($blocks as $b) {
			$tile = $this->level->getTile($b);
			if ($tile instanceof Spawnable) {
				$tile->spawnTo($this);
			}
		}

		return true;
	}

	/**
	 * Performs a left-click (attack) action on the block.
	 *
	 * @return bool if an action took place successfully
	 */
	public function attackBlock(Vector3 $pos, int $face) : bool
	{
		if ($pos->distanceSquared($this) > 10000) {
			return false;
		}

		$target = $this->level->getBlock($pos);

		$ev = new PlayerInteractEvent($this, $this->inventory->getItemInHand(), $target, null, $face, PlayerInteractEvent::LEFT_CLICK_BLOCK);
		$ev->call();
		if ($ev->isCancelled()) {
			if ($this->getProtocolVersion() < ProtocolInfo::PROTOCOL_137) {
				$this->inventory->sendContents($this);
				return false;
			}

			$this->inventory->sendHeldItem($this);
			return false;
		}

		if ($target->onAttack($this->inventory->getItemInHand(), $face, $this)) {
			return true;
		}

		$block = $target->getSide($face);
		if ($block->getId() === Block::FIRE) {
			$this->level->setBlock($block, BlockFactory::get(Block::AIR));
			$this->level->broadcastLevelSoundEvent($block, LevelSoundEventPacket::SOUND_EXTINGUISH_FIRE);
			return true;
		}

		if (!$this->isCreative()) {
			//TODO: improve this to take stuff like swimming, ladders, enchanted tools into account, fix wrong tool break time calculations for bad tools (pmmp/PocketMine-MP#211)
			$breakTime = ceil(($target->getBreakTime($this->inventory->getItemInHand())) * 20);
			if ($breakTime > 0) {
				$this->level->broadcastLevelEvent($pos, LevelEventPacket::EVENT_BLOCK_START_BREAK, (int) (65535 / $breakTime));
			}
		}

		return true;
	}

	public function attackEntity(Entity $target) : bool
	{
		if ($this->isSpectator()) {
			return true;
		}

		if (!$target->isAlive()) {
			return true;
		}

		if ($target instanceof ItemEntity || $target instanceof Arrow) {
			$this->kick("Attempting to attack an invalid entity");
			$this->server->getLogger()->warning($this->getServer()->getLanguage()->translateString("pocketmine.player.invalidEntity", [$this->getName()]));
			return false;
		}

		$cancelled = false;

		$heldItem = $this->inventory->getItemInHand();

		if (!$this->canInteract($target, 8)) {
			$cancelled = true;
		} elseif ($target instanceof Player) {
			if (!$this->server->getConfigBool("pvp")) {
				$cancelled = true;
			}
		}

		$knockback = 1.0;
		if ($this->isSprinting()) {
			$knockback = 1.3;
		}
		$ev = new EntityDamageByEntityEvent($this, $target, EntityDamageEvent::CAUSE_ENTITY_ATTACK, $heldItem->getAttackPoints(), [], $knockback);

		$meleeEnchantmentDamage = 0;
		/** @var EnchantmentInstance[] $meleeEnchantments */
		$meleeEnchantments = [];
		foreach ($heldItem->getEnchantments() as $enchantment) {
			$type = $enchantment->getType();
			if ($type instanceof MeleeWeaponEnchantment && $type->isApplicableTo($target)) {
				$meleeEnchantmentDamage += $type->getDamageBonus($enchantment->getLevel());
				$meleeEnchantments[] = $enchantment;
			}
		}
		$ev->setModifier($meleeEnchantmentDamage, EntityDamageEvent::MODIFIER_WEAPON_ENCHANTMENTS);

		if ($cancelled) {
			$ev->setCancelled();
		}

		if (!$this->isSprinting() && !$this->isFlying() && $this->fallDistance > 0 && !$this->hasEffect(Effect::BLINDNESS) && !$this->isUnderwater()) {
			$ev->setModifier($ev->getFinalDamage() / 2, EntityDamageEvent::MODIFIER_CRITICAL);
		}

		$target->attack($ev);

		if ($ev->isCancelled()) {
			if ($heldItem instanceof Durable && $this->isSurvival()) {
				$this->inventory->sendContents($this);
			}
			return true;
		}

		if ($ev->getModifier(EntityDamageEvent::MODIFIER_CRITICAL) > 0) {
			$pk = new AnimatePacket();
			$pk->action = AnimatePacket::ACTION_CRITICAL_HIT;
			$pk->entityRuntimeId = $target->getId();
			$this->server->broadcastPacket($target->getViewers(), $pk);
			if ($target instanceof Player) {
				$target->dataPacket($pk);
			}
		}

		foreach ($meleeEnchantments as $enchantment) {
			$type = $enchantment->getType();
			assert($type instanceof MeleeWeaponEnchantment);
			$type->onPostAttack($this, $target, $enchantment->getLevel());
		}

		if ($this->isAlive()) {
			//reactive damage like thorns might cause us to be killed by attacking another mob, which
			//would mean we'd already have dropped the inventory by the time we reached here
			if ($heldItem->onAttackEntity($target) && $this->isSurvival()) { //always fire the hook, even if we are survival
				$this->inventory->setItemInHand($heldItem);
			}

			$this->exhaust(0.3, PlayerExhaustEvent::CAUSE_ATTACK);
		}

		return true;
	}

	public function handleCommandStep(CommandStepPacket $packet) : bool
	{
		if ($this->spawned === false || !$this->isAlive()) {
			return true;
		}

		$message = $packet->command;
		$pocketmineCommand = $this->server->getCommandMap()->getCommand($message);
		$commandDataJson = $pocketmineCommand->getJsonCommandData();
		$overload = $commandDataJson["versions"][0]["overloads"][$packet->overload] ?? null;
		if ($overload !== null && $packet->inputJson !== null) {
			if (is_countable($packet->inputJson)) {
				if (count($packet->inputJson) > self::MAX_INPUT_JSON) {
					$this->server->getLogger()->critical("Too many inputJson in CommandStepPacket from " . $this->getName());
					return false;
				}
			}

			$parameters = $overload["input"]["parameters"];
			foreach ($parameters as $parameter) {
				if (isset($packet->inputJson[$parameter["name"]])) {
					$arg = $packet->inputJson[$parameter["name"]];
					$message .= match ($parameter["type"]) {
						"blockpos" => " " . $arg["x"] . " " . $arg["y"] . " " . $arg["z"],
						"target" => " " . $arg["rules"][0]["value"],
						"rotation" => " " . $arg["rotation"],
						default => " " . $arg,
					};
				}
			}

			unset($overload, $commandDataJson, $parameters, $arg);
		}

		$this->chat("/" . $message);

		return true;
	}

	public function handleLevelSoundEvent(LevelSoundEventPacket $packet) : bool
	{
		//TODO: add events so plugins can change this
		if (
			($packet->sound === LevelSoundEventPacket::SOUND_ATTACK_NODAMAGE && $this->isSpectator()) ||
			$packet->sound === LevelSoundEventPacket::SOUND_THROW || //Being sent by server itself
			$packet->sound === LevelSoundEventPacket::SOUND_NOTE ||
			$packet->sound === LevelSoundEventPacket::SOUND_UNDEFINED
		) {
			return false;
		}

		$this->getLevel()->broadcastPacketToViewers($this, $packet);
		return true;
	}

	public function handleEntityEvent(ActorEventPacket $packet) : bool
	{
		if (!$this->spawned || !$this->isAlive()) {
			return true;
		}

		if ($this->getProtocolVersion() >= ProtocolInfo::PROTOCOL_137) {
			$this->doCloseInventory();
		}

		switch ($packet->event) {
			case ActorEventPacket::USE_ITEM: //Eating
				if ($this->getProtocolVersion() < ProtocolInfo::PROTOCOL_137) {
					$this->consumeItem($this->inventory->getItemInHand());
				}

				break;
			case ActorEventPacket::EATING_ITEM: // Eating particles
				if (!$this->isUsingItem()) {
					return false;
				}

				$itemInHand = $this->inventory->getItemInHand();
				$itemProtocol = $itemInHand->getItemProtocol($this->getProtocolVersion()) ?? $itemInHand;
				if ($itemProtocol->getId() !== $packet->data) {
					return false;
				}

				if (!$itemProtocol instanceof Consumable) {
					return false;
				}

				$currentTick = $this->server->getTick();
				if ($currentTick - $this->lastEatingSound >= 4) { // duct tape for eating sound spam bug
					$this->sendDataPacket($packet);

					foreach ($this->getViewers() as $viewer) {
						$packet->data = ($itemInHand->getItemProtocol($viewer->getProtocolVersion()) ?? $itemInHand)->getId();

						$viewer->sendDataPacket($packet);
					}

					$this->lastEatingSound = $currentTick;
				}

				break;
			default:
				return false;
		}

		return true;
	}

	/**
	 * Don't expect much from this handler. Most of it is roughly hacked and duct-taped together.
	 */
	public function handleInventoryTransaction(InventoryTransactionPacket $packet) : bool
	{
		if (!$this->spawned || !$this->isAlive()) {
			return false;
		}

		if (count($packet->trData->getActions()) > 100) {
			$this->server->getLogger()->critical("Too many actions in inventory transaction from " . $this->getName());
			return false;
		}

		$result = true;
		if ($packet->trData instanceof NormalTransactionData) {
			$result = $this->handleNormalTransaction($packet->trData);
		} elseif ($packet->trData instanceof MismatchTransactionData) {
			$this->sendAllInventories();

			$result = true;
		} elseif ($packet->trData instanceof UseItemTransactionData) {
			$result = $this->handleUseItemTransaction($packet->trData);
		} elseif ($packet->trData instanceof UseItemOnEntityTransactionData) {
			$result = $this->handleUseItemOnEntityTransaction($packet->trData);
		} elseif ($packet->trData instanceof ReleaseItemTransactionData) {
			$result = $this->handleReleaseItemTransaction($packet->trData);
		}

		if (!$result && $this->connected) {
			$this->inventory->sendContents($this);
		}

		return $result;
	}

	private function executeInventoryTransaction(InventoryTransaction $transaction) : bool
	{
		try {
			if ($this->getProtocolVersion() >= ProtocolInfo::PROTOCOL_137) {
				$transaction->execute();
			} else {
				$nextActions = $this->faultyOldTransactionActions;
				$this->faultyOldTransactionActions = [];

				$transaction->execute();

				if (count($nextActions) !== 0) {
					$nextTransaction = new InventoryTransaction($this, $nextActions);
					$nextTransaction->setSkipValidation(true);
					$this->executeInventoryTransaction($nextTransaction);
				}
			}
		} catch (TransactionValidationException $e) {
			$this->server->getLogger()->debug("Failed to execute inventory transaction from " . $this->getName() . ": " . $e->getMessage());
			$this->server->getLogger()->debug("Actions: " . json_encode($transaction->getActions()));

			$this->sendAllInventories();

			return false;
		}

		return true;
	}

	private function handleNormalTransaction(NormalTransactionData $data) : bool
	{
		/** @var InventoryAction[] $actions */
		$actions = [];
		$isCraftingPart = false;
		$isFinalCraftingPart = false;
		foreach ($data->getActions() as $networkInventoryAction) {
			if ($this->getProtocolVersion() >= ProtocolInfo::PROTOCOL_388) {
				if ($this->getProtocolVersion() < ProtocolInfo::PROTOCOL_407) {
					if (
						$networkInventoryAction->sourceType === NetworkInventoryAction::SOURCE_CONTAINER &&
						$networkInventoryAction->windowId === ContainerIds::UI &&
						$networkInventoryAction->inventorySlot === 50 &&
						!$networkInventoryAction->oldItem->getItemStack()->equalsExact($networkInventoryAction->newItem->getItemStack())
					) {
						$isCraftingPart = true;
						if (!$networkInventoryAction->oldItem->getItemStack()->isNull() && $networkInventoryAction->newItem->getItemStack()->isNull()) {
							$isFinalCraftingPart = true;
						}
					} elseif (
						$networkInventoryAction->sourceType === NetworkInventoryAction::SOURCE_TODO && (
							$networkInventoryAction->windowId === NetworkInventoryAction::SOURCE_TYPE_CRAFTING_RESULT ||
							$networkInventoryAction->windowId === NetworkInventoryAction::SOURCE_TYPE_CRAFTING_USE_INGREDIENT
						)
					) {
						$isCraftingPart = true;
					}
				} else {
					if (
						$networkInventoryAction->sourceType === NetworkInventoryAction::SOURCE_TODO && (
							$networkInventoryAction->windowId === NetworkInventoryAction::SOURCE_TYPE_CRAFTING_RESULT ||
							$networkInventoryAction->windowId === NetworkInventoryAction::SOURCE_TYPE_CRAFTING_USE_INGREDIENT
						)
					) {
						$isCraftingPart = true;
						if ($networkInventoryAction->windowId === NetworkInventoryAction::SOURCE_TYPE_CRAFTING_RESULT) {
							$isFinalCraftingPart = true;
						}
					}
				}
			} else {
				if ((
					$networkInventoryAction->sourceType === NetworkInventoryAction::SOURCE_TODO ||
						$networkInventoryAction->sourceType === NetworkInventoryAction::SOURCE_UNTRACKED_INTERACTION_UI
				) && (
					$networkInventoryAction->windowId === NetworkInventoryAction::SOURCE_TYPE_CRAFTING_RESULT ||
					$networkInventoryAction->windowId === NetworkInventoryAction::SOURCE_TYPE_CRAFTING_USE_INGREDIENT
				)) {
					$isCraftingPart = true;
					if ($networkInventoryAction->windowId === NetworkInventoryAction::SOURCE_TYPE_CRAFTING_RESULT) {
						$isFinalCraftingPart = true;
					}
				}
			}

			try {
				$action = $networkInventoryAction->createInventoryAction($this);
				if ($action !== null) {
					$actions[] = $action;
				}
			} catch (UnexpectedValueException $e) {
				$this->server->getLogger()->debug("Unhandled inventory action from " . $this->getName() . ": " . $e->getMessage());
				$this->sendAllInventories();
				return false;
			}
		}

		if ($isCraftingPart) {
			if ($this->craftingTransaction === null) {
				$this->craftingTransaction = new CraftingTransaction($this, $actions);
			} else {
				foreach ($actions as $action) {
					$this->craftingTransaction->addAction($action);
				}
			}

			if ($isFinalCraftingPart) {
				//we get the actions for this in several packets, so we need to wait until we have all the pieces before
				//trying to execute it

				$ret = true;
				try {
					$this->craftingTransaction->execute();
				} catch (TransactionValidationException $e) {
					$this->server->getLogger()->debug("Failed to execute crafting transaction for " . $this->getName() . ": " . $e->getMessage());
					$ret = false;
				}

				$this->craftingTransaction = null;
				return $ret;
			}

			return true;
		} elseif ($this->craftingTransaction !== null) {
			$this->server->getLogger()->debug("Got unexpected normal inventory action with incomplete crafting transaction from " . $this->getName() . ", refusing to execute crafting");
			$this->craftingTransaction = null;
		}

		$this->setUsingItem(false);

		$transaction = new InventoryTransaction($this, $actions);
		return $this->executeInventoryTransaction($transaction);
	}

	public function handleUseItemTransaction(UseItemTransactionData $data) : bool
	{
		if ($this->inventory->getHeldItemSlot() !== $data->getHotbarSlot()) {
			$this->inventory->equipItem($data->getHotbarSlot(), $data->getHotbarSlot());
		}

		$blockVector = $data->getBlockPos();
		$face = $data->getFace();

		switch ($data->getActionType()) {
			case UseItemTransactionData::ACTION_CLICK_BLOCK:
				//TODO: start hack for client spam bug
				$spamBug = (
					$this->lastRightClickData !== null &&
					microtime(true) - $this->lastRightClickTime < 0.1 && //100ms
					$this->lastRightClickData->getPlayerPos()->distanceSquared($data->getPlayerPos()) < 0.00001 &&
					$this->lastRightClickData->getBlockPos()->equals($data->getBlockPos()) &&
					$this->lastRightClickData->getClickPos()->distanceSquared($data->getClickPos()) < 0.00001 //signature spam bug has 0 distance, but allow some error
				);
				//get rid of continued spam if the player clicks and holds right-click
				$this->lastRightClickData = $data;
				$this->lastRightClickTime = microtime(true);
				if ($spamBug) {
					return true;
				}
				//TODO: end hack for client spam bug

				$this->useItem($blockVector, $data->getClickPos(), $data->getItemInHand()->getItemStack(), $face);
				return true;
			case UseItemTransactionData::ACTION_BREAK_BLOCK:
				return $this->removeBlock($blockVector);
			case UseItemTransactionData::ACTION_CLICK_AIR:
				$item = $data->getItemInHand()->getItemStack();
				if (!$this->isCreative() && !$item->equals($this->inventory->getItemInHand())) {
					$this->inventory->sendHeldItem($this);
					return false;
				}

				if ($this->getProtocolVersion() >= ProtocolInfo::PROTOCOL_388 && $this->isUsingItem()) {
					if ($this->consumeItem($item)) {
						$action = CompletedUsingItemPacket::ACTION_CONSUME;
					} else {
						$action = CompletedUsingItemPacket::ACTION_UNKNOWN;
					}
					$this->completeUsingItem($item, $action);
				} else {
					$this->useItem($data->getBlockPos(), $data->getClickPos(), $item, -1);
				}
				return true;
			default:
				//unknown
				break;
		}

		return false;
	}

	private function handleUseItemOnEntityTransaction(UseItemOnEntityTransactionData $data) : bool
	{
		$target = $this->level->getEntity($data->getActorRuntimeId());
		if ($target === null) {
			return false;
		}

		if ($this->inventory->getHeldItemSlot() !== $data->getHotbarSlot()) {
			$this->inventory->equipItem($data->getHotbarSlot(), $data->getHotbarSlot());
		}

		switch ($data->getActionType()) {
			case UseItemOnEntityTransactionData::ACTION_INTERACT:
				if (!$target->isAlive()) {
					return true;
				}
				$ev = new PlayerInteractEntityEvent($this, $target, $item = $this->inventory->getItemInHand(), $data->getClickPosition());
				$ev->call();

				if (!$ev->isCancelled()) {
					$oldItem = clone $item;
					if (!$target->onFirstInteract($this, $ev->getItem(), $ev->getClickPosition())) {
						if ($target instanceof Living) {
							if ($this->isCreative()) {
								$item = $oldItem;
							}

							if ($item->onInteractWithEntity($this, $target)) {
								if (!$item->equalsExact($oldItem) && !$this->isCreative()) {
									$this->inventory->setItemInHand($item);
								}
							}
						}
					} elseif (!$item->equalsExact($oldItem)) {
						$this->inventory->setItemInHand($ev->getItem());
					}
				}
				return true;
			case UseItemOnEntityTransactionData::ACTION_ATTACK:
				return $this->attackEntity($target);
			default:
				break; //unknown
		}

		return false;
	}

	private function handleReleaseItemTransaction(ReleaseItemTransactionData $data) : bool
	{
		if ($this->inventory->getHeldItemSlot() !== $data->getHotbarSlot()) {
			$this->inventory->equipItem($data->getHotbarSlot(), $data->getHotbarSlot());
		}

		try {
			switch ($data->getActionType()) {
				case ReleaseItemTransactionData::ACTION_RELEASE:
					$item = $data->getItemInHand()->getItemStack();
					if ($this->releaseItem($item)) {
						$this->completeUsingItem($item, CompletedUsingItemPacket::ACTION_SHOOT);
					}
					break;
				case ReleaseItemTransactionData::ACTION_CONSUME:
					if ($this->getProtocolVersion() < ProtocolInfo::PROTOCOL_388) {
						$this->consumeItem($this->inventory->getItemInHand());
					}
					break;
				default:
					break;
			}
		} finally {
			$this->setUsingItem(false);
		}

		return true;
	}

	public function releaseItem(Item $item) : bool
	{
		if ($this->isUsingItem()) {
			if ($this->hasItemCooldown($item)) {
				$this->inventory->sendContents($this);
				return false;
			}
			$item->onReleaseUsing($this);
			$this->resetItemCooldown($item);
		} else {
			$this->inventory->sendContents($this);
			return false;
		}
		return true;
	}

	public function handleItemStackRequest(ItemStackRequestPacket $packet) : bool
	{
		$this->sendDataPacket(ItemStackResponsePacket::create([]));
		return true;
	}

	public function completeUsingItem(Item $item, int $action) : void
	{
		if ($this->getProtocolVersion() >= ProtocolInfo::PROTOCOL_388) {
			$pk = new CompletedUsingItemPacket();
			$pk->itemId = $item->getId();
			$pk->action = $action;
			$this->sendDataPacket($pk);
		}
	}

	public function handleCraftingEvent(CraftingEventPacket $packet) : bool
	{
		if ($this->spawned === false || !$this->isAlive()) {
			return true;
		}

		$input = array_map(fn (ItemStackWrapper $itemStackWrapper) => $itemStackWrapper->getItemStack(), $packet->input);
		$output = array_map(fn (ItemStackWrapper $itemStackWrapper) => $itemStackWrapper->getItemStack(), $packet->output);

		$recipes = CraftingManager::matchRecipeByOutputs($output);
		$inventory = $this->getCraftingGrid();
		if (count($input) > 0) {
			foreach ($input as $item) {
				if ($item->isNull()) {
					continue;
				} elseif ($item->getDamage() === 0x7fff) {
					$item = ItemFactory::get($item->getId(), -1, $item->getCount(), $item->getNamedTag());
				}

				if ($this->getInventory()->contains($item)) {
					$inventory->addItem($item);
					$this->getInventory()->removeItem($item);
				} else {
					$this->getServer()->getLogger()->critical("Recipe input is invalid: not founded $item in player inventory, contents: " . implode(",", $this->getInventory()->getContents()) . " for " . $this->getName());
				}
			}
		}

		$actions = [];
		$currentRecipe = null;
		foreach ($recipes as $recipe) {
			$contents = $inventory->getContents();
			$actions = [];
			$canCraft = true;

			foreach ($recipe->getIngredientList() as $ingredient) {
				$count = max(1, $ingredient->getCount());
				$damage = !$ingredient->hasAnyDamageValue();
				$namedtag = $ingredient->hasNamedTag();

				foreach ($contents as $slot => $item) {
					if ($ingredient->equals($item, $damage, $namedtag)) {
						$reduce = min($count, $item->getCount());
						$count -= $reduce;
						$newItem = (clone $item)->setCount($item->getCount() - $reduce);

						if ($newItem->getCount() === 0) {
							$newItem = ItemFactory::get(Item::AIR, 0);
						}

						$actions[] = new SlotChangeAction($inventory, $slot, $item, $newItem);
						$contents[$slot] = $newItem;
						if ($count === 0) {
							break;
						} elseif ($count < 0) {
							$this->getServer()->getLogger()->critical("Wait... this is illegally $count $reduce for " . $this->getName());
						}
					}
				}

				if ($count > 0) {
					$canCraft = false;
					break;
				}
			}

			if ($canCraft === true) {
				$currentRecipe = $recipe;
				break;
			}
		}

		if ($currentRecipe === null) {
			$this->inventory->sendContents($this);
			return true;
		}

		$baseInventory = new class ([], 36) extends BaseInventory {
			public function getName() : string
			{
				return "null";
			}

			public function getDefaultSize() : int
			{
				return 36;
			}
		};
		foreach ($output as $slot => $item) {
			$actions[] = new SlotChangeAction($baseInventory, $slot, $baseInventory->getItem($slot), $item);
		}

		if ($this->craftingTransaction === null) {
			$this->craftingTransaction = new CraftingTransaction($this, $actions);
		}

		try {
			$this->craftingTransaction->execute();
		} catch (TransactionValidationException $exception) {
			$this->server->getLogger()->debug("Failed to execute crafting transaction: " . $exception->getMessage());
			return false;
		} finally {
			$this->craftingTransaction = null;
		}

		if (count($packet->input) === 0) {
			$items = $inventory->addItem(...$baseInventory->getContents());
		} else {
			$items = $this->getInventory()->addItem(...$baseInventory->getContents());
		}

		if (count($items) > 0) {
			foreach ($items as $item) {
				$this->dropItem($item);
			}
		}

		return true;
	}

	public function consumeItem(Item $item) : bool
	{
		if ($this->isUsingItem()) {
			$this->setUsingItem(false);

			if ($item instanceof Consumable && !($item instanceof MaybeConsumable && !$item->canBeConsumed())) {
				$ev = new PlayerItemConsumeEvent($this, $item);
				if ($this->hasItemCooldown($item)) {
					$ev->setCancelled();
				}
				$ev->call();
				if ($ev->isCancelled() || !$this->consumeObject($item)) {
					$this->inventory->sendContents($this);
					$this->sendAttributes(true);
					return false;
				}

				if ($this->isSurvival()) {
					$item->pop();
					$this->inventory->setItemInHand($item);
					$this->inventory->addItem($item->getResidue());
				}

				$pk = new ActorEventPacket();
				$pk->entityRuntimeId = $this->getId();
				$pk->event = ActorEventPacket::USE_ITEM;
				$this->sendDataPacket($pk);

				$this->server->broadcastPacket($this->getViewers(), $pk);

				$this->resetItemCooldown($item);
				return true;
			}
		}

		return false;
	}

	public function handleMobEquipment(MobEquipmentPacket $packet) : bool
	{
		if (!$this->spawned || !$this->isAlive()) {
			return true;
		}

		if ($packet->windowId === ContainerIds::OFFHAND) {
			if ($this->getProtocolVersion() < ProtocolInfo::PROTOCOL_137) {
				$transaction = new InventoryTransaction($this, [
					new ContainerSlotChangeAction($this->offHandInventory, 0, $this->offHandInventory->getItem(0), $packet->item->getItemStack())
				]);
				$transaction->setSkipValidation(true);
				return $this->executeInventoryTransaction($transaction);
			}
			return true;
		}

		if ($this->getProtocolVersion() < ProtocolInfo::PROTOCOL_137) {
			//Get the index of the slot in the actual inventory
			$packet->inventorySlot -= $this->inventory->getHotbarSize();
			if ($packet->inventorySlot < 0 || $packet->inventorySlot >= $this->inventory->getSize()) {
				//Mapping was not in range of the inventory, set it to -1
				//This happens if the client selected a blank slot (sends 255)
				$packet->inventorySlot = -1;
			}

			$this->inventory->equipItem($packet->hotbarSlot, $packet->inventorySlot);
		} else {
			$this->inventory->equipItem($packet->hotbarSlot);
		}

		$this->setUsingItem(false);

		return true;
	}

	public function handleInteract(InteractPacket $packet) : bool
	{
		if (!$this->spawned || !$this->isAlive()) {
			return true;
		}
		if ($packet->action === InteractPacket::ACTION_MOUSEOVER && $packet->target === 0) {
			//TODO HACK: silence useless spam (MCPE 1.8)
			//this packet is EXPECTED to only be sent when interacting with an entity, but due to some messy Mojang
			//hacks, it also sends it when changing the held item now, which causes us to think the inventory was closed
			//when it wasn't.
			return true;
		}

		if ($this->getProtocolVersion() >= ProtocolInfo::PROTOCOL_137) {
			$this->doCloseInventory();
		}

		$target = $this->level->getEntity($packet->target);
		if ($target === null) {
			return false;
		}

		switch ($packet->action) {
			case InteractPacket::ACTION_LEAVE_VEHICLE:
				if ($this->ridingEid === $packet->target) {
					$this->dismountEntity();
				}
				break;
			case InteractPacket::ACTION_OPEN_INVENTORY:
				if ($this->getProtocolVersion() >= ProtocolInfo::PROTOCOL_407 && $target === $this) {
					$pk = new ContainerOpenPacket();
					$pk->windowId = $this->getNewWindowId();
					$pk->type = WindowTypes::INVENTORY;
					$pk->x = $pk->y = $pk->z = 0;
					$pk->entityUniqueId = $this->getId();
					$this->sendDataPacket($pk);

					$this->setCurrentWindowType(WindowTypes::INVENTORY);
					break;
				} elseif ($target instanceof InventoryHolder) {
					if (!($target instanceof AbstractHorse && !$target->isTamed())) {
						$this->setCurrentWindowType(WindowTypes::HORSE);
						$this->addWindow($target->getInventory());
						return true;
					}
				}
				return false;
			case InteractPacket::ACTION_RIGHT_CLICK:
				if ($this->getProtocolVersion() >= ProtocolInfo::PROTOCOL_137) {
					return true;
				}
				if (!$target->isAlive()) {
					return true;
				}
				$ev = new PlayerInteractEntityEvent($this, $target, $item = $this->inventory->getItemInHand(), $target->asVector3());
				$ev->call();

				if (!$ev->isCancelled()) {
					$oldItem = clone $item;
					if (!$target->onFirstInteract($this, $ev->getItem(), $ev->getClickPosition())) {
						if ($target instanceof Living) {
							if ($this->isCreative()) {
								$item = $oldItem;
							}

							if ($item->onInteractWithEntity($this, $target)) {
								if (!$item->equalsExact($oldItem) && !$this->isCreative()) {
									$this->inventory->setItemInHand($item);
								}
							}
						}
					} elseif (!$item->equalsExact($oldItem)) {
						$this->inventory->setItemInHand($ev->getItem());
					}
				}
				break;
			case InteractPacket::ACTION_LEFT_CLICK:
				if ($this->getProtocolVersion() < ProtocolInfo::PROTOCOL_137) {
					return $this->attackEntity($target);
				}

				break;
			case InteractPacket::ACTION_MOUSEOVER:
				break; //TODO: handle these
			default:
				$this->server->getLogger()->debug("Unhandled/unknown interaction type " . $packet->action . "received from " . $this->getName());

				return false;
		}

		return true;
	}

	public function handleEmote(EmotePacket $packet) : bool
	{
		if ($packet->entityRuntimeId !== $this->id) {
			return false;
		}

		$pk = new EmotePacket();
		$pk->entityRuntimeId = $this->id;
		$pk->emoteId = $packet->emoteId;
		$pk->emoteLengthTicks = $packet->emoteLengthTicks;
		$pk->xboxUserId = "";
		$pk->platformChatId = "";
		$pk->flags = EmotePacket::FLAG_SERVER | EmotePacket::FLAG_MUTE_ANNOUNCEMENT;

		$bedrockPlayers = [];
		foreach ($this->hasSpawned as $player) {
			if ($player->getProtocolVersion() >= ProtocolInfo::PROTOCOL_388) {
				$bedrockPlayers[] = $player;
			}
		}

		$this->server->broadcastPacket($bedrockPlayers, $pk);

		return true;
	}

	public function pickBlock(Vector3 $pos, bool $addTileNBT) : bool
	{
		$block = $this->level->getBlockAt($pos->x, $pos->y, $pos->z);
		if ($block instanceof UnknownBlock) {
			return true;
		}

		if (!$this->canInteract($block->add(0.5, 0.5, 0.5), 6)) {
			return false;
		}

		$item = $block->getPickedItem($addTileNBT);
		$ev = new PlayerBlockPickEvent($this, $block, $item);
		if (!$this->isCreative(true)) {
			$this->server->getLogger()->debug("Got block-pick request from " . $this->getName() . " when not in creative mode (gamemode " . $this->getGamemode() . ")");
			$ev->setCancelled();
		}

		$ev->call();
		if (!$ev->isCancelled()) {
			$this->inventory->setItemInHand($ev->getResultItem());
		}

		return true;
	}

	public function pickEntity(int $entityId) : bool
	{
		$entity = $this->level->getEntity($entityId);
		if ($entity === null) {
			return true;
		}

		$item = $entity->getPickedItem();
		if ($item === null) {
			return true;
		}

		$ev = new PlayerEntityPickEvent($this, $entity, $item);
		if (!$this->isCreative(true)) {
			$this->server->getLogger()->debug("Got block-pick request from " . $this->getName() . " when not in creative mode (gamemode " . $this->getGamemode() . ")");
			$ev->setCancelled();
		}

		$ev->call();
		if (!$ev->isCancelled()) {
			$this->inventory->setItemInHand($ev->getResultItem());
		}

		return true;
	}

	public function handlePlayerAction(PlayerActionPacket $packet) : bool
	{
		return $this->handlePlayerActionFromData($packet->action, new Vector3($packet->x, $packet->y, $packet->z), $packet->face);
	}

	public function handlePlayerActionFromData(int $action, Vector3 $pos, int $face) : bool
	{
		if (!$this->spawned || (!$this->isAlive() && $action !== PlayerActionPacket::ACTION_DIMENSION_CHANGE_REQUEST && $action !== PlayerActionPacket::ACTION_RESPAWN)) {
			return false;
		}

		switch ($action) {
			case PlayerActionPacket::ACTION_RELEASE_ITEM:
				if ($this->isSpectator()) {
					return true;
				}

				if ($this->getProtocolVersion() < ProtocolInfo::PROTOCOL_137) {
					$this->releaseItem($this->inventory->getItemInHand());
				}

				break;
			case PlayerActionPacket::ACTION_START_BREAK:
				$this->attackBlock($pos, $face);
				break;

			case PlayerActionPacket::ACTION_ABORT_BREAK:
			case PlayerActionPacket::ACTION_STOP_BREAK:
				$this->level->broadcastLevelEvent($pos, LevelEventPacket::EVENT_BLOCK_STOP_BREAK);
				break;
			case PlayerActionPacket::ACTION_START_SLEEPING:
				//unused
				break;
			case PlayerActionPacket::ACTION_STOP_SLEEPING:
				$this->stopSleep();
				break;
			case PlayerActionPacket::ACTION_DIMENSION_CHANGE_REQUEST:
			case PlayerActionPacket::ACTION_RESPAWN:
				if ($this->isAlive()) {
					break;
				}

				$this->respawn();
				break;
			case PlayerActionPacket::ACTION_JUMP:
				$this->jump();
				return true;
			case PlayerActionPacket::ACTION_START_SPRINT:
				$this->toggleSprint(true);
				return true;
			case PlayerActionPacket::ACTION_STOP_SPRINT:
				$this->toggleSprint(false);
				return true;
			case PlayerActionPacket::ACTION_START_SNEAK:
				$this->toggleSneak(true);
				return true;
			case PlayerActionPacket::ACTION_STOP_SNEAK:
				$this->toggleSneak(false);
				return true;
			case PlayerActionPacket::ACTION_START_GLIDE:
				$this->toggleGlide(true);
				break;
			case PlayerActionPacket::ACTION_STOP_GLIDE:
				$this->toggleGlide(false);
				break;
			case PlayerActionPacket::ACTION_CONTINUE_BREAK:
				$block = $this->level->getBlock($pos);
				$this->level->addParticle(new PunchBlockParticle($pos, $block, $face));

				//TODO: destroy-progress level event
				break;
			case PlayerActionPacket::ACTION_START_SWIMMING:
				if (!$this->isSwimming()) {
					$this->toggleSwim(true);
				}
				break;
			case PlayerActionPacket::ACTION_STOP_SWIMMING:
				if ($this->isSwimming()) {
					$this->toggleSwim(false);
				}
				break;
			case PlayerActionPacket::ACTION_MISSED_SWING:
				$this->missSwing();
				break;
			case PlayerActionPacket::ACTION_START_CRAWLING:
				if (!$this->isCrawling()) {
					$this->toggleCrawl(true);
				}
				break;
			case PlayerActionPacket::ACTION_STOP_CRAWLING:
				if ($this->isCrawling()) {
					$this->toggleCrawl(false);
				}
				break;
			case PlayerActionPacket::ACTION_START_FLYING:
				if (!$this->isFlying()) {
					$this->toggleFlight(true);
				}
				break;
			case PlayerActionPacket::ACTION_STOP_FLYING:
				if ($this->isFlying()) {
					$this->toggleFlight(false);
				}
				break;
			case PlayerActionPacket::ACTION_INTERACT_BLOCK: //TODO: ignored (for now)
				break;
			case PlayerActionPacket::ACTION_CREATIVE_PLAYER_DESTROY_BLOCK:
				//TODO: do we need to handle this?
				break;
			case PlayerActionPacket::ACTION_START_ITEM_USE_ON:
			case PlayerActionPacket::ACTION_STOP_ITEM_USE_ON:
				//TODO: this has no obvious use and seems only used for analytics in vanilla - ignore it
				break;
			case PlayerActionPacket::ACTION_HANDLED_TELEPORT:
			case PlayerActionPacket::ACTION_ACK_ACTOR_DATA:
			case PlayerActionPacket::ACTION_CRACK_BREAK:
			case PlayerActionPacket::ACTION_START_USING_ITEM:
				break;
			default:
				$this->server->getLogger()->debug("Unhandled/unknown player action type " . $action . " from " . $this->getName());
				return false;
		}

		$this->setUsingItem(false);

		return true;
	}

	public function toggleSprint(bool $sprint) : bool
	{
		$ev = new PlayerToggleSprintEvent($this, $sprint);
		$ev->call();
		if ($ev->isCancelled()) {
			$this->sendData($this);
			return false;
		}

		$this->setSprinting($sprint);
		return true;
	}

	public function toggleSneak(bool $sneak) : bool
	{
		$ev = new PlayerToggleSneakEvent($this, $sneak);
		$ev->call();
		if ($ev->isCancelled()) {
			$this->sendData($this);
			return false;
		}

		$this->setSneaking($sneak);
		return true;
	}

	public function toggleGlide(bool $glide) : bool
	{
		$ev = new PlayerToggleGlideEvent($this, $glide);
		$ev->call();

		if ($ev->isCancelled()) {
			$this->sendData($this);
			return false;
		}

		$this->setGliding($glide);
		return true;
	}

	public function toggleSwim(bool $swimming) : bool
	{
		$ev = new PlayerToggleSwimEvent($this, $swimming);
		$ev->call();
		if ($ev->isCancelled()) {
			$this->sendData($this);
			return false;
		}

		$this->setSwimming($swimming);
		return true;
	}

	public function toggleCrawl(bool $crawling) : bool{
		$ev = new PlayerToggleCrawlEvent($this, $crawling);
		$ev->call();
		if($ev->isCancelled()){
			$this->sendData($this);
			return false;
		}

		$this->setCrawling($crawling);
		return true;
	}

	public function handleAnimate(AnimatePacket $packet) : bool
	{
		if (!$this->spawned || !$this->isAlive()) {
			return true;
		}

		if ($packet->entityRuntimeId !== $this->getId()) {
			$this->close($this->getLeaveMessage(), "Bad packet: AnimatePacket");
			$this->getServer()->getNetwork()->blockAddress($this->getAddress());
			return false;
		}

		$ev = new PlayerAnimationEvent($this, $packet->action);
		$ev->call();
		if ($ev->isCancelled()) {
			return true;
		}

		$riding = $this->getRidingEntity();
		if ($riding instanceof Boat) {
			if ($packet->action === AnimatePacket::ACTION_ROW_RIGHT) {
				$riding->setPaddleTimeRight($packet->rowingTime);
			} elseif ($packet->action === AnimatePacket::ACTION_ROW_LEFT) {
				$riding->setPaddleTimeLeft($packet->rowingTime);
			}
		}

		$this->server->broadcastPacket($this->getViewers(), $packet);

		return true;
	}

	public function handleRespawn(RespawnPacket $packet) : bool
	{
		if (!$this->isAlive() && $packet->respawnState === RespawnPacket::CLIENT_READY_TO_SPAWN) {
			$this->sendRespawnPacket($this, RespawnPacket::READY_TO_SPAWN);

			if ($this->level->getDimension() !== $this->getSpawn()->getLevel()->getDimension()) {
				$this->respawn();
			}
			return true;
		}

		return false;
	}

	public function useItem(Vector3 $blockVector, Vector3 $clickPos, Item $item, int $face) : void
	{
		$time = time();
		if ($time !== $this->useItemLast) {
			$this->useItemCounter = 0;
		}

		if (++$this->useItemCounter > 100) {
			$this->getServer()->getNetwork()->blockAddress($this->getAddress());
			$this->useItemLast = $time;
			return;
		}

		$this->useItemLast = $time;

		if ($face >= 0 && $face <= 5) { //Use Block, place
			$this->setUsingItem(false);

			if (!$this->canInteract($blockVector->add(0.5, 0.5, 0.5), 13) || $this->isSpectator()) {
			} elseif ($this->isCreative()) {
				$item = $this->inventory->getItemInHand();
				if ($this->level->useItemOn($blockVector, $item, $face, $clickPos, $this, true)) {
					return;
				}
			} elseif (!$this->inventory->getItemInHand()->equals($item)) {
				$this->inventory->sendHeldItem($this);
			} else {
				$item = $this->inventory->getItemInHand();
				$oldItem = clone $item;
				if ($this->level->useItemOn($blockVector, $item, $face, $clickPos, $this, true)) {
					if (!$item->equalsExact($oldItem)) {
						$this->inventory->sendHeldItem($this->hasSpawned);
						$this->inventory->setItemInHand($item);
						if ($this->getProtocolVersion() < ProtocolInfo::PROTOCOL_137) {
							$this->inventory->sendContents($this);
						}
					}

					return;
				}
			}

			$this->inventory->sendHeldItem($this);

			if ($blockVector->distanceSquared($this) > 10000) {
				return;
			}

			$target = $this->level->getBlock($blockVector);
			$block = $target->getSide($face);

			$this->level->sendBlocks([$this], [$target, $block], UpdateBlockPacket::FLAG_ALL_PRIORITY);
		} elseif ($face === -1) {
			$directionVector = $this->getDirectionVector();

			if ($this->isCreative()) {
				$item = $this->inventory->getItemInHand();
			} elseif (!$this->inventory->getItemInHand()->equals($item)) {
				$this->inventory->sendHeldItem($this);
				return;
			} else {
				$item = $this->inventory->getItemInHand();
			}

			$ev = new PlayerInteractEvent($this, $item, null, $directionVector, $face, PlayerInteractEvent::RIGHT_CLICK_AIR);
			if ($this->hasItemCooldown($item)) {
				$ev->setCancelled();
			}

			$ev->call();
			if ($ev->isCancelled()) {
				if ($this->getProtocolVersion() < ProtocolInfo::PROTOCOL_137) {
					$this->inventory->sendContents($this);
					return;
				}

				$this->inventory->sendHeldItem($this);
				return;
			}

			if ($item->onClickAir($this, $directionVector)) {
				$this->resetItemCooldown($item);
				if ($this->isSurvival()) {
					$this->inventory->setItemInHand($item);
				}
			}

			$this->setUsingItem($item instanceof Releasable && $item->canStartUsingItem($this));
		}
	}

	public function handleDropItem(DropItemPacket $packet) : bool
	{
		if ($this->spawned === false || !$this->isAlive() || $this->isSpectator()) {
			return true;
		}

		$item = $packet->item->getItemStack();

		if ($item->getId() === Item::AIR) {
			// Windows 10 Edition drops the contents of the crafting grid on container close - including air.
			return true;
		}

		$isRemoveItem = true;
		if($this->craftingGrid->contains($item)){
			$this->craftingGrid->removeItem($item);
		} elseif($this->inventory->contains($item)){
			$isRemoveItem = false;
		} elseif ($this->isCreative()) {
			if (!$this->creativeInventory->contains($item)) {
				return false;
			}
		}else{
			return false;
		}

		if(!$this->dropItem($item)){
			if ($isRemoveItem) {
				$this->inventory->addItem($item);
			} else {
				$this->inventory->sendContents($this);
			}
			return true;
		}

		if (!$isRemoveItem) {
			$this->inventory->removeItem($item);
		}

		$this->inventory->sendHeldItem($this);
		$this->inventory->sendHeldItem($this->hasSpawned);
		return true;
	}

	/**
	 * Drops an item on the ground in front of the player. Returns if the item drop was successful.
	 *
	 * @return bool if the item was dropped or if the item was null
	 */
	public function dropItem(Item $item) : bool
	{
		if (!$this->spawned || !$this->isAlive()) {
			return false;
		}

		if ($item->isNull()) {
			$this->server->getLogger()->debug($this->getName() . " attempted to drop a null item (" . $item . ")");
			return true;
		}

		$ev = new PlayerDropItemEvent($this, $item);
		$ev->call();
		if($ev->isCancelled()){
			return false;
		}

		$motion = $this->getDirectionVector()->multiply(0.4);

		$this->level->dropItem($this->add(0, 1.3, 0), $item, $motion, 40);

		$this->setUsingItem(false);
		return true;
	}

	public function getCurrentWindowType() : ?int
	{
		return $this->currentWindowType;
	}

	public function getClosingWindowId() : ?int
	{
		return $this->closingWindowId;
	}

	public function setCurrentWindowType(int $windowType) : void
	{
		$this->currentWindowType = $windowType;
	}

	public function handleContainerClose(ContainerClosePacket $packet) : bool
	{
		if(!$this->spawned || ($this->getProtocolVersion() < ProtocolInfo::PROTOCOL_407 && $packet->windowId === ContainerIds::INVENTORY)){
			return true;
		}

		//since our 1.1 doesn't track whether we've removed something from the creative inventory, we have to clean everything using lame hacks
		if ($this->isCreative(true) && $this->getProtocolVersion() < ProtocolInfo::PROTOCOL_137) {
			$this->craftingGrid->clearAll();
		}

		$this->doCloseInventory();

		$windowId = $packet->windowId;

		if ($this->getProtocolVersion() >= ProtocolInfo::PROTOCOL_407) {
			//Always send this, even if no window matches. If we told the client to close a window, it will behave as if it
			//initiated the close and expect an ack.
			$pk = new ContainerClosePacket();
			$pk->windowId = $windowId;
			$pk->windowType = $this->currentWindowType;
			$pk->server = false;
			$this->sendDataPacket($pk);
		}

		if(isset($this->windowIndex[$packet->windowId])){
			$this->closingWindowId = $windowId;
			$this->removeWindow($this->windowIndex[$windowId]);
			$this->closingWindowId = null;
		}

		return true;
	}

	public function handleContainerSetSlot(ContainerSetSlotPacket $packet) : bool
	{
		if (!$this->spawned || !$this->isAlive()) {
			return true;
		}

		$slot = $packet->slot;

		if ($slot < 0) {
			return false;
		}

		switch ($packet->windowid) {
			case ContainerIds::INVENTORY:
				if ($slot >= $this->inventory->getSize()) {
					return false;
				}

				$inventory = $this->inventory;
				break;
			case ContainerIds::ARMOR:
				if ($slot >= 4) {
					return false;
				}

				$inventory = $this->armorInventory;
				break;
			case ContainerIds::HOTBAR: //Hotbar link update
				//hotbarSlot 0-8, slot 9-44
				$this->inventory->setHotbarSlotIndex($packet->hotbarSlot, $slot - 9);
				return true;
			default:
				$inventory = $this->getWindow($packet->windowid);
				if (!($inventory instanceof Inventory)) {
					return false;
				}

				if ($slot >= $inventory->getSize()) {
					return false;
				}

				break;
		}

		$transaction = new InventoryTransaction($this, [
			new ContainerSlotChangeAction($inventory, $slot, $inventory->getItem($slot), $packet->item->getItemStack())
		]);
		$transaction->setSkipValidation(true);
		return $this->executeInventoryTransaction($transaction);
	}

	public function handleAdventureSettings(AdventureSettingsPacket $packet) : bool
	{
		if ($this->getProtocolVersion() >= ProtocolInfo::PROTOCOL_527) {
			return true; //no longer used, but the client still sends it for flight changes
		}

		if ($this->getProtocolVersion() >= ProtocolInfo::PROTOCOL_137 && $packet->entityUniqueId !== $this->getId()) {
			return false; //TODO
		}

		$handled = false;

		$isFlying = $packet->getFlag(AdventureSettingsPacket::FLYING);
		if ($isFlying && !$this->allowFlight) {
			$this->kick($this->server->getLanguage()->translateString("kick.reason.cheat", ["%ability.flight"]));
			return true;
		} elseif ($isFlying !== $this->isFlying()) {
			$ev = new PlayerToggleFlightEvent($this, $isFlying);
			$ev->call();
			if ($ev->isCancelled()) {
				$this->sendAbilities();
			} else { //don't use setFlying() here, to avoid feedback loops
				$this->flying = $ev->isFlying();
				$this->resetFallDistance();
			}

			$handled = true;
		}

		if ($packet->getFlag(AdventureSettingsPacket::NO_CLIP) && !$this->allowMovementCheats && !$this->isSpectator()) {
			$this->kick($this->server->getLanguage()->translateString("kick.reason.cheat", ["%ability.noclip"]));
			return true;
		}

		//TODO: check other changes

		return $handled;
	}

	public function handleBlockEntityData(BlockActorDataPacket $packet) : bool
	{
		if (!$this->spawned || !$this->isAlive()) {
			return true;
		}

		$this->doCloseInventory();

		$pos = new Vector3($packet->x, $packet->y, $packet->z);
		if ($pos->distanceSquared($this) > 10000) {
			return true;
		}

		$t = $this->level->getTile($pos);
		if ($t instanceof Spawnable) {
			$nbt = new NetworkLittleEndianNBTStream();
			$_ = 0;
			$compound = $nbt->read($packet->namedtag, false, $_, 512);

			if (!($compound instanceof CompoundTag)) {
				return false;
			}

			if (!$t->updateCompoundTag($compound, $this)) {
				$t->spawnTo($this);
			}
		}

		return true;
	}

	public function handleSetPlayerGameType(SetPlayerGameTypePacket $packet) : bool
	{
		$gameMode = TypeConverter::getInstance()->protocolGameModeToCore($packet->gamemode);
		if ($gameMode !== $this->gamemode) {
			//Set this back to default. TODO: handle this properly
			$this->sendGamemode();
			$this->sendAbilitiesAndAdventureSettings(); //TODO: we might be able to do this with the abilities packet alone
		}
		return true;
	}

	public function handleMapInfoRequest(MapInfoRequestPacket $packet) : bool
	{
		$data = MapManager::getMapDataById($packet->mapId);
		if ($data instanceof MapData) {
			$this->sendEncoded(MapCache::getInstance($this->getMapProtocol())->getCache($data));
			return true;
		}
		return false;
	}

	public function handleItemFrameDropItem(ItemFrameDropItemPacket $packet) : bool
	{
		if (!$this->spawned || !$this->isAlive()) {
			return true;
		}

		$block = $this->level->getBlockAt($packet->x, $packet->y, $packet->z);
		if ($block instanceof ItemFrame) {
			$block->onAttack(ItemFactory::air(), 0, $this);
		}

		return true;
	}

	public function handleResourcePackChunkRequest(ResourcePackChunkRequestPacket $packet) : bool
	{
		if ($this->resourcePacksDone) {
			return false;
		}
		$manager = $this->server->getResourcePackManager($this->getProtocolVersion());
		$pack = $manager->getPackById($packet->packId);
		if (!($pack instanceof ResourcePack)) {
			$this->close("", "disconnectionScreen.resourcePack", true);
			$this->server->getLogger()->debug("Got a resource pack chunk request for unknown pack with UUID " . $packet->packId . ", available packs: " . implode(", ", $manager->getPackIdList()));

			return false;
		}

		$packId = $pack->getPackId();

		if (isset($this->downloadedChunks[$packId][$packet->chunkIndex])) {
			$this->close("", "disconnectionScreen.resourcePack", true);
			$this->server->getLogger()->debug("Duplicate request for chunk $packet->chunkIndex of pack $packet->packId");

			return false;
		}

		$offset = $packet->chunkIndex * self::RESOURCE_PACK_CHUNK_SIZE;
		if ($offset < 0 || $offset >= $pack->getPackSize()) {
			$this->close("", "disconnectionScreen.resourcePack", true);
			$this->server->getLogger()->debug("Invalid out-of-bounds request for chunk $packet->chunkIndex of $packet->packId: offset $offset, file size " . $pack->getPackSize());

			return false;
		}

		if (!isset($this->downloadedChunks[$packId])) {
			$this->downloadedChunks[$packId] = [$packet->chunkIndex => true];
		} else {
			$this->downloadedChunks[$packId][$packet->chunkIndex] = true;
		}

		$pk = new ResourcePackChunkDataPacket();
		$pk->packId = $pack->getPackId();
		$pk->chunkIndex = $packet->chunkIndex;
		$pk->data = $pack->getPackChunk($offset, self::RESOURCE_PACK_CHUNK_SIZE);
		$pk->progress = $offset;
		$this->dataPacket($pk);
		return true;
	}

	public function handleBookEdit(BookEditPacket $packet) : bool
	{
		if ($this->getProtocolVersion() <= ProtocolInfo::PROTOCOL_201) {
			$packet->inventorySlot -= 9;
		}

		/** @var WritableBook $oldBook */
		$oldBook = $this->inventory->getItem($packet->inventorySlot);
		if ($oldBook->getId() !== Item::WRITABLE_BOOK) {
			return false;
		}

		$newBook = clone $oldBook;
		$modifiedPages = [];

		switch ($packet->type) {
			case BookEditPacket::TYPE_REPLACE_PAGE:
				$newBook->setPageText($packet->pageNumber, $packet->text);
				$modifiedPages[] = $packet->pageNumber;
				break;
			case BookEditPacket::TYPE_ADD_PAGE:
				if (!$newBook->pageExists($packet->pageNumber)) {
					//this may only come before a page which already exists
					//TODO: the client can send insert-before actions on trailing client-side pages which cause odd behaviour on the server
					return false;
				}
				$newBook->insertPage($packet->pageNumber, $packet->text);
				$modifiedPages[] = $packet->pageNumber;
				break;
			case BookEditPacket::TYPE_DELETE_PAGE:
				if (!$newBook->pageExists($packet->pageNumber)) {
					return false;
				}
				$newBook->deletePage($packet->pageNumber);
				$modifiedPages[] = $packet->pageNumber;
				break;
			case BookEditPacket::TYPE_SWAP_PAGES:
				if (!$newBook->pageExists($packet->pageNumber) || !$newBook->pageExists($packet->secondaryPageNumber)) {
					//the client will create pages on its own without telling us until it tries to switch them
					$newBook->addPage(max($packet->pageNumber, $packet->secondaryPageNumber));
				}
				$newBook->swapPages($packet->pageNumber, $packet->secondaryPageNumber);
				$modifiedPages = [$packet->pageNumber, $packet->secondaryPageNumber];
				break;
			case BookEditPacket::TYPE_SIGN_BOOK:
				/** @var WrittenBook $newBook */
				$newBook = Item::get(Item::WRITTEN_BOOK, 0, 1, $newBook->getNamedTag());
				$newBook->setAuthor($packet->author);
				$newBook->setTitle($packet->title);
				$newBook->setGeneration(WrittenBook::GENERATION_ORIGINAL);
				break;
			default:
				return false;
		}

		$event = new PlayerEditBookEvent($this, $oldBook, $newBook, $packet->type, $modifiedPages);
		$event->call();
		if ($event->isCancelled()) {
			return true;
		}

		$this->getInventory()->setItem($packet->inventorySlot, $event->getNewBook());

		return true;
	}

	public function handleEncoded(string $payload) : void
	{
		if (!$this->connected) {
			return;
		}

		Timings::$playerNetworkReceive->startTiming();
		try {
			$this->packetBatchLimiter->decrement();

			if ($this->cipher !== null) {
				Timings::$playerNetworkReceiveDecrypt->startTiming();
				try {
					$payload = $this->cipher->decrypt($payload);
				} catch (DecryptionException $e) {
					$this->server->getLogger()->debug($this->getName() . " Encrypted packet: " . base64_encode($payload));
					throw PacketHandlingException::wrap($e, "Packet decryption error");
				} finally {
					Timings::$playerNetworkReceiveDecrypt->stopTiming();
				}
			}

			if (strlen($payload) < 1) {
				throw new PacketHandlingException("No bytes in payload");
			}

			if ($this->enableCompression) {
				Timings::$playerNetworkReceiveDecompress->startTiming();

				try {
					$compressionType = ord($payload[0]);
					if ($this->getProtocolVersion() >= ProtocolInfo::PROTOCOL_975) {
						$this->server->getLogger()->warning("PROTOCOL975_DEBUG type=" . $compressionType . " payloadFull=" . bin2hex($payload) . " len=" . strlen($payload));
					}
					if ($compressionType == CompressionAlgorithm::NONE) {
						$compressed = substr($payload, 1);
						$decompressed = $compressed;
					} elseif ($compressionType == CompressionAlgorithm::ZLIB) {
						$compressed = substr($payload, 1);
						$decompressed = NetworkCompression::decompress($compressed);
					} else {
						$decompressed = NetworkCompression::decompress($payload);
					}
				} catch (DecompressionException $e) {
					$this->server->getLogger()->debug($this->getName() . " Failed to decompress packet: " . base64_encode($payload));
					throw PacketHandlingException::wrap($e, "Compressed packet batch decode error");
				}

				if ($this->getProtocolVersion() >= ProtocolInfo::PROTOCOL_975) {
					$this->server->getLogger()->warning("PROTOCOL975_DEBUG_DECOMPRESSED len=" . strlen($decompressed) . " hex=" . bin2hex($decompressed));
				}

				Timings::$playerNetworkReceiveDecompress->stopTiming();
			} else {
				$decompressed = $payload;
			}

			try{
				$stream = new BinaryStream($decompressed);
				foreach(PacketBatch::decodeRaw($stream) as $buffer){
					$packet = PacketPool::getPacket($buffer, $this->getProtocolVersion());
					if($packet instanceof UnknownPacket){
						$this->server->getLogger()->debug($this->getName() . " Unknown packet: " . base64_encode($buffer));
						throw new PacketHandlingException("Unknown packet received");
					}

					$packet->setProtocol($this->getProtocolVersion());
					$this->traceProtocol975("IN", $packet, strlen($buffer));

					try{
						$this->handleDataPacket($packet);
					}catch(PacketHandlingException $e){
						$this->server->getLogger()->debug($this->getName() . " " . $packet->getName() . ": " . base64_encode($buffer));
						throw PacketHandlingException::wrap($e, "Error processing " . $packet->getName());
					}
				}
			}catch(PacketDecodeException|BinaryDataException $e){
				$this->server->getLogger()->debug($this->getName() . " " . $e);
				throw PacketHandlingException::wrap($e, "Packet batch decode error");
			}
		} finally {
			Timings::$playerNetworkReceive->stopTiming();
		}
	}

	/**
	 * Called when a packet is received from the client. This method will call DataPacketReceiveEvent.
	 */
	public function handleDataPacket(DataPacket $packet) : void
	{
		if ($this->sessionAdapter !== null) {
			$this->sessionAdapter->handleDataPacket($packet);
		}
	}

	/**
	 * @return bool|int
	 */
	public function sendDataPacket(DataPacket $packet, bool $needACK = false, bool $immediate = false)
	{
		if (!$this->connected) {
			return false;
		}

		if ($packet instanceof BatchPacket) {
			return true;
		}

		//Basic safety restriction. TODO: improve this
		if (!$this->loggedIn && !$packet->canBeSentBeforeLogin()) {
			$this->server->getLogger()->debug("Attempted to send " . get_class($packet) . " to " . $this->getName() . " too early");
			return false;
		}

		$timings = Timings::getSendDataPacketTimings($packet);
		$timings->startTiming();
		try {
			//due to plugins there were client crashes
			$packet = clone $packet;

			$packet->setProtocol($this->protocolVersion);

			$ev = new DataPacketSendEvent($this, $packet);
			$ev->call();
			if ($ev->isCancelled()) {
				return false;
			}

			if (PacketIdTranslator::getInstance()->toNetworkId($this->getProtocolVersion(), $packet->pid()) === null) {
				return false;
			}

			if ($this->getProtocolVersion() >= ProtocolInfo::PROTOCOL_975 && (
				$packet instanceof PlayerListPacket ||
				$packet instanceof AvailableActorIdentifiersPacket ||
				$packet instanceof BiomeDefinitionListPacket ||
				$packet instanceof AvailableCommandsPacket
			)) {
				$this->traceProtocol975("SKIP", $packet, 0);
				return false;
			}

			$buffer = self::encodePacketTimed($packet);
			$this->traceProtocol975("OUT", $packet, strlen($buffer));
			$this->addToSendBuffer($buffer);

			if ($immediate) {
				$this->flushSendBuffer(true);
			}

			return true;
		} finally {
			$timings->stopTiming();
		}
	}

	public function queueEncoded(string $data) : bool
	{
		if (!$this->connected) {
			return false;
		}

		if ($this->getProtocolVersion() >= ProtocolInfo::PROTOCOL_975) {
			$this->server->getLogger()->info($this->getProtocolTracePrefix("RAW") . " queued encoded payload bytes=" . strlen($data));
			$this->server->getLogger()->info($this->getProtocolTracePrefix("SKIP") . " raw encoded payload bytes=" . strlen($data));
			return true;
		}

		$payload = new CompressBatchPromise();
		$payload->resolve($data);
		$this->queueCompressed($payload);

		return true;
	}

	public function queueCompressed(CompressBatchPromise|string $payload, bool $immediate = false) : void
	{
		$this->flushSendBuffer($immediate); //Maintain ordering if possible
		$this->queueCompressedNoBufferFlush($payload, $immediate);
	}

	protected function queueCompressedNoBufferFlush(CompressBatchPromise|string $payload, bool $immediate = false) : void
	{
		if (is_string($payload)) {
			if ($immediate) {
				//Skips all queues
				$this->sendEncoded($payload, true);
			} else {
				$this->batchQueue->enqueue($payload);
				$this->flushCompressedQueue();
			}
		} elseif ($immediate) {
			//Skips all queues
			$this->sendEncoded($payload->getResult(), true);
		} else {
			$this->batchQueue->enqueue($payload);
			$payload->onResolve(function () : void {
				if ($this->connected) {
					$this->flushCompressedQueue();
				}
			});
		}
	}

	private function flushCompressedQueue() : void
	{
		Timings::$playerNetworkSend->startTiming();
		try {
			while (!$this->batchQueue->isEmpty()) {
				/** @var CompressBatchPromise|string $current */
				$current = $this->batchQueue->bottom();
				if (is_string($current)) {
					$this->batchQueue->dequeue();
					$this->sendEncoded($current);

				} elseif ($current->hasResult()) {
					$this->batchQueue->dequeue();
					$this->sendEncoded($current->getResult());

				} else {
					//can't send any more queued until this one is ready
					break;
				}
			}
		} finally {
			Timings::$playerNetworkSend->stopTiming();
		}
	}

	/**
	 * @internal
	 */
	public function addToSendBuffer(string $buffer) : void
	{
		$this->sendBuffer[] = $buffer;
	}

	public static function encodePacketTimed(DataPacket $packet) : string{
		$timings = Timings::getEncodeDataPacketTimings($packet);
		$timings->startTiming();
		try{
			$packet->encode();
			return $packet->getBuffer();
		}finally{
			$timings->stopTiming();
		}
	}

	public function flushSendBuffer(bool $immediate = false) : void
	{
		if (count($this->sendBuffer) > 0) {
			$packetCount = count($this->sendBuffer);
			$syncMode = null; //automatic
			if ($immediate) {
				$syncMode = true;
			} elseif ($this->forceAsyncCompression) {
				$syncMode = false;
			}

			$stream = new BinaryStream();
			PacketBatch::encodeRaw($stream, $this->sendBuffer);
			$rawBatchSize = strlen($stream->getBuffer());

			if ($this->enableCompression) {
				$buffer = $this->server->prepareBatch($stream->getBuffer(), $this->getProtocolVersion(), $syncMode);
			} else {
				$buffer = $stream->getBuffer();
			}

			if ($this->getProtocolVersion() >= ProtocolInfo::PROTOCOL_975 && $rawBatchSize > 100000) {
				$queuedBytes = is_string($buffer) ? (string) strlen($buffer) : ($buffer->hasResult() ? (string) strlen($buffer->getResult()) : "pending");
				$this->server->getLogger()->info($this->getProtocolTracePrefix("BATCH") . " packets=" . $packetCount . " rawBytes=" . $rawBatchSize . " queuedBytes=" . $queuedBytes . " immediate=" . ($immediate ? "yes" : "no"));
			}

			$this->sendBuffer = [];
			$this->queueCompressedNoBufferFlush($buffer, $immediate);
		}
	}

	protected function sendEncoded(string $payload, bool $immediate = false) : void
	{
		if (!$this->connected) {
			return;
		}

		$plainSize = strlen($payload);
		if ($this->getProtocolVersion() >= ProtocolInfo::PROTOCOL_649) {
			$payload = chr(CompressionAlgorithm::ZLIB) . $payload;
		}

		if ($this->cipher !== null) {
			Timings::$playerNetworkSendEncrypt->startTiming();
			$payload = $this->cipher->encrypt($payload);
			Timings::$playerNetworkSendEncrypt->stopTiming();
		}

		if ($this->getProtocolVersion() >= ProtocolInfo::PROTOCOL_975 && $plainSize > 100000) {
			$this->server->getLogger()->info($this->getProtocolTracePrefix("SEND") . " plainBytes=" . $plainSize . " wireBytes=" . strlen($payload) . " immediate=" . ($immediate ? "yes" : "no"));
		}

		$this->sender->putBuffer($this->sessionId, $payload, false, $immediate);
	}

	private function traceProtocol975(string $direction, DataPacket $packet, int $bytes) : void
	{
		if ($this->getProtocolVersion() < ProtocolInfo::PROTOCOL_975) {
			return;
		}
		if ($direction === "IN" && $packet->pid() === ProtocolInfo::PLAYER_AUTH_INPUT_PACKET) {
			return;
		}

		$networkId = PacketIdTranslator::getInstance()->toNetworkId($this->getProtocolVersion(), $packet->pid());
		$counter = $direction === "IN" ? ++$this->protocolTraceIn : ++$this->protocolTraceOut;
		$this->server->getLogger()->info($this->getProtocolTracePrefix($direction) . " #" . $counter . " " . $packet->getName() . " core=0x" . dechex($packet->pid()) . " net=" . ($networkId === null ? "none" : "0x" . dechex($networkId)) . " bytes=" . $bytes);
	}

	private function getProtocolTracePrefix(string $direction) : string
	{
		$name = $this->username !== "" ? $this->username : $this->getName();
		return "975 TRACE " . $direction . " [" . $name . "/" . $this->getAddress() . ":" . $this->getPort() . "]";
	}

	/**
	 * @deprecated
	 */
	public function batchDataPacket(DataPacket $packet) : bool
	{
		return $this->sendDataPacket($packet);
	}

	/**
	 * @internal
	 */
	public function getCipher() : ?EncryptionContext
	{
		return $this->cipher;
	}

	/**
	 * @return bool|int
	 */
	public function dataPacket(DataPacket $packet, bool $needACK = false)
	{
		return $this->sendDataPacket($packet, $needACK, false);
	}

	/**
	 * @return bool|int
	 */
	public function directDataPacket(DataPacket $packet, bool $needACK = false)
	{
		return $this->sendDataPacket($packet, $needACK, true);
	}

	/**
	 * Transfers a player to another server.
	 *
	 * @param string $address The IP address or hostname of the destination server
	 * @param int    $port    The destination port, defaults to 19132
	 * @param string $message Message to show in the console when closing the player
	 *
	 * @return bool if transfer was successful.
	 */
	public function transfer(string $address, int $port = 19132, string $message = "transfer") : bool
	{
		$ev = new PlayerTransferEvent($this, $address, $port, $message);
		$ev->call();
		if (!$ev->isCancelled()) {
			$pk = new TransferPacket();
			$pk->address = $ev->getAddress();
			$pk->port = $ev->getPort();
			$this->directDataPacket($pk);
			$this->close("", $ev->getMessage(), false);

			return true;
		}

		return false;
	}

	/**
	 * Kicks a player from the server
	 *
	 * @param TextContainer|string $quitMessage
	 */
	public function kick(string $reason = "", bool $isAdmin = true, $quitMessage = null) : bool
	{
		$ev = new PlayerKickEvent($this, $reason, $quitMessage ?? $this->getLeaveMessage());
		$ev->call();
		if (!$ev->isCancelled()) {
			$reason = $ev->getReason();
			$message = $reason;
			if ($isAdmin) {
				if (!$this->isBanned()) {
					$message = "Kicked by admin." . ($reason !== "" ? " Reason: " . $reason : "");
				}
			} else {
				if ($reason === "") {
					$message = "disconnectionScreen.noReason";
				}
			}
			$this->close($ev->getQuitMessage(), $message);

			return true;
		}

		return false;
	}

	/**
	 * @param int $fadeIn  Duration in ticks for fade-in. If -1 is given, client-sided defaults will be used.
	 * @param int $stay    Duration in ticks to stay on screen for
	 * @param int $fadeOut Duration in ticks for fade-out.
	 *
	 * @return void
	 * @deprecated
	 * @see Player::sendTitle()
	 */
	public function addTitle(string $title, string $subtitle = "", int $fadeIn = -1, int $stay = -1, int $fadeOut = -1)
	{
		$this->sendTitle($title, $subtitle, $fadeIn, $stay, $fadeOut);
	}

	/**
	 * Adds a title text to the user's screen, with an optional subtitle.
	 *
	 * @param int $fadeIn  Duration in ticks for fade-in. If -1 is given, client-sided defaults will be used.
	 * @param int $stay    Duration in ticks to stay on screen for
	 * @param int $fadeOut Duration in ticks for fade-out.
	 */
	public function sendTitle(string $title, string $subtitle = "", int $fadeIn = -1, int $stay = -1, int $fadeOut = -1) : void
	{
		$this->setTitleDuration($fadeIn, $stay, $fadeOut);
		if ($subtitle !== "") {
			$this->sendSubTitle($subtitle);
		}
		$this->sendTitleText($title, SetTitlePacket::TYPE_SET_TITLE);
	}

	/**
	 * @return void
	 * @see Player::sendSubTitle()
	 *
	 * @deprecated
	 */
	public function addSubTitle(string $subtitle)
	{
		$this->sendSubTitle($subtitle);
	}

	/**
	 * Sets the subtitle message, without sending a title.
	 */
	public function sendSubTitle(string $subtitle) : void
	{
		$this->sendTitleText($subtitle, SetTitlePacket::TYPE_SET_SUBTITLE);
	}

	/**
	 * @return void
	 * @see Player::sendActionBarMessage()
	 *
	 * @deprecated
	 */
	public function addActionBarMessage(string $message)
	{
		$this->sendActionBarMessage($message);
	}

	/**
	 * Adds small text to the user's screen.
	 */
	public function sendActionBarMessage(string $message) : void
	{
		$this->sendTitleText($message, SetTitlePacket::TYPE_SET_ACTIONBAR_MESSAGE);
	}

	/**
	 * Removes the title from the client's screen.
	 *
	 * @return void
	 */
	public function removeTitles()
	{
		$pk = new SetTitlePacket();
		$pk->type = SetTitlePacket::TYPE_CLEAR_TITLE;
		$this->dataPacket($pk);
	}

	/**
	 * Resets the title duration settings to defaults and removes any existing titles.
	 *
	 * @return void
	 */
	public function resetTitles()
	{
		$pk = new SetTitlePacket();
		$pk->type = SetTitlePacket::TYPE_RESET_TITLE;
		$this->dataPacket($pk);
	}

	/**
	 * Sets the title duration.
	 *
	 * @param int $fadeIn  Title fade-in time in ticks.
	 * @param int $stay    Title stay time in ticks.
	 * @param int $fadeOut Title fade-out time in ticks.
	 *
	 * @return void
	 */
	public function setTitleDuration(int $fadeIn, int $stay, int $fadeOut)
	{
		if ($fadeIn >= 0 && $stay >= 0 && $fadeOut >= 0) {
			$pk = new SetTitlePacket();
			$pk->type = SetTitlePacket::TYPE_SET_ANIMATION_TIMES;
			$pk->fadeInTime = $fadeIn;
			$pk->stayTime = $stay;
			$pk->fadeOutTime = $fadeOut;
			$this->dataPacket($pk);
		}
	}

	/**
	 * Internal function used for sending titles.
	 *
	 * @return void
	 */
	protected function sendTitleText(string $title, int $type)
	{
		$pk = new SetTitlePacket();
		$pk->type = $type;
		$pk->text = $title;
		$this->dataPacket($pk);
	}

	/**
	 * Sends a direct chat message to a player
	 *
	 * @param TextContainer|string $message
	 *
	 * @return void
	 */
	public function sendMessage($message)
	{
		if ($message instanceof TextContainer) {
			if ($message instanceof TranslationContainer) {
				$this->sendTranslation($message->getText(), $message->getParameters());
				return;
			}
			$message = $message->getText();
		}

		$pk = new TextPacket();
		$pk->type = TextPacket::TYPE_RAW;
		$pk->message = $this->server->getLanguage()->translateString($message);
		$this->dataPacket($pk);
	}

	public function sendMessagef(string $format, mixed ...$args) : void{
		$this->sendMessage(sprintf($format, ...$args));
	}

	/**
	 * @param string[] $parameters
	 *
	 * @return void
	 */
	public function sendTranslation(string $message, array $parameters = [])
	{
		$pk = new TextPacket();
		if (!$this->server->isLanguageForced()) {
			$pk->type = TextPacket::TYPE_TRANSLATION;
			$pk->needsTranslation = true;
			$pk->message = $this->server->getLanguage()->translateString($message, $parameters, "pocketmine.");
			foreach ($parameters as $i => $p) {
				$parameters[$i] = $this->server->getLanguage()->translateString($p, [], "pocketmine.");
			}
			$pk->parameters = $parameters;
		} else {
			$pk->type = TextPacket::TYPE_RAW;
			$pk->message = $this->server->getLanguage()->translateString($message, $parameters);
		}
		$this->dataPacket($pk);
	}

	/**
	 * Sends a popup message to the player
	 *
	 * TODO: add translation type popups
	 *
	 * @return void
	 */
	public function sendPopup(string $message, string $subtitle = "")
	{
		$pk = new TextPacket();
		$pk->type = TextPacket::TYPE_POPUP;
		if ($this->getProtocolVersion() < ProtocolInfo::PROTOCOL_137) {
			$pk->message = $subtitle;
			$pk->sourceName = $message;
		} else {
			$pk->message = $message;
		}
		$this->dataPacket($pk);
	}

	/**
	 * @return void
	 */
	public function sendTip(string $message)
	{
		$pk = new TextPacket();
		$pk->type = TextPacket::TYPE_TIP;
		$pk->message = $message;
		$this->dataPacket($pk);
	}

	/**
	 * @return void
	 */
	public function sendWhisper(string $sender, string $message)
	{
		$pk = new TextPacket();
		$pk->type = TextPacket::TYPE_WHISPER;
		$pk->sourceName = $sender;
		$pk->message = $message;
		$this->dataPacket($pk);
	}

	/**
	 * Sends Toast Notification to player.
	 */
	public function sendToast(string $title, string $content) : void
	{
		$pk = new ToastRequestPacket();
		$pk->title = $title;
		$pk->content = $content;
		$this->dataPacket($pk);
	}

	/**
	 * Sends a Form to the player, or queue to send it if a form is already open.
	 */
	public function sendForm(Form $form) : void
	{
		$formData = json_encode($form);
		if ($formData === false) {
			$this->server->getLogger()->debug("Failed to encode form JSON: " . json_last_error_msg());
			return;
		}
		$id = $this->formIdCounter++;
		$pk = new ModalFormRequestPacket();
		$pk->formId = $id;
		$pk->formData = $formData;
		if ($this->dataPacket($pk) !== false) {
			$this->forms[$id] = $form;
		}
	}

	/**
	 * @param mixed $responseData
	 */
	public function onFormSubmit(int $formId, $responseData) : bool
	{
		if (!isset($this->forms[$formId])) {
			$this->server->getLogger()->debug("Got unexpected response for form $formId");
			return false;
		}

		try {
			$this->forms[$formId]->handleResponse($this, $responseData);
		} catch (FormValidationException $e) {
			$this->server->getLogger()->critical("Failed to validate form " . get_class($this->forms[$formId]) . ": " . $e->getMessage());
			$this->server->getLogger()->logException($e);
		} finally {
			unset($this->forms[$formId]);
		}

		return true;
	}

	/**
	 * Closes any forms that the player currently has open
	 */
	public function closeAllForms() : void
	{
		$this->sendDataPacket(ClientboundCloseFormPacket::create());
	}

	/**
	 * Note for plugin developers: use kick() with the isAdmin
	 * flag set to kick without the "Kicked by admin" part instead of this method.
	 *
	 * @param TextContainer|string $message Message to be broadcasted
	 * @param string               $reason  Reason showed in console
	 */
	final public function close($message = "", string $reason = "generic reason", bool $notify = true) : void
	{
		if ($this->connected && !$this->closed) {
			if ($notify && strlen($reason) > 0) {
				$pk = new DisconnectPacket();
				$pk->reason = 0;
				$pk->message = $reason;
				$this->sendDataPacket($pk, false, true);
			}
			$this->sender->close($this->sessionId, $notify ? $reason : "");

			$this->sessionAdapter = null;
			$this->connected = false;

			PermissionManager::getInstance()->unsubscribeFromPermission(Server::BROADCAST_CHANNEL_USERS, $this);
			PermissionManager::getInstance()->unsubscribeFromPermission(Server::BROADCAST_CHANNEL_ADMINISTRATIVE, $this);

			$this->stopSleep();

			if ($this->joined) {
				$this->doCloseInventory();

				$ev = new PlayerQuitEvent($this, $message, $reason);
				$ev->call();
				if ($ev->getQuitMessage() != "") {
					$this->server->broadcastMessage($ev->getQuitMessage());
				}

				$this->save();
			}
			$this->joined = false;

			if ($this->isValid()) {
				foreach ($this->usedChunks as $index => $d) {
					Level::getXZ($index, $chunkX, $chunkZ);
					$this->level->unregisterChunkLoader($this, $chunkX, $chunkZ);
					foreach ($this->level->getChunkEntities($chunkX, $chunkZ) as $entity) {
						$entity->despawnFrom($this);
					}
					unset($this->usedChunks[$index]);
				}
			}
			$this->usedChunks = [];
			$this->loadQueue = [];

			if ($this->loggedIn) {
				$this->server->onPlayerLogout($this);

				foreach ($this->server->getOnlinePlayers() as $player) {
					if (!$player->canSee($this)) {
						$player->showPlayer($this);
					}
				}
				$this->hiddenPlayers = [];
			}

			$this->removeAllWindows(true);
			$this->windows = [];
			$this->windowIndex = [];

			if ($this->constructed) {
				parent::close();
			}
			$this->spawned = false;

			if ($this->loggedIn) {
				$this->loggedIn = false;
				$this->server->removeOnlinePlayer($this);
			}

			$this->server->removePlayer($this);

			$this->server->getLogger()->info($this->getServer()->getLanguage()->translateString("pocketmine.player.logOut", [
				TextFormat::AQUA . $this->getName() . TextFormat::WHITE,
				$this->ip,
				$this->port,
				$this->getServer()->getLanguage()->translateString($reason),
				$this->vanillaVersion,
				$this->getProtocolVersion()
			]));

			$this->spawnPosition = null;
			$this->deathPosition = null;

			if ($this->perm !== null) {
				$this->perm->clearPermissions();
				$this->perm = null;
			}
		}
	}

	/**
	 * @return mixed[]
	 */
	public function __debugInfo()
	{
		return [];
	}

	public function canSaveWithChunk() : bool
	{
		return false;
	}

	public function setCanSaveWithChunk(bool $value) : void
	{
		throw new BadMethodCallException("Players can't be saved with chunks");
	}

	/**
	 * Handles player data saving
	 *
	 * @return void
	 */
	public function save()
	{
		if ($this->closed) {
			return;
		}

		parent::saveNBT();

		if ($this->isValid()) {
			$this->namedtag->setString("Level", $this->level->getFolderName());
		}

		if ($this->hasValidSpawnPosition()) {
			$this->namedtag->setString("SpawnLevel", $this->spawnPosition->getLevel()->getFolderName());
			$this->namedtag->setInt("SpawnX", $this->spawnPosition->getFloorX());
			$this->namedtag->setInt("SpawnY", $this->spawnPosition->getFloorY());
			$this->namedtag->setInt("SpawnZ", $this->spawnPosition->getFloorZ());

			if (!$this->isAlive()) {
				//hack for respawn after quit
				$this->namedtag->setTag(new ListTag("Pos", [
					new DoubleTag("", $this->spawnPosition->x),
					new DoubleTag("", $this->spawnPosition->y),
					new DoubleTag("", $this->spawnPosition->z)
				]));
			}
		}

		if ($this->deathPosition !== null && $this->deathPosition->isValid()) {
			$this->namedtag->setString("DeathLevel", $this->deathPosition->getLevel()->getFolderName());
			$this->namedtag->setInt("DeathPositionX", $this->deathPosition->getFloorX());
			$this->namedtag->setInt("DeathPositionY", $this->deathPosition->getFloorY());
			$this->namedtag->setInt("DeathPositionZ", $this->deathPosition->getFloorZ());
		}

		$this->namedtag->setInt("playerGameType", $this->gamemode);
		$this->namedtag->setLong("lastPlayed", (int) floor(microtime(true) * 1000));

		if ($this->username != "" && $this->namedtag instanceof CompoundTag) {
			$this->server->saveOfflinePlayerData($this->username, $this->namedtag);
		}
	}

	public function kill() : void
	{
		if (!$this->spawned) {
			return;
		}

		parent::kill();

		$this->sendRespawnPacket($this->getSpawn());
	}

	protected function onDeath() : void
	{
		//Crafting grid must always be evacuated even if keep-inventory is true. This dumps the contents into the
		//main inventory and drops the rest on the ground.
		$this->doCloseInventory();

		$this->setDeathPosition($this->getPosition());

		$ev = new PlayerDeathEvent($this, $this->getDrops(), null, $this->getXpDropAmount());
		$ev->setKeepInventory($this->server->keepInventory || $this->level->getGameRules()->getBool(GameRules::RULE_KEEP_INVENTORY));
		$ev->setKeepExperience($this->server->keepExperience);
		$ev->call();

		$this->keepExperience = $ev->getKeepExperience();

		if (!$ev->getKeepInventory()) {
			foreach ($ev->getDrops() as $item) {
				$this->level->dropItem($this, $item);
			}

			if ($this->inventory !== null) {
				$this->inventory->setHeldItemIndex(0);
				$this->inventory->clearAll();
			}
			if ($this->armorInventory !== null) {
				$this->armorInventory->clearAll();
			}
			if ($this->uiInventory !== null) {
				$this->uiInventory->clearAll();
			}
			if ($this->offHandInventory !== null) {
				$this->offHandInventory->clearAll();
			}
		}

		if (!$ev->getKeepExperience()) {
			$this->level->dropExperience($this, $ev->getXpDropAmount());
			$this->setXpAndProgress(0, 0);
		}

		$pk = DeathInfoPacket::create((string) $ev->getDeathMessage(), PlayerDeathEvent::deriveMessage($this->getDisplayName(), $this->getLastDamageCause())->getParameters());
		$this->sendDataPacket($pk);
		if ($ev->getDeathMessage() != "") {
			$this->server->broadcastMessage($ev->getDeathMessage());
		}
	}

	protected function onDeathUpdate(int $tickDiff) : bool
	{
		parent::onDeathUpdate($tickDiff);
		return false; //never flag players for despawn
	}

	protected function respawn() : void
	{
		if ($this->server->isHardcore()) {
			if ($this->kick("You have been banned because you died in hardcore mode", false)) { //this allows plugins to prevent the ban by cancelling PlayerKickEvent
				$this->server->getNameBans()->addBan($this->getName(), "Died in hardcore mode");
			}
			return;
		}

		$this->actuallyRespawn();
	}

	protected function actuallyRespawn() : void
	{
		if ($this->respawnLocked) {
			return;
		}
		$this->respawnLocked = true;

		$this->server->getLogger()->debug("Waiting for safe respawn position to be located for " . $this->getName());
		$spawn = $this->getSpawn();

		$this->server->getLogger()->debug("Respawn position located, completing respawnfor " . $this->getName());

		$ev = new PlayerRespawnEvent($this, $spawn);
		$ev->call();

		$realSpawn = Position::fromObject($ev->getRespawnPosition()->add(0.5, 0, 0.5), $ev->getRespawnPosition()->getLevel());
		$this->teleport($realSpawn);

		$this->setSprinting(false);
		$this->setSneaking(false);

		$this->extinguish();
		$this->setAirSupplyTicks($this->getMaxAirSupplyTicks());
		$this->deadTicks = 0;
		$this->noDamageTicks = 60;

		$this->removeAllEffects();
		$this->setHealth($this->getMaxHealth());

		foreach ($this->attributeMap->getAll() as $attr) {
			if ($this->keepExperience && ($attr->getId() === Attribute::EXPERIENCE || $attr->getId() === Attribute::EXPERIENCE_LEVEL)) {
				continue;
			}
			$attr->resetToDefault();
		}

		$this->sendData($this);
		$this->sendData($this->getViewers());
		$this->sendAttributes(true);

		$this->sendAbilities();
		$this->sendAllInventories();

		$this->spawnToAll();
		$this->scheduleUpdate();

		$this->respawnLocked = false;
	}

	protected function applyPostDamageEffects(EntityDamageEvent $source) : void
	{
		parent::applyPostDamageEffects($source);

		foreach (($item = $this->getInventory()->getItemInHand())->getEnchantments() as $enchantmentInstance) {
			$enchantmentInstance->getType()->onHurtEntity($this, $source->getEntity(), $item, $enchantmentInstance->getLevel());
		}

		$this->getInventory()->setItemInHand($item);

		$this->exhaust(0.3, PlayerExhaustEvent::CAUSE_DAMAGE);
	}

	public function attack(EntityDamageEvent $source) : void
	{
		if (!$this->isAlive()) {
			return;
		}

		if ($this->isCreative()
			&& $source->getCause() !== EntityDamageEvent::CAUSE_SUICIDE
		) {
			$source->setCancelled();
		} elseif (($this->isCreative() || $this->isSpectator()) && $source->getCause() === EntityDamageEvent::CAUSE_FALL) {
			$source->setCancelled();
		}

		parent::attack($source);
	}

	public function broadcastEntityEvent(int $eventId, ?int $eventData = null, ?array $players = null) : void
	{
		if ($this->spawned && $players === null) {
			$players = $this->getViewers();
			$players[] = $this;
		}
		parent::broadcastEntityEvent($eventId, $eventData, $players);
	}

	public function getOffsetPosition(Vector3 $vector3) : Vector3
	{
		$result = parent::getOffsetPosition($vector3);
		$result->y += 0.001; //Hack for MCPE falling underground for no good reason (TODO: find out why it's doing this)
		return $result;
	}

	/**
	 * @param Player[]|null $targets
	 */
	public function sendPosition(Vector3 $pos, float $yaw = null, float $pitch = null, int $mode = MovePlayerPacket::MODE_NORMAL, array $targets = null) : void
	{
		$yaw = $yaw ?? $this->yaw;
		$pitch = $pitch ?? $this->pitch;

		$pk = new MovePlayerPacket();
		$pk->entityRuntimeId = $this->getId();
		$pk->position = $this->getOffsetPosition($pos);
		$pk->pitch = $pitch;
		$pk->headYaw = $yaw;
		$pk->yaw = $yaw;
		$pk->mode = $mode;
		$pk->ridingEid = intval($this->ridingEid);

		if ($targets !== null) {
			if (in_array($this, $targets, true)) {
				$this->forceMoveSync = true;
				$this->ySize = 0;
			}
			$this->server->broadcastPacket($targets, $pk);
		} else {
			$this->forceMoveSync = true;
			$this->ySize = 0;
			$this->sendDataPacket($pk);
		}
	}

	/**
	 * {@inheritdoc}
	 */
	public function teleport(Vector3 $pos, float $yaw = null, float $pitch = null) : bool
	{
		if (parent::teleport($pos, $yaw, $pitch)) {

			$this->removeAllWindows();

			$this->sendPosition($this, $this->yaw, $this->pitch, MovePlayerPacket::MODE_TELEPORT, null);
			$this->sendPosition($this, $this->yaw, $this->pitch, MovePlayerPacket::MODE_TELEPORT, $this->getViewers());

			$this->spawnToAll();

			$this->resetFallDistance();
			$this->nextChunkOrderRun = 0;
			if ($this->spawnChunkLoadCount !== -1) {
				$this->spawnChunkLoadCount = 0;
			}
			$this->stopSleep();

			//TODO: workaround for player last pos not getting updated
			//Entity::updateMovement() normally handles this, but it's overridden with an empty function in Player
			$this->resetLastMovements();

			return true;
		}

		return false;
	}

	protected function addDefaultWindows() : void
	{
		$this->addWindow($this->getInventory(), ContainerIds::INVENTORY, true);
		$this->addWindow($this->getOffHandInventory(), ContainerIds::OFFHAND, true);
		$this->addWindow($this->getArmorInventory(), ContainerIds::ARMOR, true);

		$this->cursorInventory = new PlayerCursorInventory($this);
		$this->uiInventory = new PlayerUIInventory($this);
		$this->addWindow($this->uiInventory, ContainerIds::UI, true);

		$this->craftingGrid = new CraftingGrid($this, CraftingGrid::SIZE_SMALL);

		$this->creativeInventory = new CreativeInventory($this);

		//TODO: more windows
	}

	/**
	 * @deprecated
	 */
	public function getCursorInventory() : PlayerCursorInventory
	{
		return $this->cursorInventory;
	}

	public function getCreativeInventory() : CreativeInventory
	{
		return $this->creativeInventory;
	}

	public function getUIInventory() : PlayerUIInventory
	{
		return $this->uiInventory;
	}

	public function getCraftingGrid() : CraftingGrid
	{
		return $this->craftingGrid;
	}

	public function setCraftingGrid(CraftingGrid $grid) : void
	{
		$this->craftingGrid = $grid;
	}

	public function doCloseInventory() : void
	{
		$contents = $this->craftingGrid->getContents();
		if (count($contents) > 0) {
			$drops = $this->inventory->addItem(...$contents);
			foreach ($drops as $drop) {
				$this->dropItem($drop);
			}

			$this->craftingGrid->clearAll();
		}

		if (!$this->uiInventory->isSlotEmpty(UIInventorySlotOffset::CURSOR)) {
			if ($this->inventory->canAddItem($item = $this->uiInventory->getItem(UIInventorySlotOffset::CURSOR))) {
				$this->inventory->addItem($this->uiInventory->getItem(UIInventorySlotOffset::CURSOR));
			} else {
				$this->dropItem($item);
			}
		}

		$this->uiInventory->clearAll();

		if ($this->craftingGrid->getGridWidth() > CraftingGrid::SIZE_SMALL) {
			$this->craftingGrid = new CraftingGrid($this, CraftingGrid::SIZE_SMALL);
		}
	}

	/**
	 * Returns the window ID which the inventory has for this player, or -1 if the window is not open to the player.
	 */
	public function getWindowId(Inventory $inventory) : int
	{
		return $this->windows[spl_object_hash($inventory)] ?? ContainerIds::NONE;
	}

	/**
	 * Returns the inventory window open to the player with the specified window ID, or null if no window is open with
	 * that ID.
	 *
	 * @return Inventory|null
	 */
	public function getWindow(int $windowId)
	{
		return $this->windowIndex[$windowId] ?? null;
	}

	public function findWindow(string $expectedClass) : ?Inventory
	{
		foreach ($this->windowIndex as $window) {
			if ($window instanceof $expectedClass) {
				return $window;
			}
		}

		return null;
	}

	/**
	 * Opens an inventory window to the player. Returns the ID of the created window, or the existing window ID if the
	 * player is already viewing the specified inventory.
	 *
	 * @param int|null $forceId     Forces a special ID for the window
	 * @param bool     $isPermanent Prevents the window being removed if true.
	 */
	public function addWindow(Inventory $inventory, int $forceId = null, bool $isPermanent = false) : int
	{
		if (($id = $this->getWindowId($inventory)) !== ContainerIds::NONE) {
			return $id;
		}

		if ($forceId === null) {
			$this->windowCnt = $cnt = $this->getNewWindowId();
		} else {
			$cnt = $forceId;
			if (isset($this->windowIndex[$cnt])) {
				$this->server->getLogger()->critical("Requested force ID $forceId already in use for " . $this->getName());
				return -1;
			}
		}

		$this->windowIndex[$cnt] = $inventory;
		$this->windows[spl_object_hash($inventory)] = $cnt;
		if ($inventory->open($this)) {
			if ($isPermanent) {
				$this->permanentWindows[$cnt] = true;
			}
			return $cnt;
		} else {
			$this->removeWindow($inventory);

			return -1;
		}
	}

	/**
	 * Removes an inventory window from the player.
	 *
	 * @param bool $force Forces removal of permanent windows such as normal inventory, cursor
	 *
	 * @return void
	 */
	public function removeWindow(Inventory $inventory, bool $force = false)
	{
		$id = $this->windows[$hash = spl_object_hash($inventory)] ?? null;

		if ($id !== null && !$force && isset($this->permanentWindows[$id])) {
			$this->server->getLogger()->debug("Cannot remove fixed window $id (" . get_class($inventory) . ") from " . $this->getName());
			return;
		}

		if ($id !== null) {
			(new InventoryCloseEvent($inventory, $this))->call();
			$inventory->close($this);
			unset($this->windows[$hash], $this->windowIndex[$id], $this->permanentWindows[$id]);
		}
	}

	/**
	 * Removes all inventory windows from the player. By default this WILL NOT remove permanent windows.
	 *
	 * @param bool $removePermanentWindows Whether to remove permanent windows.
	 *
	 * @return void
	 */
	public function removeAllWindows(bool $removePermanentWindows = false)
	{
		foreach ($this->windowIndex as $id => $window) {
			if (!$removePermanentWindows && isset($this->permanentWindows[$id])) {
				continue;
			}

			$this->removeWindow($window, $removePermanentWindows);
		}
	}

	protected function sendAllInventories() : void
	{
		foreach ($this->windowIndex as $id => $inventory) {
			$inventory->sendContents($this);
		}
	}

	public function onChunkChanged(Chunk $chunk) : void
	{
		$hasSent = $this->usedChunks[$hash = Level::chunkHash($chunk->getX(), $chunk->getZ())] ?? false;
		if ($hasSent) {
			$this->usedChunks[$hash] = false;
			$this->nextChunkOrderRun = 0;
		}
	}

	public function onChunkLoaded(Chunk $chunk) : void
	{
		//NOOP
	}

	public function onChunkUnloaded(Chunk $chunk) : void
	{
		//NOOP
	}

	public function onChunkPopulated(Chunk $chunk) : void
	{
		//NOOP
	}

	public function onBlockChanged(Vector3 $block) : void
	{
		//NOOP
	}

	public function onTileChanged(Tile $tile) : void
	{
		//NOOP
	}

	/**
	 * Gets client's current input mode
	 */
	public function getCurrentInputMode() : int
	{
		return $this->currentInputMode;
	}

	/**
	 * Gets client's default input mode
	 */
	public function getDefaultInputMode() : int
	{
		return $this->defaultInputMode;
	}

	public function getDeviceOS() : ?int
	{
		return $this->deviceOS;
	}

	public function getDeviceModel() : ?string
	{
		return $this->deviceModel;
	}

	public function getDeviceId() : ?string
	{
		return $this->deviceId;
	}

	public function getProtocolVersion() : int
	{
		return $this->protocolVersion;
	}

	public function getChunkProtocol() : int
	{
		return $this->chunkProtocolVersion;
	}

	public function getCraftingProtocol() : int
	{
		return $this->craftingProtocolVersion;
	}

	public function getMapProtocol() : int
	{
		return $this->mapProtocolVersion;
	}

	/**
	 * @deprecated
	 */
	public function getProtocol() : int
	{
		return $this->protocolVersion;
	}

	public function getVanillaVersion() : string
	{
		return $this->vanillaVersion;
	}

	public function isBedrock() : bool
	{
		return $this->protocolVersion >= ProtocolInfo::PROTOCOL_137;
	}

	public function getSessionId() : int
	{
		return $this->sessionId;
	}

	public function getPacketSender() : PacketSender
	{
		return $this->sender;
	}

	public function getNewWindowId() : int
	{
		$this->windowCnt = max(ContainerIds::FIRST, ($this->windowCnt + 1) % ContainerIds::LAST);
		return $this->windowCnt;
	}

	public function getServerAddress() : string {
		return $this->serverAddress;
	}

	/**
	 * @deprecated
	 */
	public function getFaultyOldTransactionActions() : array {
		return $this->faultyOldTransactionActions;
	}

	/**
	 * @param InventoryAction[] $actions
	 * @deprecated
	 */
	public function setFaultyOldTransactionActions(array $actions) : void {
		$this->faultyOldTransactionActions = $actions;
	}

	/**
	 * @deprecated
	 */
	public function addInventoryTransactionActions(InventoryAction $action) : void {
		$this->faultyOldTransactionActions[] = $action;
	}
}
