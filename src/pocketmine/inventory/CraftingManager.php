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

namespace pocketmine\inventory;

use Generator;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\nbt\LittleEndianNBTStream;
use pocketmine\network\mcpe\compression\NetworkCompression;
use pocketmine\network\mcpe\protocol\CraftingDataPacket;
use pocketmine\network\mcpe\protocol\serializer\PacketBatch;
use pocketmine\network\mcpe\protocol\types\PotionContainerChangeRecipe as ProtocolPotionContainerChangeRecipe;
use pocketmine\network\mcpe\protocol\types\PotionTypeRecipe as ProtocolPotionTypeRecipe;
use pocketmine\timings\Timings;
use pocketmine\utils\AssumptionFailedError;
use pocketmine\utils\BinaryStream;
use function array_key_exists;
use function array_map;
use function count;
use function file_get_contents;
use function is_array;
use function json_decode;
use function usort;
use const pocketmine\BEDROCK_DATA_PATH;

class CraftingManager
{

	/**
	 * @param Item[] $items
	 */
	private static function containsUnknownOutputs(array $items) : bool
	{
		foreach ($items as $item) {
			if ($item->hasAnyDamageValue()) {
				throw new \InvalidArgumentException("Recipe outputs must not have wildcard meta values");
			}
			if (!ItemFactory::isRegistered($item->getId(), $item->getDamage())) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @var ShapedRecipe[][]
	 * @phpstan-var array<string, list<ShapedRecipe>>
	 */
	private static array $shapedRecipes = [];
	/**
	 * @var ShapelessRecipe[][]
	 * @phpstan-var array<string, list<ShapelessRecipe>>
	 */
	private static array $shapelessRecipes = [];
	/** @var FurnaceRecipe[] */
	private static array $furnaceRecipes = [];

	/**
	 * @var PotionTypeRecipe[][]
	 * @phpstan-var array<string, array<string, PotionTypeRecipe>>
	 */
	private static array $potionTypeRecipes = [];
	/**
	 * @var PotionContainerChangeRecipe[][]
	 * @phpstan-var array<int, array<string, PotionContainerChangeRecipe>>
	 */
	private static array $potionContainerChangeRecipes = [];

	/** @var string[] */
	private static array $craftingDataCache = [];

	public static function init() : void{
		$recipes = json_decode(file_get_contents(BEDROCK_DATA_PATH . "recipes.json"), true);
		if (!is_array($recipes)) {
			throw new AssumptionFailedError("recipes.json root should contain a map of recipe types");
		}

		$itemDeserializerFunc = Item::jsonDeserialize(...);

		foreach ($recipes["shapeless"] as $recipe) {
			if ($recipe["block"] !== "crafting_table") { //TODO: filter others out for now to avoid breaking economics
				continue;
			}
			$output = array_map($itemDeserializerFunc, $recipe["output"]);
			if (self::containsUnknownOutputs($output)) {
				continue;
			}
			self::registerShapelessRecipe(new ShapelessRecipe(
				array_map($itemDeserializerFunc, $recipe["input"]),
				$output,
				$recipe["priority"]
			));
		}
		foreach ($recipes["shaped"] as $recipe) {
			if ($recipe["block"] !== "crafting_table") { //TODO: filter others out for now to avoid breaking economics
				continue;
			}
			$output = array_map($itemDeserializerFunc, $recipe["output"]);
			if (self::containsUnknownOutputs($output)) {
				continue;
			}
			$ingredients = array_map($itemDeserializerFunc, $recipe["input"]);
			/** @var Item[] $ingredients */
			foreach ($ingredients as $ingredient) {
				if ($ingredient->getId() === ItemIds::PLANKS && $ingredient->getDamage() === -1) {

					//TODO: crutch planks > 1.20.50

					for ($meta = 0; $meta <= 5; ++$meta) {
						$fixIngredients = array_map($itemDeserializerFunc, $recipe["input"]);
						foreach ($ingredients as $key => $fixIngredient) {
							if ($fixIngredient->getId() === ItemIds::PLANKS && $fixIngredient->getDamage() === -1) {
								$fixIngredients[$key]->setDamage($meta);
							}
						}

						self::registerShapedRecipe(new ShapedRecipe(
							$recipe["shape"],
							$fixIngredients,
							$output,
							$recipe["priority"]
						));
					}

					//TODO: end crutch

					continue 2;
				}
			}

			self::registerShapedRecipe(new ShapedRecipe(
				$recipe["shape"],
				$ingredients,
				$output,
				$recipe["priority"]
			));
		}
		foreach ($recipes["smelting"] as $recipe) {
			if ($recipe["block"] !== "furnace") {
				continue;
			}
			$output = Item::jsonDeserialize($recipe["output"]);
			if (self::containsUnknownOutputs([$output])) {
				continue;
			}
			self::registerFurnaceRecipe(
				new FurnaceRecipe(
					$output,
					Item::jsonDeserialize($recipe["input"])
				)
			);
		}
		foreach ($recipes["potion_type"] as $recipe) {
			$output = Item::jsonDeserialize($recipe["output"]);
			if (self::containsUnknownOutputs([$output])) {
				continue;
			}
			self::registerPotionTypeRecipe(new PotionTypeRecipe(
				Item::jsonDeserialize($recipe["input"]),
				Item::jsonDeserialize($recipe["ingredient"]),
				$output
			));
		}
		foreach ($recipes["potion_container_change"] as $recipe) {
			if (!ItemFactory::isRegistered($recipe["output_item_id"])) {
				continue;
			}
			self::registerPotionContainerChangeRecipe(new PotionContainerChangeRecipe(
				$recipe["input_item_id"],
				Item::jsonDeserialize($recipe["ingredient"]),
				$recipe["output_item_id"]
			));
		}
	}

	public static function sync(array $newItems) : void{
		$sources = [
			&self::$shapedRecipes,
			&self::$shapelessRecipes,
			//TODO:
			#&self::$furnaceRecipes,
			#&self::$potionContainerChangeRecipes,
			#&self::$potionTypeRecipes
		];

		foreach ($sources as &$source) {
			foreach ($source as $hash => $list) {
				/** @var BrewingRecipe $recipe */
				foreach ($list as $recipeIdx => $recipe) {
					if ($recipe instanceof CraftingRecipe) {
						$ingredients = ($recipe instanceof ShapedRecipe) ? $recipe->getIngredientRawList() : $recipe->getIngredientList();

						foreach ($ingredients as $idx => $ingredient) {
							if (($new = $newItems[ItemFactory::getListOffset($ingredient->getId(), $ingredient->getDamage())] ?? null) !== null) {
								$recipe->setIngredient($idx, $new);
							}
						}

						$changes = [];

						foreach ($recipe->getResults() as $idx => $item) {
							if (($new = $newItems[ItemFactory::getListOffset($item->getId(), $item->getDamage())] ?? null) !== null) {
								$changes[$idx] = $new;
							}
						}

						if (count($changes) > 0) {
							$recipe->setResults($changes + $recipe->getResults());

							unset($source[$hash][$recipeIdx]);

							$source[self::hashOutputs($recipe->getResults())][] = $recipe;
						}
					}
				}
			}
		}
	}

	private static function buildCache(int $protocolVersion) : void
	{
		Timings::$craftingDataCacheRebuild->startTiming();

		$pk = new CraftingDataPacket();
		foreach (self::$shapelessRecipes as $list) {
			foreach ($list as $recipe) {
				$pk->addShapelessRecipe($recipe);
			}
		}

		foreach (self::$shapedRecipes as $list) {
			foreach ($list as $recipe) {
				$pk->addShapedRecipe($recipe);
			}
		}

		foreach (self::$furnaceRecipes as $recipe) {
			$pk->addFurnaceRecipe($recipe);
		}

		foreach (self::$potionTypeRecipes as $recipes) {
			foreach ($recipes as $recipe) {
				$input = $recipe->getInput();
				$ingredient = $recipe->getIngredient();
				$output = $recipe->getOutput();

				$pk->potionTypeRecipes[] = new ProtocolPotionTypeRecipe(
					$input->getId(),
					$input->getDamage(),
					$ingredient->getId(),
					$ingredient->getDamage(),
					$output->getId(),
					$output->getDamage(),
				);
			}
		}

		foreach (self::$potionContainerChangeRecipes as $recipes) {
			foreach ($recipes as $recipe) {
				$ingredient = $recipe->getIngredient();

				$pk->potionContainerRecipes[] = new ProtocolPotionContainerChangeRecipe(
					$recipe->getInputItemId(),
					$ingredient->getId(),
					$recipe->getInputItemId()
				);
			}
		}

		$pk->cleanRecipes = true;

		$stream = new BinaryStream();
		PacketBatch::encodePackets($stream, [$pk], $protocolVersion);

		self::$craftingDataCache[$protocolVersion] = NetworkCompression::compress($stream->getBuffer(), $protocolVersion);
		Timings::$craftingDataCacheRebuild->stopTiming();
	}

	public static function getCraftingDataPacket(int $craftingProtocol) : ?string
	{
		if (!array_key_exists($craftingProtocol, self::$craftingDataCache)) {
			self::buildCache($craftingProtocol);
		}
		return self::$craftingDataCache[$craftingProtocol];
	}

	/**
	 * Function used to arrange Shapeless Recipe ingredient lists into a consistent order.
	 */
	public static function sort(Item $i1, Item $i2) : int
	{
		//Use spaceship operator to compare each property, then try the next one if they are equivalent.
		($retval = $i1->getId() <=> $i2->getId()) === 0 && ($retval = $i1->getDamage() <=> $i2->getDamage()) === 0 && ($retval = $i1->getCount() <=> $i2->getCount());

		return $retval;
	}

	/**
	 * @param Item[] $items
	 *
	 * @return Item[]
	 */
	private static function pack(array $items) : array
	{
		/** @var Item[] $result */
		$result = [];

		foreach ($items as $i => $item) {
			foreach ($result as $otherItem) {
				if ($item->equals($otherItem)) {
					$otherItem->setCount($otherItem->getCount() + $item->getCount());
					continue 2;
				}
			}

			//No matching item found
			$result[] = clone $item;
		}

		return $result;
	}

	private static function hashOutputs(array $outputs) : string
	{
		$outputs = self::pack($outputs);
		usort($outputs, [self::class, "sort"]);
		$result = new BinaryStream();

		foreach ($outputs as $o) {
			//count is not written because the outputs might be from multiple repetitions of a single recipe
			//this reduces the accuracy of the hash, but it won't matter in most cases.
			$result->putVarInt($o->getId());
			$result->putVarInt($o->getDamage());

			$result->put((new LittleEndianNBTStream())->write($o->getNamedTag()->ksort()));
		}

		return $result->getBuffer();
	}

	/**
	 * @return ShapelessRecipe[][]
	 * @phpstan-return array<string, list<ShapelessRecipe>>
	 */
	public static function getShapelessRecipes() : array
	{
		return self::$shapelessRecipes;
	}

	/**
	 * @return ShapedRecipe[][]
	 * @phpstan-return array<string, list<ShapedRecipe>>
	 */
	public static function getShapedRecipes() : array
	{
		return self::$shapedRecipes;
	}

	/**
	 * @return FurnaceRecipe[]
	 * @phpstan-return array<string, FurnaceRecipe>
	 */
	public static function getFurnaceRecipes() : array
	{
		return self::$furnaceRecipes;
	}

	/**
	 * @return PotionTypeRecipe[][]
	 * @phpstan-return array<string, array<string, PotionTypeRecipe>>
	 */
	public static function getPotionTypeRecipes() : array
	{
		return self::$potionTypeRecipes;
	}

	/**
	 * @return PotionContainerChangeRecipe[][]
	 * @phpstan-return array<int, array<string, PotionContainerChangeRecipe>>
	 */
	public static function getPotionContainerChangeRecipes() : array
	{
		return self::$potionContainerChangeRecipes;
	}

	public static function registerShapedRecipe(ShapedRecipe $recipe) : void
	{
		self::$shapedRecipes[self::hashOutputs($recipe->getResults())][] = $recipe;

		self::$craftingDataCache = [];
	}

	public static function registerShapelessRecipe(ShapelessRecipe $recipe) : void
	{
		self::$shapelessRecipes[self::hashOutputs($recipe->getResults())][] = $recipe;

		self::$craftingDataCache = [];
	}

	public static function registerFurnaceRecipe(FurnaceRecipe $recipe) : void
	{
		$input = $recipe->getInput();
		self::$furnaceRecipes[$input->getId() . ":" . ($input->hasAnyDamageValue() ? "?" : $input->getDamage())] = $recipe;

		self::$craftingDataCache = [];
	}

	public static function registerPotionTypeRecipe(PotionTypeRecipe $recipe) : void
	{
		$input = $recipe->getInput();
		$ingredient = $recipe->getIngredient();
		self::$potionTypeRecipes[$input->getId() . ":" . $input->getDamage()][$ingredient->getId() . ":" . ($ingredient->hasAnyDamageValue() ? "?" : $ingredient->getDamage())] = $recipe;

		self::$craftingDataCache = [];
	}

	public static function registerPotionContainerChangeRecipe(PotionContainerChangeRecipe $recipe) : void
	{
		$ingredient = $recipe->getIngredient();
		self::$potionContainerChangeRecipes[$recipe->getInputItemId()][$ingredient->getId() . ":" . ($ingredient->hasAnyDamageValue() ? "?" : $ingredient->getDamage())] = $recipe;

		self::$craftingDataCache = [];
	}

	/**
	 * @param Item[] $outputs
	 */
	public static function matchRecipe(CraftingGrid $grid, array $outputs) : ?CraftingRecipe
	{
		//TODO: try to match special recipes before anything else (first they need to be implemented!)

		$outputHash = self::hashOutputs($outputs);

		if (isset(self::$shapedRecipes[$outputHash])) {
			foreach (self::$shapedRecipes[$outputHash] as $recipe) {
				if ($recipe->matchesCraftingGrid($grid)) {
					return $recipe;
				}
			}
		}

		if (isset(self::$shapelessRecipes[$outputHash])) {
			foreach (self::$shapelessRecipes[$outputHash] as $recipe) {
				if ($recipe->matchesCraftingGrid($grid)) {
					return $recipe;
				}
			}
		}

		return null;
	}

	/**
	 * @param Item[] $outputs
	 */
	public static function matchRecipeByOutputs(array $outputs) : Generator
	{
		//TODO: try to match special recipes before anything else (first they need to be implemented!)

		$outputHash = self::hashOutputs($outputs);

		if (isset(self::$shapedRecipes[$outputHash])) {
			foreach (self::$shapedRecipes[$outputHash] as $recipe) {
				yield $recipe;
			}
		}

		if (isset(self::$shapelessRecipes[$outputHash])) {
			foreach (self::$shapelessRecipes[$outputHash] as $recipe) {
				yield $recipe;
			}
		}
	}

	public static function matchFurnaceRecipe(Item $input) : ?FurnaceRecipe
	{
		return self::$furnaceRecipes[$input->getId() . ":" . $input->getDamage()] ?? self::$furnaceRecipes[$input->getId() . ":?"] ?? null;
	}

	public static function matchBrewingRecipe(Item $input, Item $ingredient) : ?BrewingRecipe
	{
		return self::$potionTypeRecipes[$input->getId() . ":" . $input->getDamage()][$ingredient->getId() . ":" . $ingredient->getDamage()] ??
			self::$potionTypeRecipes[$input->getId() . ":" . $input->getDamage()][$ingredient->getId() . ":?"] ??
			self::$potionContainerChangeRecipes[$input->getId()][$ingredient->getId() . ":" . $ingredient->getDamage()] ??
			self::$potionContainerChangeRecipes[$input->getId()][$ingredient->getId() . ":?"] ?? null;
	}
}
