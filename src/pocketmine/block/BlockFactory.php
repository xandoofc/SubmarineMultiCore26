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

namespace pocketmine\block;

use InvalidArgumentException;
use pocketmine\item\Item;
use pocketmine\level\Position;
use RuntimeException;
use SplFixedArray;

use function array_fill;
use function array_filter;
use function min;

/**
 * Manages block registration and instance creation
 */
class BlockFactory
{
	/**
	 * @var SplFixedArray|Block[]
	 * @phpstan-var SplFixedArray<Block>
	 */
	public static $fullList = null;

	/**
	 * @var SplFixedArray|int[]
	 * @phpstan-var SplFixedArray<int>
	 */
	private static $mappedStateIds;

	/**
	 * @var SplFixedArray|int[]
	 * @phpstan-var SplFixedArray<int>
	 */
	public static $light;
	/**
	 * @var SplFixedArray|int[]
	 * @phpstan-var SplFixedArray<int>
	 */
	public static $lightFilter;
	/**
	 * @var SplFixedArray|bool[]
	 * @phpstan-var SplFixedArray<bool>
	 */
	public static $diffusesSkyLight;
	/**
	 * @var SplFixedArray|float[]
	 * @phpstan-var SplFixedArray<float>
	 */
	public static $blastResistance;

	/** @var SplFixedArray */
	public static $hasEntityCollision = null;

		 /**
		  * Initializes the block factory. By default this is called only once on server start, however you may wish to use
		  * this if you need to reset the block factory back to its original defaults for whatever reason.
		  */
	public static function init() : void
	{
		if (self::$fullList === null) {
			self::$fullList = new SplFixedArray(2048 << Block::INTERNAL_METADATA_BITS);
			self::$mappedStateIds = new SplFixedArray(2048 << Block::INTERNAL_METADATA_BITS);

			self::$light = SplFixedArray::fromArray(array_fill(0, 2048 << Block::INTERNAL_METADATA_BITS, 0));
			self::$lightFilter = SplFixedArray::fromArray(array_fill(0, 2048 << Block::INTERNAL_METADATA_BITS, 1));
			self::$diffusesSkyLight = SplFixedArray::fromArray(array_fill(0, 2048 << Block::INTERNAL_METADATA_BITS, false));
			self::$blastResistance = SplFixedArray::fromArray(array_fill(0, 2048 << Block::INTERNAL_METADATA_BITS, 0.0));
			self::$hasEntityCollision = SplFixedArray::fromArray(array_fill(0, 2048 << Block::INTERNAL_METADATA_BITS, false));

			self::registerBlock(new Air());
			self::registerBlock(new Stone());
			self::registerBlock(new Grass());
			self::registerBlock(new Dirt());
			self::registerBlock(new Cobblestone());
			self::registerBlock(new Planks());
			self::registerBlock(new Sapling());
			self::registerBlock(new Bedrock());
			self::registerBlock(new Water());
			self::registerBlock(new StillWater());
			self::registerBlock(new Lava());
			self::registerBlock(new StillLava());
			self::registerBlock(new Sand());
			self::registerBlock(new Gravel());
			self::registerBlock(new GoldOre());
			self::registerBlock(new IronOre());
			self::registerBlock(new CoalOre());
			self::registerBlock(new Log());
			self::registerBlock(new Leaves());
			self::registerBlock(new Sponge());
			self::registerBlock(new Glass());
			self::registerBlock(new LapisOre());
			self::registerBlock(new Lapis());
			self::registerBlock(new Sandstone());
			self::registerBlock(new NoteBlock());
			self::registerBlock(new Bed());
			self::registerBlock(new PoweredRail());
			self::registerBlock(new DetectorRail());
			//TODO: STICKY_PISTON
			self::registerBlock(new Cobweb());
			self::registerBlock(new TallGrass());
			self::registerBlock(new DeadBush());
			//TODO: PISTON
			//TODO: PISTONARMCOLLISION
			self::registerBlock(new Wool());
			self::registerBlock(new Element(Block::ELEMENT_0, 0, "???"));
			self::registerBlock(new Dandelion());
			self::registerBlock(new Flower());
			self::registerBlock(new BrownMushroom());
			self::registerBlock(new RedMushroom());
			self::registerBlock(new Gold());
			self::registerBlock(new Iron());
			self::registerBlock(new DoubleStoneSlab());
			self::registerBlock(new StoneSlab());
			self::registerBlock(new Bricks());
			self::registerBlock(new TNT());
			self::registerBlock(new Bookshelf());
			self::registerBlock(new MossyCobblestone());
			self::registerBlock(new Obsidian());
			self::registerBlock(new Torch());
			self::registerBlock(new Fire());
			self::registerBlock(new MonsterSpawner());
			self::registerBlock(new WoodenStairs(Block::OAK_STAIRS, 0, "Oak Stairs"));
			self::registerBlock(new Chest());
			//TODO: REDSTONE_WIRE
			self::registerBlock(new DiamondOre());
			self::registerBlock(new Diamond());
			self::registerBlock(new CraftingTable());
			self::registerBlock(new Wheat());
			self::registerBlock(new Farmland());
			self::registerBlock(new Furnace());
			self::registerBlock(new BurningFurnace());
			self::registerBlock(new SignPost(Block::SIGN_POST, 0, "Oak Sign Post", Item::SIGN, Block::WALL_SIGN));
			self::registerBlock(new WoodenDoor(Block::OAK_DOOR_BLOCK, 0, "Oak Door", Item::OAK_DOOR));
			self::registerBlock(new Ladder());
			self::registerBlock(new Rail());
			self::registerBlock(new CobblestoneStairs());
			self::registerBlock(new WallSign(Block::WALL_SIGN, 0, "Oak Sign Post", Item::SIGN, Block::WALL_SIGN));
			self::registerBlock(new Lever());
			self::registerBlock(new StonePressurePlate(Block::STONE_PRESSURE_PLATE, 0, "Stone Pressure Plate"));
			self::registerBlock(new IronDoor());
			self::registerBlock(new WoodenPressurePlate(Block::WOODEN_PRESSURE_PLATE, 0, "Wooden Pressure Plate"));
			self::registerBlock(new RedstoneOre());
			self::registerBlock(new GlowingRedstoneOre());
			self::registerBlock(new RedstoneTorchUnlit());
			self::registerBlock(new RedstoneTorch());
			self::registerBlock(new StoneButton(Block::STONE_BUTTON, 0, "Stone Button"));
			self::registerBlock(new SnowLayer());
			self::registerBlock(new Ice());
			self::registerBlock(new Snow());
			self::registerBlock(new Cactus());
			self::registerBlock(new Clay());
			self::registerBlock(new Sugarcane());
			self::registerBlock(new Jukebox());
			self::registerBlock(new WoodenFence());
			self::registerBlock(new Pumpkin());
			self::registerBlock(new Netherrack());
			self::registerBlock(new SoulSand());
			self::registerBlock(new Glowstone());
			self::registerBlock(new NetherPortal());
			self::registerBlock(new LitPumpkin());
			self::registerBlock(new Cake());
			//TODO: REPEATER_BLOCK
			//TODO: POWERED_REPEATER
			self::registerBlock(new InvisibleBedrock());
			self::registerBlock(new WoodenTrapdoor(Block::WOODEN_TRAPDOOR, 0, "Wooden Trapdoor"));
			self::registerBlock(new InfestedStone());
			self::registerBlock(new StoneBricks());
			self::registerBlock(new BrownMushroomBlock());
			self::registerBlock(new RedMushroomBlock());
			self::registerBlock(new IronBars());
			self::registerBlock(new GlassPane());
			self::registerBlock(new Melon());
			self::registerBlock(new PumpkinStem());
			self::registerBlock(new MelonStem());
			self::registerBlock(new Vine());
			self::registerBlock(new FenceGate(Block::OAK_FENCE_GATE, 0, "Oak Fence Gate"));
			self::registerBlock(new BrickStairs());
			self::registerBlock(new StoneBrickStairs());
			self::registerBlock(new Mycelium());
			self::registerBlock(new WaterLily());
			self::registerBlock(new NetherBrick(Block::NETHER_BRICK_BLOCK, 0, "Nether Bricks"));
			self::registerBlock(new NetherBrickFence());
			self::registerBlock(new NetherBrickStairs());
			self::registerBlock(new NetherWartPlant());
			self::registerBlock(new EnchantingTable());
			self::registerBlock(new BrewingStand());
			self::registerBlock(new Cauldron());
			self::registerBlock(new EndPortal());
			self::registerBlock(new EndPortalFrame());
			self::registerBlock(new EndStone());
			self::registerBlock(new DragonEgg());
			self::registerBlock(new RedstoneLamp());
			self::registerBlock(new LitRedstoneLamp());
			//TODO: DROPPER
			self::registerBlock(new ActivatorRail());
			self::registerBlock(new CocoaBlock());
			self::registerBlock(new SandstoneStairs());
			self::registerBlock(new EmeraldOre());
			self::registerBlock(new EnderChest());
			self::registerBlock(new TripwireHook());
			self::registerBlock(new Tripwire());
			self::registerBlock(new Emerald());
			self::registerBlock(new WoodenStairs(Block::SPRUCE_STAIRS, 0, "Spruce Stairs"));
			self::registerBlock(new WoodenStairs(Block::BIRCH_STAIRS, 0, "Birch Stairs"));
			self::registerBlock(new WoodenStairs(Block::JUNGLE_STAIRS, 0, "Jungle Stairs"));
			//TODO: COMMAND_BLOCK
			self::registerBlock(new Beacon());
			self::registerBlock(new CobblestoneWall());
			self::registerBlock(new FlowerPot());
			self::registerBlock(new Carrot());
			self::registerBlock(new Potato());
			self::registerBlock(new WoodenButton(Block::WOODEN_BUTTON, 0, "Wooden Button"));
			self::registerBlock(new Skull());
			self::registerBlock(new Anvil());
			self::registerBlock(new TrappedChest());
			self::registerBlock(new WeightedPressurePlateLight());
			self::registerBlock(new WeightedPressurePlateHeavy());
			//TODO: COMPARATOR_BLOCK
			//TODO: POWERED_COMPARATOR
			self::registerBlock(new DaylightSensor());
			self::registerBlock(new Redstone());
			self::registerBlock(new NetherQuartzOre());
			self::registerBlock(new Hopper());
			self::registerBlock(new Quartz());
			self::registerBlock(new QuartzStairs());
			self::registerBlock(new DoubleWoodenSlab());
			self::registerBlock(new WoodenSlab());
			self::registerBlock(new StainedHardenedClay());
			self::registerBlock(new StainedGlassPane());
			self::registerBlock(new Leaves2());
			self::registerBlock(new Log2());
			self::registerBlock(new WoodenStairs(Block::ACACIA_STAIRS, 0, "Acacia Stairs"));
			self::registerBlock(new WoodenStairs(Block::DARK_OAK_STAIRS, 0, "Dark Oak Stairs"));
			self::registerBlock(new Slime());
			self::registerBlock(new IronTrapdoor(Block::IRON_TRAPDOOR, 0, "Iron Trapdoor"));
			self::registerBlock(new Prismarine());
			self::registerBlock(new SeaLantern());
			self::registerBlock(new HayBale());
			self::registerBlock(new Carpet());
			self::registerBlock(new HardenedClay());
			self::registerBlock(new Coal());
			self::registerBlock(new PackedIce());
			self::registerBlock(new DoublePlant());
			self::registerBlock(new StandingBanner());
			self::registerBlock(new WallBanner());
			//TODO: DAYLIGHT_DETECTOR_INVERTED
			self::registerBlock(new RedSandstone());
			self::registerBlock(new RedSandstoneStairs());
			self::registerBlock(new DoubleStoneSlab2());
			self::registerBlock(new StoneSlab2());
			self::registerBlock(new FenceGate(Block::SPRUCE_FENCE_GATE, 0, "Spruce Fence Gate"));
			self::registerBlock(new FenceGate(Block::BIRCH_FENCE_GATE, 0, "Birch Fence Gate"));
			self::registerBlock(new FenceGate(Block::JUNGLE_FENCE_GATE, 0, "Jungle Fence Gate"));
			self::registerBlock(new FenceGate(Block::DARK_OAK_FENCE_GATE, 0, "Dark Oak Fence Gate"));
			self::registerBlock(new FenceGate(Block::ACACIA_FENCE_GATE, 0, "Acacia Fence Gate"));
			//TODO: REPEATING_COMMAND_BLOCK
			//TODO: CHAIN_COMMAND_BLOCK
			//TODO: HARD_GRASS_PANE
			//TODO: HARD_STAINED_GLASS_PANE
			//TODO: CHEMICAL_HEAT
			self::registerBlock(new WoodenDoor(Block::SPRUCE_DOOR_BLOCK, 0, "Spruce Door", Item::SPRUCE_DOOR));
			self::registerBlock(new WoodenDoor(Block::BIRCH_DOOR_BLOCK, 0, "Birch Door", Item::BIRCH_DOOR));
			self::registerBlock(new WoodenDoor(Block::JUNGLE_DOOR_BLOCK, 0, "Jungle Door", Item::JUNGLE_DOOR));
			self::registerBlock(new WoodenDoor(Block::ACACIA_DOOR_BLOCK, 0, "Acacia Door", Item::ACACIA_DOOR));
			self::registerBlock(new WoodenDoor(Block::DARK_OAK_DOOR_BLOCK, 0, "Dark Oak Door", Item::DARK_OAK_DOOR));
			self::registerBlock(new GrassPath());
			self::registerBlock(new ItemFrame());
			//TODO: CHORUS_FLOWER
			self::registerBlock(new Purpur());
			//TODO: COLORED_TORCH_RG
			self::registerBlock(new PurpurStairs());
			self::registerBlock(new UndyedShulkerBox());
			self::registerBlock(new EndBricks());
			self::registerBlock(new FrostedIce());
			self::registerBlock(new EndRod());
			//TODO: END_GATEWAY
			//TODO: ALLOW
			//TODO: DENY
			//TODO: BORDER_BLOCM
			self::registerBlock(new Magma());
			self::registerBlock(new NetherWartBlock());
			self::registerBlock(new NetherBrick(Block::RED_NETHER_BRICK, 0, "Red Nether Bricks"));
			self::registerBlock(new BoneBlock());
			self::registerBlock(new ShulkerBox());
			self::registerBlock(new GlazedTerracotta(Block::PURPLE_GLAZED_TERRACOTTA, 0, "Purple Glazed Terracotta"));
			self::registerBlock(new GlazedTerracotta(Block::WHITE_GLAZED_TERRACOTTA, 0, "White Glazed Terracotta"));
			self::registerBlock(new GlazedTerracotta(Block::ORANGE_GLAZED_TERRACOTTA, 0, "Orange Glazed Terracotta"));
			self::registerBlock(new GlazedTerracotta(Block::MAGENTA_GLAZED_TERRACOTTA, 0, "Magenta Glazed Terracotta"));
			self::registerBlock(new GlazedTerracotta(Block::LIGHT_BLUE_GLAZED_TERRACOTTA, 0, "Light Blue Glazed Terracotta"));
			self::registerBlock(new GlazedTerracotta(Block::YELLOW_GLAZED_TERRACOTTA, 0, "Yellow Glazed Terracotta"));
			self::registerBlock(new GlazedTerracotta(Block::LIME_GLAZED_TERRACOTTA, 0, "Lime Glazed Terracotta"));
			self::registerBlock(new GlazedTerracotta(Block::PINK_GLAZED_TERRACOTTA, 0, "Pink Glazed Terracotta"));
			self::registerBlock(new GlazedTerracotta(Block::GRAY_GLAZED_TERRACOTTA, 0, "Grey Glazed Terracotta"));
			self::registerBlock(new GlazedTerracotta(Block::SILVER_GLAZED_TERRACOTTA, 0, "Light Grey Glazed Terracotta"));
			self::registerBlock(new GlazedTerracotta(Block::CYAN_GLAZED_TERRACOTTA, 0, "Cyan Glazed Terracotta"));

			self::registerBlock(new GlazedTerracotta(Block::BLUE_GLAZED_TERRACOTTA, 0, "Blue Glazed Terracotta"));
			self::registerBlock(new GlazedTerracotta(Block::BROWN_GLAZED_TERRACOTTA, 0, "Brown Glazed Terracotta"));
			self::registerBlock(new GlazedTerracotta(Block::GREEN_GLAZED_TERRACOTTA, 0, "Green Glazed Terracotta"));
			self::registerBlock(new GlazedTerracotta(Block::RED_GLAZED_TERRACOTTA, 0, "Red Glazed Terracotta"));
			self::registerBlock(new GlazedTerracotta(Block::BLACK_GLAZED_TERRACOTTA, 0, "Black Glazed Terracotta"));
			self::registerBlock(new Concrete());
			self::registerBlock(new ConcretePowder());
			//TODO: CHEMISTRY_TABLE
			//TODO: UNDERWATER_TORCH
			//TODO: CHORUS_PLANT
			self::registerBlock(new StainedGlass());
			//TODO: CAMERA
			self::registerBlock(new Podzol());
			self::registerBlock(new Beetroot());
			self::registerBlock(new Stonecutter());
			self::registerBlock(new GlowingObsidian());
			self::registerBlock(new NetherReactor());
			self::registerBlock(new InfoUpdate(Block::INFO_UPDATE, 0, "update!"));
			self::registerBlock(new InfoUpdate(Block::INFO_UPDATE2, 0, "ate!upd"));
			//TODO: MOVINGBLOCK
			//TODO: OBSERVER
			//TODO: STRUCTURE_BLOCK
			//TODO: HARD_GLASS
			//TODO: HARD_STAINED_GLASS
			self::registerBlock(new Reserved6(Block::RESERVED6, 0, "reserved6"));

			self::registerBlock(new PrismarineStairs());
			self::registerBlock(new DarkPrismarineStairs());
			self::registerBlock(new PrismarineBricksStairs());
			self::registerBlock(new StrippedLog(Block::STRIPPED_SPRUCE_LOG, 0, "Stripped Spruce Log"));
			self::registerBlock(new StrippedLog(Block::STRIPPED_BIRCH_LOG, 0, "Stripped Birch Log"));
			self::registerBlock(new StrippedLog(Block::STRIPPED_JUNGLE_LOG, 0, "Stripped Jungle Log"));
			self::registerBlock(new StrippedLog(Block::STRIPPED_ACACIA_LOG, 0, "Stripped Acacia Log"));
			self::registerBlock(new StrippedLog(Block::STRIPPED_DARK_OAK_LOG, 0, "Stripped Dark Oak Log"));
			self::registerBlock(new StrippedLog(Block::STRIPPED_OAK_LOG, 0, "Stripped Oak Log"));
			self::registerBlock(new BlueIce());
			self::registerBlock(new Element(Block::ELEMENT_1, 0, "Hydrogen"));
			self::registerBlock(new Element(Block::ELEMENT_2, 0, "Helium"));
			self::registerBlock(new Element(Block::ELEMENT_3, 0, "Lithium"));
			self::registerBlock(new Element(Block::ELEMENT_4, 0, "Beryllium"));
			self::registerBlock(new Element(Block::ELEMENT_5, 0, "Boron"));
			self::registerBlock(new Element(Block::ELEMENT_6, 0, "Carbon"));
			self::registerBlock(new Element(Block::ELEMENT_7, 0, "Nitrogen"));
			self::registerBlock(new Element(Block::ELEMENT_8, 0, "Oxygen"));
			self::registerBlock(new Element(Block::ELEMENT_9, 0, "Fluorine"));
			self::registerBlock(new Element(Block::ELEMENT_10, 0, "Neon"));
			self::registerBlock(new Element(Block::ELEMENT_11, 0, "Sodium"));
			self::registerBlock(new Element(Block::ELEMENT_12, 0, "Magnesium"));
			self::registerBlock(new Element(Block::ELEMENT_13, 0, "Aluminum"));
			self::registerBlock(new Element(Block::ELEMENT_14, 0, "Silicon"));
			self::registerBlock(new Element(Block::ELEMENT_15, 0, "Phosphorus"));
			self::registerBlock(new Element(Block::ELEMENT_16, 0, "Sulfur"));
			self::registerBlock(new Element(Block::ELEMENT_17, 0, "Chlorine"));
			self::registerBlock(new Element(Block::ELEMENT_18, 0, "Argon"));
			self::registerBlock(new Element(Block::ELEMENT_19, 0, "Potassium"));
			self::registerBlock(new Element(Block::ELEMENT_20, 0, "Calcium"));
			self::registerBlock(new Element(Block::ELEMENT_21, 0, "Scandium"));
			self::registerBlock(new Element(Block::ELEMENT_22, 0, "Titanium"));
			self::registerBlock(new Element(Block::ELEMENT_23, 0, "Vanadium"));
			self::registerBlock(new Element(Block::ELEMENT_24, 0, "Chromium"));
			self::registerBlock(new Element(Block::ELEMENT_25, 0, "Manganese"));
			self::registerBlock(new Element(Block::ELEMENT_26, 0, "Iron"));
			self::registerBlock(new Element(Block::ELEMENT_27, 0, "Cobalt"));
			self::registerBlock(new Element(Block::ELEMENT_28, 0, "Nickel"));
			self::registerBlock(new Element(Block::ELEMENT_29, 0, "Copper"));
			self::registerBlock(new Element(Block::ELEMENT_30, 0, "Zinc"));
			self::registerBlock(new Element(Block::ELEMENT_31, 0, "Gallium"));
			self::registerBlock(new Element(Block::ELEMENT_32, 0, "Germanium"));
			self::registerBlock(new Element(Block::ELEMENT_33, 0, "Arsenic"));
			self::registerBlock(new Element(Block::ELEMENT_34, 0, "Selenium"));
			self::registerBlock(new Element(Block::ELEMENT_35, 0, "Bromine"));
			self::registerBlock(new Element(Block::ELEMENT_36, 0, "Krypton"));
			self::registerBlock(new Element(Block::ELEMENT_37, 0, "Rubidium"));
			self::registerBlock(new Element(Block::ELEMENT_38, 0, "Strontium"));
			self::registerBlock(new Element(Block::ELEMENT_39, 0, "Yttrium"));
			self::registerBlock(new Element(Block::ELEMENT_40, 0, "Zirconium"));
			self::registerBlock(new Element(Block::ELEMENT_41, 0, "Niobium"));
			self::registerBlock(new Element(Block::ELEMENT_42, 0, "Molybdenum"));
			self::registerBlock(new Element(Block::ELEMENT_43, 0, "Technetium"));
			self::registerBlock(new Element(Block::ELEMENT_44, 0, "Ruthenium"));
			self::registerBlock(new Element(Block::ELEMENT_45, 0, "Rhodium"));
			self::registerBlock(new Element(Block::ELEMENT_46, 0, "Palladium"));
			self::registerBlock(new Element(Block::ELEMENT_47, 0, "Silver"));
			self::registerBlock(new Element(Block::ELEMENT_48, 0, "Cadmium"));
			self::registerBlock(new Element(Block::ELEMENT_49, 0, "Indium"));
			self::registerBlock(new Element(Block::ELEMENT_50, 0, "Tin"));
			self::registerBlock(new Element(Block::ELEMENT_51, 0, "Antimony"));
			self::registerBlock(new Element(Block::ELEMENT_52, 0, "Tellurium"));
			self::registerBlock(new Element(Block::ELEMENT_53, 0, "Iodine"));
			self::registerBlock(new Element(Block::ELEMENT_54, 0, "Xenon"));
			self::registerBlock(new Element(Block::ELEMENT_55, 0, "Cesium"));
			self::registerBlock(new Element(Block::ELEMENT_56, 0, "Barium"));
			self::registerBlock(new Element(Block::ELEMENT_57, 0, "Lanthanum"));
			self::registerBlock(new Element(Block::ELEMENT_58, 0, "Cerium"));
			self::registerBlock(new Element(Block::ELEMENT_59, 0, "Praseodymium"));
			self::registerBlock(new Element(Block::ELEMENT_60, 0, "Neodymium"));
			self::registerBlock(new Element(Block::ELEMENT_61, 0, "Promethium"));
			self::registerBlock(new Element(Block::ELEMENT_62, 0, "Samarium"));
			self::registerBlock(new Element(Block::ELEMENT_63, 0, "Europium"));
			self::registerBlock(new Element(Block::ELEMENT_64, 0, "Gadolinium"));
			self::registerBlock(new Element(Block::ELEMENT_65, 0, "Terbium"));
			self::registerBlock(new Element(Block::ELEMENT_66, 0, "Dysprosium"));
			self::registerBlock(new Element(Block::ELEMENT_67, 0, "Holmium"));
			self::registerBlock(new Element(Block::ELEMENT_68, 0, "Erbium"));
			self::registerBlock(new Element(Block::ELEMENT_69, 0, "Thulium"));
			self::registerBlock(new Element(Block::ELEMENT_70, 0, "Ytterbium"));
			self::registerBlock(new Element(Block::ELEMENT_71, 0, "Lutetium"));
			self::registerBlock(new Element(Block::ELEMENT_72, 0, "Hafnium"));
			self::registerBlock(new Element(Block::ELEMENT_73, 0, "Tantalum"));
			self::registerBlock(new Element(Block::ELEMENT_74, 0, "Tungsten"));
			self::registerBlock(new Element(Block::ELEMENT_75, 0, "Rhenium"));
			self::registerBlock(new Element(Block::ELEMENT_76, 0, "Osmium"));
			self::registerBlock(new Element(Block::ELEMENT_77, 0, "Iridium"));
			self::registerBlock(new Element(Block::ELEMENT_78, 0, "Platinum"));
			self::registerBlock(new Element(Block::ELEMENT_79, 0, "Gold"));
			self::registerBlock(new Element(Block::ELEMENT_80, 0, "Mercury"));
			self::registerBlock(new Element(Block::ELEMENT_81, 0, "Thallium"));
			self::registerBlock(new Element(Block::ELEMENT_82, 0, "Lead"));
			self::registerBlock(new Element(Block::ELEMENT_83, 0, "Bismuth"));
			self::registerBlock(new Element(Block::ELEMENT_84, 0, "Polonium"));
			self::registerBlock(new Element(Block::ELEMENT_85, 0, "Astatine"));
			self::registerBlock(new Element(Block::ELEMENT_86, 0, "Radon"));
			self::registerBlock(new Element(Block::ELEMENT_87, 0, "Francium"));
			self::registerBlock(new Element(Block::ELEMENT_88, 0, "Radium"));
			self::registerBlock(new Element(Block::ELEMENT_89, 0, "Actinium"));
			self::registerBlock(new Element(Block::ELEMENT_90, 0, "Thorium"));
			self::registerBlock(new Element(Block::ELEMENT_91, 0, "Protactinium"));
			self::registerBlock(new Element(Block::ELEMENT_92, 0, "Uranium"));
			self::registerBlock(new Element(Block::ELEMENT_93, 0, "Neptunium"));
			self::registerBlock(new Element(Block::ELEMENT_94, 0, "Plutonium"));
			self::registerBlock(new Element(Block::ELEMENT_95, 0, "Americium"));
			self::registerBlock(new Element(Block::ELEMENT_96, 0, "Curium"));
			self::registerBlock(new Element(Block::ELEMENT_97, 0, "Berkelium"));
			self::registerBlock(new Element(Block::ELEMENT_98, 0, "Californium"));
			self::registerBlock(new Element(Block::ELEMENT_99, 0, "Einsteinium"));
			self::registerBlock(new Element(Block::ELEMENT_100, 0, "Fermium"));
			self::registerBlock(new Element(Block::ELEMENT_101, 0, "Mendelevium"));
			self::registerBlock(new Element(Block::ELEMENT_102, 0, "Nobelium"));
			self::registerBlock(new Element(Block::ELEMENT_103, 0, "Lawrencium"));
			self::registerBlock(new Element(Block::ELEMENT_104, 0, "Rutherfordium"));
			self::registerBlock(new Element(Block::ELEMENT_105, 0, "Dubnium"));
			self::registerBlock(new Element(Block::ELEMENT_106, 0, "Seaborgium"));
			self::registerBlock(new Element(Block::ELEMENT_107, 0, "Bohrium"));
			self::registerBlock(new Element(Block::ELEMENT_108, 0, "Hassium"));
			self::registerBlock(new Element(Block::ELEMENT_109, 0, "Meitnerium"));
			self::registerBlock(new Element(Block::ELEMENT_110, 0, "Darmstadtium"));
			self::registerBlock(new Element(Block::ELEMENT_111, 0, "Roentgenium"));
			self::registerBlock(new Element(Block::ELEMENT_112, 0, "Copernicium"));
			self::registerBlock(new Element(Block::ELEMENT_113, 0, "Nihonium"));
			self::registerBlock(new Element(Block::ELEMENT_114, 0, "Flerovium"));
			self::registerBlock(new Element(Block::ELEMENT_115, 0, "Moscovium"));
			self::registerBlock(new Element(Block::ELEMENT_116, 0, "Livermorium"));
			self::registerBlock(new Element(Block::ELEMENT_117, 0, "Tennessine"));
			self::registerBlock(new Element(Block::ELEMENT_118, 0, "Oganesson"));
			//TODO: SEAGRASS
			//TODO: CORAL
			//TODO: CORAL_BLOCK
			//TODO: CORAL_FAN
			//TODO: CORAL_FAN_DEAD
			//TODO: CORAL_FAN_HANG
			//TODO: CORAL_FAN_HANG2
			//TODO: CORAL_FAN_HANG3
			//TODO: KELP
			//TODO: DRIED_KELP_BLOCK
			self::registerBlock(new WoodenButton(Block::ACACIA_BUTTON, 0, "Acacia Button"));
			self::registerBlock(new WoodenButton(Block::BIRCH_BUTTON, 0, "Birch Button"));
			self::registerBlock(new WoodenButton(Block::DARK_OAK_BUTTON, 0, "Dark Oak Button"));
			self::registerBlock(new WoodenButton(Block::JUNGLE_BUTTON, 0, "Jungle Button"));
			self::registerBlock(new WoodenButton(Block::SPRUCE_BUTTON, 0, "Spruce Button"));
			self::registerBlock(new WoodenTrapdoor(Block::ACACIA_TRAPDOOR, 0, "Acacia Trapdoor"));
			self::registerBlock(new WoodenTrapdoor(Block::BIRCH_TRAPDOOR, 0, "Birch Trapdoor"));
			self::registerBlock(new WoodenTrapdoor(Block::DARK_OAK_TRAPDOOR, 0, "Dark Oak Trapdoor"));
			self::registerBlock(new WoodenTrapdoor(Block::JUNGLE_TRAPDOOR, 0, "Jungle Trapdoor"));
			self::registerBlock(new WoodenTrapdoor(Block::SPRUCE_TRAPDOOR, 0, "Spruce Trapdoor"));
			self::registerBlock(new WoodenPressurePlate(Block::ACACIA_PRESSURE_PLATE, 0, "Acacia Pressure Plate"));
			self::registerBlock(new WoodenPressurePlate(Block::BIRCH_PRESSURE_PLATE, 0, "Birch Pressure Plate"));
			self::registerBlock(new WoodenPressurePlate(Block::DARK_OAK_PRESSURE_PLATE, 0, "Dark Oak Pressure Plate"));
			self::registerBlock(new WoodenPressurePlate(Block::JUNGLE_PRESSURE_PLATE, 0, "Jungle Pressure Plate"));
			self::registerBlock(new WoodenPressurePlate(Block::SPRUCE_PRESSURE_PLATE, 0, "Spruce Pressure Plate"));
			self::registerBlock(new CarvedPumpkin());
			//TODO: SEA_PICKLE
			//TODO: CONDUIT
			//TODO: TURTLE_EGG
			//TODO: BUBBLE_COLUMN
			self::registerBlock(new Barrier());
			self::registerBlock(new StoneSlab3());
			//TODO: BAMBOO
			//TODO: BAMBOO_SAPLING
			//TODO: SCAFFOLDING
			self::registerBlock(new StoneSlab4());
			self::registerBlock(new DoubleStoneSlab3());
			self::registerBlock(new DoubleStoneSlab4());
			self::registerBlock(new GraniteStairs());
			self::registerBlock(new DioriteStairs());
			self::registerBlock(new AndesiteStairs());
			self::registerBlock(new PolishedGraniteStairs());
			self::registerBlock(new PolishedDioriteStairs());
			self::registerBlock(new PolishedAndesiteStairs());
			self::registerBlock(new MossyStoneBrickStairs());
			self::registerBlock(new SmoothRedSandstoneStairs());
			self::registerBlock(new SmoothSandstoneStairs());
			self::registerBlock(new EndBrickStairs());
			self::registerBlock(new MossyCobblestoneStairs());
			self::registerBlock(new NormalStoneStairs());
			self::registerBlock(new SignPost(Block::SPRUCE_STANDING_SIGN, 0, "Spruce Sign Post", Item::SPRUCE_SIGN, Block::SPRUCE_WALL_SIGN));
			self::registerBlock(new WallSign(Block::SPRUCE_WALL_SIGN, 0, "Spruce Sign Post", Item::SPRUCE_SIGN, Block::SPRUCE_WALL_SIGN));
			self::registerBlock(new SmoothStone());
			self::registerBlock(new RedNetherBrickStairs());
			self::registerBlock(new SmoothQuartzStairs());
			self::registerBlock(new SignPost(Block::BIRCH_STANDING_SIGN, 0, "Birch Sign Post", Item::BIRCH_SIGN, Block::BIRCH_WALL_SIGN));
			self::registerBlock(new WallSign(Block::BIRCH_WALL_SIGN, 0, "Birch Sign Post", Item::BIRCH_SIGN, Block::BIRCH_WALL_SIGN));
			self::registerBlock(new SignPost(Block::JUNGLE_STANDING_SIGN, 0, "Jungle Sign Post", Item::JUNGLE_SIGN, Block::JUNGLE_WALL_SIGN));
			self::registerBlock(new WallSign(Block::JUNGLE_WALL_SIGN, 0, "Jungle Sign Post", Item::JUNGLE_SIGN, Block::JUNGLE_WALL_SIGN));
			self::registerBlock(new SignPost(Block::ACACIA_STANDING_SIGN, 0, "Acacia Sign Post", Item::ACACIA_SIGN, Block::ACACIA_WALL_SIGN));
			self::registerBlock(new WallSign(Block::ACACIA_WALL_SIGN, 0, "Acacia Sign Post", Item::ACACIA_SIGN, Block::ACACIA_WALL_SIGN));
			self::registerBlock(new SignPost(Block::DARKOAK_STANDING_SIGN, 0, "Darkoak Sign Post", Item::DARKOAK_SIGN, Block::DARKOAK_WALL_SIGN));
			self::registerBlock(new WallSign(Block::DARKOAK_WALL_SIGN, 0, "Darkoak Sign Post", Item::DARKOAK_SIGN, Block::DARKOAK_WALL_SIGN));
			//TODO: LECTERN
			//TODO: GRINDSTONE
			//TODO: BLAST_FURNACE
			//TODO: STONECUTTER_BLOCK
			//TODO: SMOKER
			//TODO: LIT_SMOKER
			//TODO: CARTOGRAPHY_TABLE
			//TODO: FLETCHING_TABLE
			//TODO: SMITHING_TABLE
			//TODO: BARREL
			//TODO: LOOM
			//TODO: BELL
			//TODO: SWEET_BERRY_BUSH
			self::registerBlock(new Lantern());
			//TODO: CAMPFIRE
			//TODO: LAVA_CAULDRON
			//TODO: JIGSAW
			self::registerBlock(new Wood());
			//TODO: COMPOSTER
			//TODO: LIT_BLAST_FURNACE
			//TODO: LIGHT_BLOCK
			//TODO: WITHER_ROSE
			//TODO: STICKY_PISTON_ARM_COLLISION
			//TODO: BEE_NEST
			//TODO: BEEHIVE
			//TODO: HONEY_BLOCK
			//TODO: HONEYCOMB_BLOCK
			//TODO: LODESTONE
			//TODO: CRIMSON_ROOTS
			//TODO: WARPED_ROOTS
			//TODO: CRIMSON_STEM
			//TODO: WARPED_STEM
			//TODO: WARPED_WART_BLOCK
			//TODO: CRIMSON_FUNGUS
			//TODO: WARPED_FUNGUS
			//TODO: SHROOMLIGHT
			//TODO: WEEPING_VINES
			//TODO: CRIMSON_NYLIUM
			//TODO: WARPED_NYLIUM
			//TODO: BASALT
			//TODO: POLISHED_BASALT
			//TODO: SOUL_SOIL
			//TODO: SOUL_FIRE
			//TODO: NETHER_SPROUTS
			//TODO: TARGET
			//TODO: STRIPPED_CRIMSON_STEM
			//TODO: STRIPPED_WARPED_STEM
			//TODO: CRIMSON_PLANKS
			//TODO: WARPED_PLANKS
			//TODO: CRIMSON_DOOR
			//TODO: WARPED_DOOR
			//TODO: CRIMSON_TRAPDOOR
			//TODO: WARPED_TRAPDOOR
			//TODO: CRIMSON_STANDING_SIGN
			//TODO: WARPED_STANDING_SIGN
			//TODO: CRIMSON_WALL_SIGN
			//TODO: WARPED_WALL_SIGN
			//TODO: CRIMSON_STAIRS
			//TODO: WARPED_STAIRS
			//TODO: CRIMSON_FENCE
			//TODO: WARPED_FENCE
			//TODO: CRIMSON_FENCE_GATE
			//TODO: WARPED_FENCE_GATE
			//TODO: CRIMSON_BUTTON
			//TODO: WARPED_BUTTON
			//TODO: CRIMSON_PRESSURE_PLATE
			//TODO: WARPED_PRESSURE_PLATE
			//TODO: CRIMSON_SLAB
			//TODO: WARPED_SLAB
			//TODO: CRIMSON_DOUBLE_SLAB
			//TODO: WARPED_DOUBLE_SLAB
			//TODO: SOUL_TORCH
			//TODO: SOUL_LANTERN
			//TODO: NETHERITE_BLOCK
			self::registerBlock(new AncientDebris());
			//TODO: RESPAWN_ANCHOR
			//TODO: BLACKSTONE
			//TODO: POLISHED_BLACKSTONE_BRICKS
			//TODO: POLISHED_BLACKSTONE_BRICK_STAIRS
			//TODO: BLACKSTONE_STAIRS
			//TODO: BLACKSTONE_WALL
			//TODO: POLISHED_BLACKSTONE_BRICK_WALL
			//TODO: CHISELED_POLISHED_BLACKSTONE
			//TODO: CRACKED_POLISHED_BLACKSTONE_BRICKS
			//TODO: GILDED_BLACKSTONE
			//TODO: BLACKSTONE_SLAB
			//TODO: BLACKSTONE_DOUBLE_SLAB
			//TODO: POLISHED_BLACKSTONE_BRICK_SLAB
			//TODO: POLISHED_BLACKSTONE_BRICK_DOUBLE_SLAB
			//TODO: CHAIN
			//TODO: TWISTING_VINES
			//TODO: NETHER_GOLD_ORE
			//TODO: CRYING_OBSIDIAN
			//TODO: SOUL_CAMPFIRE
			//TODO: POLISHED_BLACKSTONE
			//TODO: POLISHED_BLACKSTONE_STAIRS
			//TODO: POLISHED_BLACKSTONE_DOUBLE_SLAB
			//TODO: POLISHED_BLACKSTONE_PRESSURE_PLATE
			//TODO: POLISHED_BLACKSTONE_BUTTON
			//TODO: POLISHED_BLACKSTONE_WALL
			//TODO: WARPED_HYPHAE
			//TODO: CRIMSON_HYPHAE
			//TODO: STRIPPED_CRIMSON_HYPHAE
			//TODO: STRIPPED_WARPED_HYPHAE
			//TODO: CHISELED_NETHER_BRICKS
			//TODO: CRACKED_NETHER_BRICKS
			//TODO: QUARTZ_BRICKS
			//TODO: UNKNOWN
			//TODO: POWDER_SNOW
			//TODO: SCULK_SENSOR
			//TODO: POINTED_DRIPSTONE
			//TODO: COPPER_ORE
			//TODO: LIGHTNING_ROD
			//TODO: DRIPSTONE_BLOCK
			//TODO: DIRT_WITH_ROOTS
			//TODO: HANGING_ROOTS
			//TODO: MOSS_BLOCK
			//TODO: SPORE_BLOSSOM
			//TODO: CAVE_VINES
			//TODO: BIG_DRIPLEAF
			//TODO: AZALEA_LEAVES
			//TODO: AZALEA_LEAVES_FLOWERED
			//TODO: CALCITE
			//TODO: AMETHYST_BLOCK
			//TODO: BUDDING_AMETHYST
			//TODO: AMETHYST_CLUSTER
			//TODO: LARGE_AMETHYST_BUD
			//TODO: MEDIUM_AMETHYST_BUD
			//TODO: SMALL_AMETHYST_BUD
			//TODO: TUFF
			//TODO: TINTED_GLASS
			//TODO: MOSS_CARPET
			//TODO: SMALL_DRIPLEAF_BLOCK
			//TODO: AZALEA
			//TODO: FLOWERING_AZALEA
			//TODO: GLOW_FRAME
			//TODO: COPPER_BLOCK
			//TODO: EXPOSED_COPPER
			//TODO: WEATHERED_COPPER
			//TODO: OXIDIZED_COPPER
			//TODO: WAXED_COPPER
			//TODO: WAXED_EXPOSED_COPPER
			//TODO: WAXED_WEATHERED_COPPER
			//TODO: CUT_COPPER
			//TODO: EXPOSED_CUT_COPPER
			//TODO: WEATHERED_CUT_COPPER
			//TODO: OXIDIZED_CUT_COPPER
			//TODO: WAXED_CUT_COPPER
			//TODO: WAXED_EXPOSED_CUT_COPPER
			//TODO: WAXED_WEATHERED_CUT_COPPER
			//TODO: CUT_COPPER_STAIRS
			//TODO: EXPOSED_CUT_COPPER_STAIRS
			//TODO: WEATHERED_CUT_COPPER_STAIRS
			//TODO: OXIDIZED_CUT_COPPER_STAIRS
			//TODO: WAXED_CUT_COPPER_STAIRS
			//TODO: WAXED_EXPOSED_CUT_COPPER_STAIRS
			//TODO: WAXED_WEATHERED_CUT_COPPER_STAIRS
			//TODO: OXIDIZED_DOUBLE_CUT_COPPER_SLAB
			//TODO: CUT_COPPER_SLAB
			//TODO: EXPOSED_CUT_COPPER_SLAB
			//TODO: WEATHERED_CUT_COPPER_SLAB
			//TODO: OXIDIZED_CUT_COPPER_SLAB
			//TODO: WAXED_CUT_COPPER_SLAB
			//TODO: WAXED_EXPOSED_CUT_COPPER_SLAB
			//TODO: WAXED_WEATHERED_CUT_COPPER_SLAB
			//TODO: DOUBLE_CUT_COPPER_SLAB
			//TODO: EXPOSED_DOUBLE_CUT_COPPER_SLAB
			//TODO: WEATHERED_DOUBLE_CUT_COPPER_SLAB
			//TODO: OXIDIZED_DOUBLE_CUT_COPPER_SLAB
			//TODO: WAXED_DOUBLE_CUT_COPPER_SLAB
			//TODO: WAXED_EXPOSED_DOUBLE_CUT_COPPER_SLAB
			//TODO: WAXED_WEATHERED_DOUBLE_CUT_COPPER_SLAB
			//TODO: CAVE_VINES_BODY_WITH_BERRIES
			//TODO: CAVE_VINES_HEAD_WITH_BERRIES
			//TODO: SMOOTH_BASALT
			//TODO: DEEPSLATE
			//TODO: COBBLED_DEEPSLATE
			//TODO: COBBLED_DEEPSLATE_SLAB
			//TODO: COBBLED_DEEPSLATE_STAIRS
			//TODO: COBBLED_DEEPSLATE_WALL
			//TODO: POLISHED_DEEPSLATE
			//TODO: POLISHED_DEEPSLATE_SLAB
			//TODO: POLISHED_DEEPSLATE_STAIRS
			//TODO: POLISHED_DEEPSLATE_WALL
			//TODO: DEEPSLATE_TILES
			//TODO: DEEPSLATE_TILE_SLAB
			//TODO: DEEPSLATE_TILE_STAIRS
			//TODO: DEEPSLATE_TILE_WALL
			//TODO: DEEPSLATE_BRICKS
			//TODO: DEEPSLATE_BRICK_SLAB
			//TODO: DEEPSLATE_BRICK_STAIRS
			//TODO: DEEPSLATE_BRICK_WALL
			//TODO: CHISELED_DEEPSLATE
			//TODO: COBBLED_DEEPSLATE_DOUBLE_SLAB
			//TODO: POLISHED_DEEPSLATE_DOUBLE_SLAB
			//TODO: DEEPSLATE_TILE_DOUBLE_SLAB
			//TODO: DEEPSLATE_BRICK_DOUBLE_SLAB
			//TODO: DEEPSLATE_LAPIS_ORE
			//TODO: DEEPSLATE_IRON_ORE
			//TODO: DEEPSLATE_GOLD_ORE
			//TODO: DEEPSLATE_REDSTONE_ORE
			//TODO: LIT_DEEPSLATE_REDSTONE_ORE
			//TODO: DEEPSLATE_DIAMOND_ORE
			//TODO: DEEPSLATE_COAL_ORE
			//TODO: DEEPSLATE_EMERALD_ORE
			//TODO: DEEPSLATE_COPPER_ORE
			//TODO: CRACKED_DEEPSLATE_TILES
			//TODO: CRACKED_DEEPSLATE_BRICKS
			//TODO: GLOW_LICHEN
			//TODO: CANDLE
			//TODO: WHITE_CANDLE
			//TODO: ORANGE_CANDLE
			//TODO: MAGENTA_CANDLE
			//TODO: LIGHT_BLUE_CANDLE
			//TODO: YELLOW_CANDLE
			//TODO: LIME_CANDLE
			//TODO: PINK_CANDLE
			//TODO: GRAY_CANDLE
			//TODO: LIGHT_GRAY_CANDLE
			//TODO: CYAN_CANDLE
			//TODO: PURPLE_CANDLE
			//TODO: BLUE_CANDLE
			//TODO: BROWN_CANDLE
			//TODO: GREEN_CANDLE
			//TODO: RED_CANDLE
			//TODO: BLACK_CANDLE
			//TODO: CANDLE_CAKE
			//TODO: WHITE_CANDLE_CAKE
			//TODO: ORANGE_CANDLE_CAKE
			//TODO: MAGENTA_CANDLE_CAKE
			//TODO: LIGHT_BLUE_CANDLE_CAKE
			//TODO: YELLOW_CANDLE_CAKE
			//TODO: LIME_CANDLE_CAKE
			//TODO: PINK_CANDLE_CAKE
			//TODO: GRAY_CANDLE_CAKE
			//TODO: LIGHT_GRAY_CANDLE_CAKE
			//TODO: CYAN_CANDLE_CAKE
			//TODO: PURPLE_CANDLE_CAKE
			//TODO: BLUE_CANDLE_CAKE
			//TODO: BROWN_CANDLE_CAKE
			//TODO: GREEN_CANDLE_CAKE
			//TODO: RED_CANDLE_CAKE
			//TODO: BLACK_CANDLE_CAKE
			//TODO: WAXED_OXIDIZED_COPPER
			//TODO: WAXED_OXIDIZED_CUT_COPPER
			//TODO: WAXED_OXIDIZED_CUT_COPPER_STAIRS
			//TODO: WAXED_OXIDIZED_CUT_COPPER_SLAB
			//TODO: WAXED_OXIDIZED_DOUBLE_CUT_COPPER_SLAB
			//TODO: RAW_IRON_BLOCK
			//TODO: RAW_COPPER_BLOCK
			//TODO: RAW_GOLD_BLOCK
			//TODO: INFESTED_DEEPSLATE
			//TODO: BAMBOO_DOOR
			//TODO: SCULK
			//TODO: SCULK_VEIN
			//TODO: SCULK_CATALYST
			//TODO: SCULK_SHRIEKER

			//TODO: CLIENT_REQUEST_PLACEHOLDER_BLOCK

			//TODO: FROG_SPAWN
			//TODO: PEARLESCENT_FROGLIGHT
			//TODO: VERDANT_FROGLIGHT
			//TODO: OCHRE_FROGLIGHT
			//TODO: MANGROVE_LEAVES
			//TODO: MANGROVE_PROPAGULE

			//TODO: MUD
			//TODO: MUD_BRICK_DOUBLE_SLAB
			//TODO: MUD_BRICK_SLAB
			//TODO: MUD_BRICK_STAIRS
			//TODO: MUD_BRICK_WALL
			//TODO: MUD_BRICKS
			//TODO: PACKED_MUD
			//TODO: REINFORCED_DEEPSLATE
			//TODO: MANGROVE_DOOR
			//TODO: MANGROVE_BUTTON
			//TODO: MANGROVE_DOUBLE_SLAB
			//TODO: MANGROVE_FENCE
			//TODO: MANGROVE_FENCE_GATE
			//TODO: MANGROVE_LOG
			//TODO: MANGROVE_PLANKS
			//TODO: MANGROVE_PRESSURE_PLATE
			//TODO: MANGROVE_ROOTS
			//TODO: MANGROVE_SLAB
			//TODO: MANGROVE_STAIRS
			//TODO: MANGROVE_STANDING_SIGN
			//TODO: MANGROVE_TRAPDOOR
			//TODO: MANGROVE_WALL_SIGN
			//TODO: MANGROVE_WOOD
			//TODO: MUDDY_MANGROVE_ROOTS
			//TODO: STRIPPED_MANGROVE_LOG
			//TODO: STRIPPED_MANGROVE_WOOD
			//TODO: BAMBOO_BUTTON
			//TODO: BAMBOO_DOUBLE_SLAB
			//TODO: BAMBOO_FENCE
			//TODO: BAMBOO_HANGING_SIGN
			//TODO: BAMBOO_MOSAIC
			//TODO: BAMBOO_MOSAIC_DOUBLE_SLAB
			//TODO: BAMBOO_MOSAIC_SLAB
			//TODO: BAMBOO_MOSAIC_STAIRS
			//TODO: BAMBOO_PLANKS
			//TODO: BAMBOO_PRESSURE_PLATE
			//TODO: BAMBOO_SLAB
			//TODO: BAMBOO_STAIRS
			//TODO: BAMBOO_STANDING_SIGN
			//TODO: BAMBOO_WALL_SIGN
			//TODO: BAMBOO_TRAPDOOR
			//TODO: BIRCH_HANGING_SIGN
			//TODO: CHISELED_BOOKSHELF
			//TODO: CRIMSON_HANGING_SIGN
			//TODO: DARK_OAK_HANGING_SIGN
			//TODO: JUNGLE_HANGING_SIGN
			//TODO: MANGROVE_HANGING_SIGN
			//TODO: OAK_HANGING_SIGN

			//TODO: WARPED_HANGING_SIGN
			//TODO: SPRUCE_HANGING_SIGN
			//TODO: BAMBOO_BLOCK
			//TODO: STRIPPED_BAMBOO_BLOCK
			//TODO: DECORATED_POT
			//TODO: SUSPICIOUS_SAND
			//TODO: TORCHFLOWER
			//TODO: TORCHFLOWER_CROP
			//TODO: CALIBRATED_SCULK_SENSOR
			//TODO: CHERRY_BUTTON
			//TODO: CHERRY_DOUBLE_SLAB
			//TODO: CHERRY_FENCE
			//TODO: CHERRY_FENCE_GATE
			//TODO: CHERRY_HANGING_SIGN
			//TODO: CHERRY_LEAVES
			//TODO: CHERRY_LOG
			//TODO: CHERRY_PLANKS
			//TODO: CHERRY_PRESSURE_PLATE
			//TODO: CHERRY_SAPLING
			//TODO: CHERRY_SLAB
			//TODO: CHERRY_STAIRS
			//TODO: CHERRY_STANDING_SIGN
			//TODO: CHERRY_TRAPDOOR
			//TODO: CHERRY_WALL_SIGN
			//TODO: CHERRY_WOOD
			//TODO: PINK_PETALS
			//TODO: STRIPPED_CHERRY_WOOD
			//TODO: STRIPPED_CHERRY_LOG
			//TODO: SUSPICIOUS_GRAVEL
			//TODO: PITCHER_CROP
			//TODO: PITCHER_PLANT
			//TODO: SNIFFER_EGG
			self::registerBlock(new ChiseledCopper(BlockIds::CHISELED_COPPER, 0, "Chiseled Copper"));
			//TODO: CHISELED_TUFF
			//TODO: CHISELED_TUFF_BRICKS
			//TODO: COPPER_BULB
			self::registerBlock(new CopperGrate(BlockIds::COPPER_GRATE, 0, "Copper Grate"));
			self::registerBlock(new CopperTrapdoor(BlockIds::COPPER_TRAPDOOR, 0, "Copper Trapdoor"));
			//TODO: CRAFTER
			self::registerBlock(new ChiseledCopper(BlockIds::EXPOSED_CHISELED_COPPER, 0, "Exposed Chiseled Copper"));
			//TODO: EXPOSED_COPPER_BULB
			self::registerBlock(new CopperGrate(BlockIds::EXPOSED_COPPER_GRATE, 0, "Exposed Copper Grate"));
			self::registerBlock(new CopperTrapdoor(BlockIds::EXPOSED_COPPER_TRAPDOOR, 0, "Exposed Copper Trapdoor"));
			self::registerBlock(new ChiseledCopper(BlockIds::OXIDIZED_CHISELED_COPPER, 0, "Oxidized Chiseled Copper"));
			//TODO: OXIDIZED_COPPER_BULB
			self::registerBlock(new CopperGrate(BlockIds::OXIDIZED_COPPER_GRATE, 0, "Oxidized Copper Grate"));
			self::registerBlock(new CopperTrapdoor(BlockIds::OXIDIZED_COPPER_TRAPDOOR, 0, "Oxidized Copper Trapdoor"));
			//TODO: POLISHED_TUFF
			//TODO: POLISHED_TUFF_DOUBLE_SLAB
			//TODO: POLISHED_TUFF_SLAB
			//TODO: POLISHED_TUFF_STAIRS
			//TODO: POLISHED_TUFF_WALL
			//TODO: TUFF_BRICK_DOUBLE_SLAB
			//TODO: TUFF_BRICK_SLAB
			//TODO: TUFF_BRICK_STAIRS
			//TODO: TUFF_BRICK_WALL
			//TODO: TUFF_BRICKS
			//TODO: TUFF_SLAB
			//TODO: TUFF_STAIRS
			//TODO: TUFF_WALL
			self::registerBlock(new ChiseledCopper(BlockIds::WAXED_CHISELED_COPPER, 0, "Waxed Chiseled Copper"));
			//TODO: WAXED_COPPER_BULB
			self::registerBlock(new CopperGrate(BlockIds::WAXED_COPPER_GRATE, 0, "Waxed Copper Grate"));
			self::registerBlock(new CopperTrapdoor(BlockIds::WAXED_COPPER_TRAPDOOR, 0, "Waxed Copper Trapdoor"));
			self::registerBlock(new ChiseledCopper(BlockIds::WAXED_EXPOSED_CHISELED_COPPER, 0, "Waxed Exposed Chiseled Copper"));
			//TODO: WAXED_EXPOSED_COPPER_BULB
			self::registerBlock(new CopperGrate(BlockIds::WAXED_EXPOSED_COPPER_GRATE, 0, "Waxed Exposed Copper Grate"));
			self::registerBlock(new CopperTrapdoor(BlockIds::WAXED_EXPOSED_COPPER_TRAPDOOR, 0, "Waxed Exposed Copper Trapdoor"));
			self::registerBlock(new ChiseledCopper(BlockIds::WAXED_OXIDIZED_CHISELED_COPPER, 0, "Waxed Oxidized Chiseled Copper"));
			//TODO: WAXED_OXIDIZED_COPPER_BULB
			self::registerBlock(new CopperGrate(BlockIds::WAXED_OXIDIZED_COPPER_GRATE, 0, "Waxed Oxidized Copper Grate"));
			self::registerBlock(new CopperTrapdoor(BlockIds::WAXED_OXIDIZED_COPPER_TRAPDOOR, 0, "Waxed Oxidized Copper Trapdoor"));
			self::registerBlock(new ChiseledCopper(BlockIds::WAXED_WEATHERED_CHISELED_COPPER, 0, "Waxed Weathered Chiseled Copper"));
			//TODO: WAXED_WEATHERED_COPPER_BULB
			self::registerBlock(new CopperGrate(BlockIds::WAXED_WEATHERED_COPPER_GRATE, 0, "Waxed Weathered Copper Grate"));
			self::registerBlock(new CopperTrapdoor(BlockIds::WAXED_WEATHERED_COPPER_TRAPDOOR, 0, "Waxed Weathered Copper Trapdoor"));
			self::registerBlock(new ChiseledCopper(BlockIds::WEATHERED_CHISELED_COPPER, 0, "Weathered Chiseled Copper"));
			//TODO: WEATHERED_COPPER_BULB
			self::registerBlock(new CopperGrate(BlockIds::WEATHERED_COPPER_GRATE, 0, "Weathered Copper Grate"));
			self::registerBlock(new CopperTrapdoor(BlockIds::WEATHERED_COPPER_TRAPDOOR, 0, "Weathered Copper Trapdoor"));
			self::registerBlock(new CopperDoor(BlockIds::WEATHERED_COPPER_DOOR, 0, "Weathered Copper Door"));
			self::registerBlock(new CopperDoor(BlockIds::WAXED_WEATHERED_COPPER_DOOR, 0, "Waxed Weathered Copper Door"));
			self::registerBlock(new CopperDoor(BlockIds::WAXED_OXIDIZED_COPPER_DOOR, 0, "Waxed Oxidized Copper Door"));
			self::registerBlock(new CopperDoor(BlockIds::WAXED_EXPOSED_COPPER_DOOR, 0, "Waxed Exposed Copper Door"));
			self::registerBlock(new CopperDoor(BlockIds::WAXED_COPPER_DOOR, 0, "Waxed Copper Door"));
			self::registerBlock(new CopperDoor(BlockIds::OXIDIZED_COPPER_DOOR, 0, "Oxidized Copper Door"));
			self::registerBlock(new CopperDoor(BlockIds::EXPOSED_COPPER_DOOR, 0, "Exposed Copper Door"));
			self::registerBlock(new CopperDoor(BlockIds::COPPER_DOOR, 0, "Copper Door"));
			self::registerBlock(new CherryDoor());
			//TODO: RESIN_CLUMP
			//TODO: ACACIA_HANGING_SIGN

			//TODO: TRIAL_SPAWNER
			//TODO: VAULT
			self::registerBlock(new HeavyCore());
			//TODO: DEPRECATED_ANVIL
			//TODO: MUSHROOM_STEM

			self::registerBlock(new DriedGhast());
			self::registerBlock(new ChiseledResinBricks());
			self::registerBlock(new ClosedEyeblossom());
			self::registerBlock(new CreakingHeart());
			self::registerBlock(new OpenEyeblossom());
			self::registerBlock(new PaleHangingMoss());
			self::registerBlock(new PaleMoss());
			self::registerBlock(new PaleMossCarpet());
			self::registerBlock(new PaleOakButton());
			self::registerBlock(new PaleOakDoor());
			self::registerBlock(new PaleOakDoubleSlab());
			self::registerBlock(new PaleOakFence());
			self::registerBlock(new PaleOakFenceGate());
			//TODO: PALE_OAK_HANGING_SIGN
			self::registerBlock(new PaleOakLeaves());
			self::registerBlock(new PaleOakLog());
			self::registerBlock(new PaleOakPlanks());
			self::registerBlock(new PaleOakPressurePlate());
			self::registerBlock(new PaleOakSapling());
			self::registerBlock(new PaleOakSlab());
			self::registerBlock(new PaleOakStairs());
			self::registerBlock(new SignPost(Block::PALE_OAK_STANDING_SIGN, 0, "Pale Oak Sign Post", Item::PALE_OAK_SIGN, Block::PALE_OAK_WALL_SIGN));
			self::registerBlock(new PaleOakTrapdoor());
			self::registerBlock(new WallSign(Block::PALE_OAK_WALL_SIGN, 0, "Pale Oak Sign Post", Item::PALE_OAK_SIGN, Block::PALE_OAK_WALL_SIGN));
			self::registerBlock(new PaleOakWood());
			self::registerBlock(new Resin());
			self::registerBlock(new ResinBrickDoubleSlab());
			self::registerBlock(new ResinBrickSlab());
			self::registerBlock(new ResinBrickStairs());
			self::registerBlock(new ResinBricks());
			self::registerBlock(new StrippedPaleOakLog());
			self::registerBlock(new StrippedPaleOakWood());
			//TODO: RESIN_BRICK_WALL
			self::registerBlock(new Bush());
			self::registerBlock(new CactusFlower());
			self::registerBlock(new FireflyBush());
			self::registerBlock(new LeafLitter());
			self::registerBlock(new ShortDryGrass());
			self::registerBlock(new TallDryGrass());
			self::registerBlock(new Wildflowers());
			//TODO: COPPER_CHEST
			//TODO: EXPOSED_COPPER_CHEST
			//TODO: OXIDIZED_COPPER_CHEST
			//TODO: WAXED_COPPER_CHEST
			//TODO: WAXED_EXPOSED_COPPER_CHEST
			//TODO: WAXED_OXIDDIZED_COPPER_CHEST
			//TODO: WAXED_WEATHERED_COPPER_CHEST
			//TODO: WEATHERED_COPPER_CHEST

			for ($id = 0, $size = self::$fullList->getSize() >> Block::INTERNAL_METADATA_BITS; $id < $size; ++$id) {
				if (self::$fullList[$id << Block::INTERNAL_METADATA_BITS] === null) {
					self::registerBlock(new UnknownBlock($id));
				}
			}
		}
	}

	public static function isInit() : bool
	{
		return self::$fullList !== null;
	}

	/**
	 * Registers a block type into the index. Plugins may use this method to register new block types or override
	 * existing ones.
	 *
	 * NOTE: If you are registering a new block type, you will need to add it to the creative inventory yourself - it
	 * will not automatically appear there.
	 *
	 * @param bool $override Whether to override existing registrations
	 *
	 * @throws RuntimeException if something attempted to override an already-registered block without specifying the
	 * $override parameter.
	 */
	public static function registerBlock(Block $block, bool $override = false) : void
	{
		$id = $block->getId();
		$meta = $block->getDamage();

		if (!$override && self::isRegistered($id, $meta)) {
			throw new RuntimeException("Trying to overwrite an already registered block");
		}

		for ($meta = 0; $meta < 16; ++$meta) {
			$variant = clone $block;
			$variant->setDamage($meta);

			self::fillStaticArrays($variant->getFullId(), $variant);
		}
	}

	private static function fillStaticArrays(int $index, Block $block) : void
	{
		self::$fullList[$index] = $block;
		self::$mappedStateIds[$index] = $block->getFullId();
		self::$light[$index] = $block->getLightLevel();
		self::$lightFilter[$index] = min(15, $block->getLightFilter() + 1); //opacity plus 1 standard light filter
		self::$diffusesSkyLight[$index] = $block->diffusesSkyLight();
		self::$blastResistance[$index] = $block->getBlastResistance();
		self::$hasEntityCollision[$index] = $block->hasEntityCollision();
	}

	/**
	 * Returns a new Block instance with the specified ID, meta and position.
	 */
	public static function get(int $id, int $meta = 0, Position $pos = null) : Block
	{
		if ($meta < 0 || $meta > 0xf) {
			$meta = 0;
		}

		try {
			if (self::$fullList === null) {
				$block = new UnknownBlock($id, $meta);
			} else {
				$block = clone (self::$fullList[self::getListOffset($id, $meta)] ?? new UnknownBlock($id, $meta));
			}
		} catch (RuntimeException $e) {
			throw new InvalidArgumentException("Block ID $id is out of bounds");
		}

		if ($pos !== null) {
			$block->x = $pos->getFloorX();
			$block->y = $pos->getFloorY();
			$block->z = $pos->getFloorZ();
			$block->level = $pos->level;
		}

		return $block;
	}

	public static function fromFullBlock(int $fullState, Position $pos = null) : Block
	{
		return self::get($fullState >> Block::INTERNAL_METADATA_BITS, $fullState & Block::INTERNAL_METADATA_MASK, $pos);
	}

	/**
	 * Returns whether a specified block state is already registered in the block factory.
	 */
	public static function isRegistered(int $id, int $meta = 0) : bool
	{
		$b = self::$fullList[self::getListOffset($id, $meta)];
		return $b !== null && !($b instanceof UnknownBlock);
	}

	/**
	 * @return Block[]
	 */
	public static function getAllKnownStates() : array
	{
		return array_filter(self::$fullList->toArray(), function (?Block $v) : bool { return $v !== null; });
	}

	/**
	 * Returns the ID of the state mapped to the given state ID.
	 * Used to correct invalid blockstates found in loaded chunks.
	 */
	public static function getMappedStateId(int $fullState) : int
	{
		return self::$mappedStateIds[$fullState] ?? $fullState;
	}

	public static function getListOffset(int $id, int $meta) : int{
		return $id << Block::INTERNAL_METADATA_BITS | $meta;
	}
}
