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

use pocketmine\inventory\FurnaceRecipe;
use pocketmine\inventory\ShapedRecipe;
use pocketmine\inventory\ShapelessRecipe;
use pocketmine\item\ItemFactory;
use pocketmine\network\mcpe\convert\ItemTranslator;
use pocketmine\network\mcpe\NetworkBinaryStream;
use pocketmine\network\mcpe\NetworkSession;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStackWrapper;
use pocketmine\network\mcpe\protocol\types\PotionContainerChangeRecipe;
use pocketmine\network\mcpe\protocol\types\PotionTypeRecipe;
use pocketmine\network\mcpe\protocol\types\recipe\MaterialReducerRecipe;
use pocketmine\network\mcpe\protocol\types\recipe\MaterialReducerRecipeOutput;
use pocketmine\utils\Binary;

use function count;
use function str_repeat;

class CraftingDataPacket extends DataPacket
{
	public const NETWORK_ID = ProtocolInfo::CRAFTING_DATA_PACKET;

	public const ENTRY_SHAPELESS = 0;
	public const ENTRY_SHAPED = 1;
	public const ENTRY_FURNACE = 2;
	public const ENTRY_FURNACE_DATA = 3;
	public const ENTRY_MULTI = 4; //TODO
	public const ENTRY_SHULKER_BOX = 5; //TODO
	public const ENTRY_USER_DATA_SHAPELESS = 5; //TODO
	public const ENTRY_SHAPELESS_CHEMISTRY = 6; //TODO
	public const ENTRY_SHAPED_CHEMISTRY = 7; //TODO

	/** @var object[] */
	public $entries = [];
	/** @var PotionTypeRecipe[] */
	public $potionTypeRecipes = [];
	/** @var PotionContainerChangeRecipe[] */
	public $potionContainerRecipes = [];
	/** @var MaterialReducerRecipe[] */
	public $materialReducerRecipes = [];
	/** @var bool */
	public $cleanRecipes = false;

	public $decodedEntries = [];

	protected function decodePayload() : void
	{
		$this->decodedEntries = [];
		$recipeCount = $this->getUnsignedVarInt();
		for ($i = 0; $i < $recipeCount; ++$i) {
			$entry = [];
			$entry["type"] = $recipeType = $this->getVarInt();

			switch ($recipeType) {
				case self::ENTRY_SHAPELESS:
				case self::ENTRY_SHULKER_BOX:
				case self::ENTRY_USER_DATA_SHAPELESS:
				case self::ENTRY_SHAPELESS_CHEMISTRY:
					if ($this->getProtocol() >= ProtocolInfo::PROTOCOL_361) {
						$entry["recipe_id"] = $this->getString();
					}
					$ingredientCount = $this->getUnsignedVarInt();
					$entry["input"] = [];
					for ($j = 0; $j < $ingredientCount; ++$j) {
						if ($this->getProtocol() >= ProtocolInfo::PROTOCOL_361) {
							$entry["input"][] = $in = $this->getRecipeIngredient($this->getProtocol());
							$in->setCount(1); //TODO HACK: they send a useless count field which breaks the PM crafting system because it isn't always 1
						} else {
							$entry["input"][] = $this->getSlot($this->getProtocol(), false)->getItemStack();
						}
					}
					$resultCount = $this->getUnsignedVarInt();
					$entry["output"] = [];
					for ($k = 0; $k < $resultCount; ++$k) {
						$entry["output"][] = $this->getSlot($this->getProtocol(), false)->getItemStack();
					}
					$entry["uuid"] = $this->getUUID()->toString();
					if ($this->getProtocol() >= ProtocolInfo::PROTOCOL_354) {
						$entry["block"] = $this->getString();
						if ($this->getProtocol() >= ProtocolInfo::PROTOCOL_361) {
							$entry["priority"] = $this->getVarInt();
							if ($this->getProtocol() >= ProtocolInfo::PROTOCOL_685) {
								$unlockingContext = $this->getBool();
								if (!$unlockingContext) {
									for ($i = 0, $count = $this->getUnsignedVarInt(); $i < $count; $i++) {
										$this->getRecipeIngredient($this->getProtocol());
									}
								}
							}
							if ($this->getProtocol() >= ProtocolInfo::PROTOCOL_407) {
								$entry["net_id"] = $this->readRecipeNetId();
							}
						}
					}

					break;
				case self::ENTRY_SHAPED:
				case self::ENTRY_SHAPED_CHEMISTRY:
					if ($this->getProtocol() >= ProtocolInfo::PROTOCOL_361) {
						$entry["recipe_id"] = $this->getString();
					}
					$entry["width"] = $this->getVarInt();
					$entry["height"] = $this->getVarInt();
					$count = $entry["width"] * $entry["height"];
					$entry["input"] = [];
					for ($j = 0; $j < $count; ++$j) {
						if ($this->getProtocol() >= ProtocolInfo::PROTOCOL_361) {
							$entry["input"][] = $in = $this->getRecipeIngredient($this->getProtocol());
							$in->setCount(1); //TODO HACK: they send a useless count field which breaks the PM crafting system
						} else {
							$entry["input"][] = $this->getSlot($this->getProtocol(), false)->getItemStack();
						}
					}
					$resultCount = $this->getUnsignedVarInt();
					$entry["output"] = [];
					for ($k = 0; $k < $resultCount; ++$k) {
						$entry["output"][] = $this->getSlot($this->getProtocol(), false)->getItemStack();
					}
					$entry["uuid"] = $this->getUUID()->toString();
					if ($this->getProtocol() >= ProtocolInfo::PROTOCOL_354) {
						$entry["block"] = $this->getString();
						if ($this->getProtocol() >= ProtocolInfo::PROTOCOL_361) {
							$entry["priority"] = $this->getVarInt();
							if ($this->getProtocol() >= ProtocolInfo::PROTOCOL_407) {
								if ($this->getProtocol() >= ProtocolInfo::PROTOCOL_671) {
									$entry["symmetric"] = $this->getBool();
									if ($this->getProtocol() >= ProtocolInfo::PROTOCOL_685) {
										$unlockingContext = $this->getBool();
										if (!$unlockingContext) {
											for ($i = 0, $count = $this->getUnsignedVarInt(); $i < $count; $i++) {
												$this->getRecipeIngredient($this->getProtocol());
											}
										}
									}
								}
								$entry["net_id"] = $this->readRecipeNetId();
							}
						}
					}

					break;
				case self::ENTRY_FURNACE:
				case self::ENTRY_FURNACE_DATA:
					$inputId = $this->getVarInt();
					if ($this->getProtocol() >= ProtocolInfo::PROTOCOL_419) {
						$itemTranslator = ItemTranslator::getInstance($this->getProtocol());
						if ($recipeType === self::ENTRY_FURNACE) {
							[$inputId, $inputData] = $itemTranslator->fromNetworkIdWithWildcardHandling($inputId, 0x7fff);
						} else {
							$inputData = $this->getVarInt();
							[$inputId, $inputData] = $itemTranslator->fromNetworkIdWithWildcardHandling($inputId, $inputData);
						}
					} else {
						$inputData = -1;
						if ($recipeType === self::ENTRY_FURNACE_DATA) {
							$inputData = $this->getVarInt();
							if ($inputData === 0x7fff) {
								$inputData = -1;
							}
						}
					}
					$entry["input"] = ItemFactory::get($inputId, $inputData);
					if ($this->getProtocol() >= ProtocolInfo::PROTOCOL_361) {
						$entry["output"] = $out = $this->getSlot($this->getProtocol(), false)->getItemStack();
						if ($out->getDamage() === 0x7fff) {
							$out->setDamage(0); //TODO HACK: some 1.12 furnace recipe outputs have wildcard damage values
						}
					} else {
						$entry["output"] = $this->getSlot($this->getProtocol(), false)->getItemStack();
					}
					if ($this->getProtocol() >= ProtocolInfo::PROTOCOL_354) {
						$entry["block"] = $this->getString();
					}

					break;
				case self::ENTRY_MULTI:
					$entry["uuid"] = $this->getUUID()->toString();
					if ($this->getProtocol() >= ProtocolInfo::PROTOCOL_407) {
						$entry["net_id"] = $this->readRecipeNetId();
					}
					break;
				default:
					throw new PacketDecodeException("Unhandled recipe type $recipeType!"); //do not continue attempting to decode
			}
			$this->decodedEntries[] = $entry;
		}
		if ($this->getProtocol() >= ProtocolInfo::PROTOCOL_388) {
			for ($i = 0, $count = $this->getUnsignedVarInt(); $i < $count; ++$i) {
				$input = $this->getVarInt();
				if ($this->getProtocol() >= ProtocolInfo::PROTOCOL_407) {
					$inputMeta = $this->getVarInt();
				}
				$ingredient = $this->getVarInt();
				if ($this->getProtocol() >= ProtocolInfo::PROTOCOL_407) {
					$ingredientMeta = $this->getVarInt();
				}
				$output = $this->getVarInt();
				if ($this->getProtocol() >= ProtocolInfo::PROTOCOL_407) {
					$outputMeta = $this->getVarInt();
				}
				if ($this->getProtocol() >= ProtocolInfo::PROTOCOL_419) {
					$itemTranslator = ItemTranslator::getInstance($this->getProtocol());
					[$input, $inputMeta] = $itemTranslator->fromNetworkId($input, $inputMeta ?? 0);
					[$ingredient, $ingredientMeta] = $itemTranslator->fromNetworkId($ingredient, $ingredientMeta ?? 0);
					[$output, $outputMeta] = $itemTranslator->fromNetworkId($output, $outputMeta ?? 0);
				}
				$this->potionTypeRecipes[] = new PotionTypeRecipe($input, $inputMeta ?? 0, $ingredient, $ingredientMeta ?? 0, $output, $outputMeta ?? 0);
			}
			for ($i = 0, $count = $this->getUnsignedVarInt(); $i < $count; ++$i) {
				$input = $this->getVarInt();
				$ingredient = $this->getVarInt();
				$output = $this->getVarInt();
				if ($this->getProtocol() >= ProtocolInfo::PROTOCOL_419) {
					$itemTranslator = ItemTranslator::getInstance($this->getProtocol());
					[$input, ] = $itemTranslator->fromNetworkId($input, 0);
					[$ingredient, ] = $itemTranslator->fromNetworkId($ingredient, 0);
					[$output, ] = $itemTranslator->fromNetworkId($output, 0);
				}
				$this->potionContainerRecipes[] = new PotionContainerChangeRecipe($input, $ingredient, $output);
			}
			if ($this->getProtocol() >= ProtocolInfo::PROTOCOL_465) {
				for ($i = 0, $count = $this->getUnsignedVarInt(); $i < $count; ++$i) {
					$inputIdAndData = $this->getVarInt();
					[$inputId, $inputMeta] = [$inputIdAndData >> 16, $inputIdAndData & 0x7fff];
					$outputs = [];
					for ($j = 0, $outputCount = $this->getUnsignedVarInt(); $j < $outputCount; ++$j) {
						$outputItemId = $this->getVarInt();
						$outputItemCount = $this->getVarInt();
						$outputs[] = new MaterialReducerRecipeOutput($outputItemId, $outputItemCount);
					}
					$this->materialReducerRecipes[] = new MaterialReducerRecipe($inputId, $inputMeta, $outputs);
				}
			}
		}
		$this->cleanRecipes = $this->getBool();
	}

	private static function writeEntry($entry, NetworkBinaryStream $stream, int $pos, int $playerProtocol)
	{
		if ($entry instanceof ShapelessRecipe) {
			return self::writeShapelessRecipe($entry, $stream, $pos, $playerProtocol);
		} elseif ($entry instanceof ShapedRecipe) {
			return self::writeShapedRecipe($entry, $stream, $pos, $playerProtocol);
		} elseif ($entry instanceof FurnaceRecipe) {
			return self::writeFurnaceRecipe($entry, $stream, $playerProtocol);
		}
		//TODO: add MultiRecipe

		return -1;
	}

	private static function writeShapelessRecipe(ShapelessRecipe $recipe, NetworkBinaryStream $stream, int $pos, int $playerProtocol)
	{
		if ($playerProtocol >= ProtocolInfo::PROTOCOL_361) {
			$stream->putString(Binary::writeInt($pos)); //some kind of recipe ID, doesn't matter what it is as long as it's unique
		}
		$stream->putUnsignedVarInt($recipe->getIngredientCount());
		foreach ($recipe->getIngredientList() as $item) {
			if ($playerProtocol >= ProtocolInfo::PROTOCOL_361) {
				$stream->putRecipeIngredient($item, $playerProtocol);
			} else {
				$stream->putSlot(ItemStackWrapper::legacy($item), $playerProtocol, false);
			}
		}

		$results = $recipe->getResults();
		$stream->putUnsignedVarInt(count($results));
		foreach ($results as $item) {
			$stream->putSlot(ItemStackWrapper::legacy($item), $playerProtocol, false);
		}

		$stream->put(str_repeat("\x00", 16)); //Null UUID
		if ($playerProtocol >= ProtocolInfo::PROTOCOL_354) {
			$stream->putString("crafting_table"); //TODO: blocktype (no prefix) (this might require internal API breaks)
			if ($playerProtocol >= ProtocolInfo::PROTOCOL_361) {
				$stream->putVarInt($recipe->getPriority());
				if ($playerProtocol >= ProtocolInfo::PROTOCOL_407) {
					if ($playerProtocol >= ProtocolInfo::PROTOCOL_685) {
						$stream->putBool(true); //RecipeUnlockingRequirement
					}
					$stream->writeRecipeNetId($pos); //TODO: ANOTHER recipe ID, only used on the network
				}
			}
		}

		return CraftingDataPacket::ENTRY_SHAPELESS;
	}

	private static function writeShapedRecipe(ShapedRecipe $recipe, NetworkBinaryStream $stream, int $pos, int $playerProtocol)
	{
		if ($playerProtocol >= ProtocolInfo::PROTOCOL_361) {
			$stream->putString(Binary::writeInt($pos)); //some kind of recipe ID, doesn't matter what it is as long as it's unique
		}
		$stream->putVarInt($recipe->getWidth());
		$stream->putVarInt($recipe->getHeight());

		for ($z = 0; $z < $recipe->getHeight(); ++$z) {
			for ($x = 0; $x < $recipe->getWidth(); ++$x) {
				$ingredient = $recipe->getIngredient($x, $z);
				if ($playerProtocol >= ProtocolInfo::PROTOCOL_361) {
					$stream->putRecipeIngredient($ingredient, $playerProtocol);
				} else {
					$stream->putSlot(ItemStackWrapper::legacy($ingredient), $playerProtocol, false);
				}
			}
		}

		$results = $recipe->getResults();
		$stream->putUnsignedVarInt(count($results));
		foreach ($results as $item) {
			$stream->putSlot(ItemStackWrapper::legacy($item), $playerProtocol, false);
		}

		$stream->put(str_repeat("\x00", 16)); //Null UUID
		if ($playerProtocol >= ProtocolInfo::PROTOCOL_354) {
			$stream->putString("crafting_table"); //TODO: blocktype (no prefix) (this might require internal API breaks)
			if ($playerProtocol >= ProtocolInfo::PROTOCOL_361) {
				$stream->putVarInt($recipe->getPriority());
				if ($playerProtocol >= ProtocolInfo::PROTOCOL_407) {
					if ($playerProtocol >= ProtocolInfo::PROTOCOL_671) {
						$stream->putBool(true); //symmetric
						if ($playerProtocol >= ProtocolInfo::PROTOCOL_685) {
							$stream->putBool(true); //RecipeUnlockingRequirement
						}
					}
					$stream->writeRecipeNetId($pos); //TODO: ANOTHER recipe ID, only used on the network
				}
			}
		}

		return CraftingDataPacket::ENTRY_SHAPED;
	}

	private static function writeFurnaceRecipe(FurnaceRecipe $recipe, NetworkBinaryStream $stream, int $playerProtocol)
	{
		$id = $recipe->getInput()->getId();
		$damage = $recipe->getInput()->getDamage();
		if ($playerProtocol >= ProtocolInfo::PROTOCOL_419) {
			$itemTranslator = ItemTranslator::getInstance($playerProtocol);
			if ($recipe->getInput()->hasAnyDamageValue()) {
				[$id, ] = $itemTranslator->toNetworkId($id, 0);
				$damage = 0x7fff;
			} else {
				[$id, $damage] = $itemTranslator->toNetworkId($id, $damage);
			}
		}
		$stream->putVarInt($id);
		if ($playerProtocol >= ProtocolInfo::PROTOCOL_419) {
			$stream->putVarInt($damage);
			$result = CraftingDataPacket::ENTRY_FURNACE_DATA;
		} else {
			$result = CraftingDataPacket::ENTRY_FURNACE;
			if (!$recipe->getInput()->hasAnyDamageValue()) { //Data recipe
				$stream->putVarInt($damage);
				$result = CraftingDataPacket::ENTRY_FURNACE_DATA;
			}
		}
		$stream->putSlot(ItemStackWrapper::legacy($recipe->getResult()), $playerProtocol, false);
		if ($playerProtocol >= ProtocolInfo::PROTOCOL_354) {
			$stream->putString("furnace"); //TODO: blocktype (no prefix) (this might require internal API breaks)
		}
		return $result;
	}

	public function addShapelessRecipe(ShapelessRecipe $recipe)
	{
		$this->entries[] = $recipe;
	}

	public function addShapedRecipe(ShapedRecipe $recipe)
	{
		$this->entries[] = $recipe;
	}

	public function addFurnaceRecipe(FurnaceRecipe $recipe)
	{
		$this->entries[] = $recipe;
	}

	protected function encodePayload() : void
	{
		$this->putUnsignedVarInt(count($this->entries));

		$writer = new NetworkBinaryStream();
		$counter = 0;
		foreach ($this->entries as $d) {
			$entryType = self::writeEntry($d, $writer, $counter++, $this->getProtocol());
			if ($entryType >= 0) {
				$this->putVarInt($entryType);
				$this->put($writer->getBuffer());
			} else {
				$this->putVarInt(-1);
			}

			$writer->reset();
		}
		if ($this->getProtocol() >= ProtocolInfo::PROTOCOL_388) {
			$this->putUnsignedVarInt(count($this->potionTypeRecipes));
			foreach ($this->potionTypeRecipes as $recipe) {
				$input = $recipe->getInputItemId();
				$inputMeta = $recipe->getInputItemMeta();
				$ingredient = $recipe->getIngredientItemId();
				$ingredientMeta = $recipe->getIngredientItemMeta();
				$output = $recipe->getOutputItemId();
				$outputMeta = $recipe->getOutputItemMeta();
				if ($this->getProtocol() >= ProtocolInfo::PROTOCOL_419) {
					$itemTranslator = ItemTranslator::getInstance($this->getProtocol());
					[$input, $inputMeta] = $itemTranslator->toNetworkId($input, $inputMeta);
					[$ingredient, $ingredientMeta] = $itemTranslator->toNetworkId($ingredient, $ingredientMeta);
					[$output, $outputMeta] = $itemTranslator->toNetworkId($output, $outputMeta);
				}
				$this->putVarInt($input);
				if ($this->getProtocol() >= ProtocolInfo::PROTOCOL_407) {
					$this->putVarInt($inputMeta);
				}
				$this->putVarInt($ingredient);
				if ($this->getProtocol() >= ProtocolInfo::PROTOCOL_407) {
					$this->putVarInt($ingredientMeta);
				}
				$this->putVarInt($output);
				if ($this->getProtocol() >= ProtocolInfo::PROTOCOL_407) {
					$this->putVarInt($outputMeta);
				}
			}
			$this->putUnsignedVarInt(count($this->potionContainerRecipes));
			foreach ($this->potionContainerRecipes as $recipe) {
				$input = $recipe->getInputItemId();
				$ingredient = $recipe->getIngredientItemId();
				$output = $recipe->getOutputItemId();
				if ($this->getProtocol() >= ProtocolInfo::PROTOCOL_419) {
					$itemTranslator = ItemTranslator::getInstance($this->getProtocol());
					[$input, ] = $itemTranslator->toNetworkId($input, 0);
					[$ingredient, ] = $itemTranslator->toNetworkId($ingredient, 0);
					[$output, ] = $itemTranslator->toNetworkId($output, 0);
				}
				$this->putVarInt($input);
				$this->putVarInt($ingredient);
				$this->putVarInt($output);
			}
			if ($this->getProtocol() >= ProtocolInfo::PROTOCOL_465) {
				$this->putUnsignedVarInt(count($this->materialReducerRecipes));
				foreach ($this->materialReducerRecipes as $recipe) {
					$this->putVarInt(($recipe->getInputItemId() << 16) | $recipe->getInputItemMeta());
					$this->putUnsignedVarInt(count($recipe->getOutputs()));
					foreach ($recipe->getOutputs() as $output) {
						$this->putVarInt($output->getItemId());
						$this->putVarInt($output->getCount());
					}
				}
			}
		}

		$this->putBool($this->cleanRecipes);
	}

	public function mustBeDecoded() : bool
	{
		return false;
	}

	public function handle(NetworkSession $session) : bool
	{
		return $session->handleCraftingData($this);
	}
}
