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

use InvalidArgumentException;
use pocketmine\item\Item;

use function array_map;
use function count;

class ShapelessRecipe implements CraftingRecipe
{
	/** @var Item[] */
	private $ingredients = [];
	/** @var Item[] */
	private $results;
	/** @var int */
	private $priority;

	/**
	 * @param Item[] $ingredients No more than 9 total. This applies to sum of item stack counts, not count of array.
	 * @param Item[] $results     List of result items created by this recipe.
	 */
	public function __construct(array $ingredients, array $results, int $priority)
	{
		foreach ($ingredients as $item) {
			//Ensure they get split up properly
			$this->addIngredient($item);
		}

		$this->results = array_map(function (Item $item) : Item { return clone $item; }, $results);
		$this->priority = $priority;
	}

	public function getResults() : array
	{
		return array_map(function (Item $item) : Item { return clone $item; }, $this->results);
	}

	public function setResults(array $results) : void{
		$this->results = $results;
	}

	public function getPriority() : int
	{
		return $this->priority;
	}

	public function getResultsFor(CraftingGrid $grid) : array
	{
		return $this->getResults();
	}

	/**
	 * @throws InvalidArgumentException
	 */
	public function addIngredient(Item $item) : ShapelessRecipe
	{
		if (count($this->ingredients) + $item->getCount() > 9) {
			throw new InvalidArgumentException("Shapeless recipes cannot have more than 9 ingredients");
		}

		while ($item->getCount() > 0) {
			$this->ingredients[] = $item->pop();
		}

		return $this;
	}

	public function setIngredient(mixed $idx, Item $item) : void
	{
		if ($idx < 0 || $idx > 9) {
			throw new InvalidArgumentException("Shapeless recipes cannot have more than 9 ingredients");
		}

		$this->ingredients[$idx] = clone $item;
	}

	/**
	 * @return $this
	 */
	public function removeIngredient(Item $item)
	{
		foreach ($this->ingredients as $index => $ingredient) {
			if ($item->getCount() <= 0) {
				break;
			}
			if ($ingredient->equals($item, !$item->hasAnyDamageValue(), $item->hasCompoundTag())) {
				unset($this->ingredients[$index]);
				$item->pop();
			}
		}

		return $this;
	}

	/**
	 * @return Item[]
	 */
	public function getIngredientList() : array
	{
		return array_map(function (Item $item) : Item { return clone $item; }, $this->ingredients);
	}

	public function setIngredientList(array $ingredients) : void{
		$this->ingredients = $ingredients;
	}

	public function getIngredientCount() : int
	{
		$count = 0;
		foreach ($this->ingredients as $ingredient) {
			$count += $ingredient->getCount();
		}

		return $count;
	}

	public function registerToCraftingManager(CraftingManager $manager) : void
	{
		$manager->registerShapelessRecipe($this);
	}

	public function matchesCraftingGrid(CraftingGrid $grid) : bool
	{
		//don't pack the ingredients - shapeless recipes require that each ingredient be in a separate slot
		$input = $grid->getContents();

		foreach ($this->ingredients as $needItem) {
			foreach ($input as $j => $haveItem) {
				if ($haveItem->equals($needItem, !$needItem->hasAnyDamageValue(), $needItem->hasCompoundTag()) && $haveItem->getCount() >= $needItem->getCount()) {
					unset($input[$j]);
					continue 2;
				}
			}

			return false; //failed to match the needed item to a given item
		}

		return empty($input); //crafting grid should be empty apart from the given ingredient stacks
	}
}
