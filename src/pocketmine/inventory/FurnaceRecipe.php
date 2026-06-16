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

use pocketmine\item\Item;

class FurnaceRecipe implements Recipe
{
	/** @var Item */
	private $output;

	/** @var Item */
	private $ingredient;

	public function __construct(Item $result, Item $ingredient)
	{
		$this->output = clone $result;
		$this->ingredient = clone $ingredient;
	}

	public function setInput(Item $item)
	{
		$this->ingredient = clone $item;
	}

	public function getInput() : Item
	{
		return clone $this->ingredient;
	}

	public function getResult() : Item
	{
		return clone $this->output;
	}

	public function registerToCraftingManager(CraftingManager $manager) : void
	{
		$manager->registerFurnaceRecipe($this);
	}
}
