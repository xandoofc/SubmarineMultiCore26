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

namespace pocketmine\network\mcpe\protocol\types\recipe;

final class MaterialReducerRecipe
{
	private $inputItemId;
	private $inputItemMeta;
	/**
	 * @var MaterialReducerRecipeOutput[]
	 * @phpstan-var list<MaterialReducerRecipeOutput>
	 */
	private $outputs;

	/**
	 * @param MaterialReducerRecipeOutput[] $outputs
	 * @phpstan-param list<MaterialReducerRecipeOutput> $outputs
	 */
	public function __construct(int $inputItemId, int $inputItemMeta, array $outputs)
	{
		$this->inputItemId = $inputItemId;
		$this->inputItemMeta = $inputItemMeta;
		$this->outputs = $outputs;
	}

	public function getInputItemId() : int
	{
		return $this->inputItemId;
	}

	public function getInputItemMeta() : int
	{
		return $this->inputItemMeta;
	}

	/** @return MaterialReducerRecipeOutput[] */
	public function getOutputs() : array
	{
		return $this->outputs;
	}
}
