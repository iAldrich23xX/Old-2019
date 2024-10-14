<?php

/*
 *
 *  ____            _        _   __  __ _                  __  __ ____
 * |  _ \ ___   ___| | _____| |_|  \/  (_)_ __   ___      |  \/  |  _ \
 * | |_) / _ \ / __| |/ / _ \ __| |\/| | | '_ \ / _ \_____| |\/| | |_) |
 * |  __/ (_) | (__|   <  __/ |_| |  | | | | | |  __/_____| |  | |  __/
 * |_|   \___/ \___|_|\_\___|\__|_|  |_|_|_| |_|\___|     |_|  |_|_|
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author PocketMine Team
 * @link http://www.pocketmine.net/
 *
 *
 */

declare(strict_types=1);

namespace pocketmine\block;

use pocketmine\block\utils\SupportType;
use pocketmine\block\utils\TreeType;
use pocketmine\event\block\LeavesDecayEvent;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\item\VanillaItems;
use pocketmine\math\Facing;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\world\BlockTransaction;
use pocketmine\world\World;
use function mt_rand;

class Leaves extends Transparent{

	protected TreeType $treeType;
	protected bool $noDecay = false;
	protected bool $checkDecay = false;

	public function __construct(BlockIdentifier $idInfo, string $name, BlockBreakInfo $breakInfo, TreeType $treeType){
		parent::__construct($idInfo, $name, $breakInfo);
		$this->treeType = $treeType;
	}

	protected function writeStateToMeta() : int{
		return ($this->noDecay ? BlockLegacyMetadata::LEAVES_FLAG_NO_DECAY : 0) | ($this->checkDecay ? BlockLegacyMetadata::LEAVES_FLAG_CHECK_DECAY : 0);
	}

	public function readStateFromData(int $id, int $stateMeta) : void{
		$this->noDecay = ($stateMeta & BlockLegacyMetadata::LEAVES_FLAG_NO_DECAY) !== 0;
		$this->checkDecay = ($stateMeta & BlockLegacyMetadata::LEAVES_FLAG_CHECK_DECAY) !== 0;
	}

	public function getStateBitmask() : int{
		return 0b1100;
	}

	public function isNoDecay() : bool{ return $this->noDecay; }

	/** @return $this */
	public function setNoDecay(bool $noDecay) : self{
		$this->noDecay = $noDecay;
		return $this;
	}

	public function isCheckDecay() : bool{ return $this->checkDecay; }

	/** @return $this */
	public function setCheckDecay(bool $checkDecay) : self{
		$this->checkDecay = $checkDecay;
		return $this;
	}

	public function blocksDirectSkyLight() : bool{
		return true;
	}

	public function place(BlockTransaction $tx, Item $item, Block $blockReplace, Block $blockClicked, int $face, Vector3 $clickVector, ?Player $player = null) : bool{
		$this->noDecay = true; //artificial leaves don't decay
		return parent::place($tx, $item, $blockReplace, $blockClicked, $face, $clickVector, $player);
	}

	public function getDropsForCompatibleTool(Item $item) : array{
		if(($item->getBlockToolType() & BlockToolType::SHEARS) !== 0){
			return parent::getDropsForCompatibleTool($item);
		}

		$drops = [];
		if(mt_rand(1, 20) === 1){ //Saplings
			$drops[] = ItemFactory::getInstance()->get(ItemIds::SAPLING, $this->treeType->getMagicNumber());
		}
		if(($this->treeType->equals(TreeType::OAK()) || $this->treeType->equals(TreeType::DARK_OAK())) && mt_rand(1, 200) === 1){ //Apples
			$drops[] = VanillaItems::APPLE();
		}
		if(mt_rand(1, 50) === 1){
			$drops[] = VanillaItems::STICK()->setCount(mt_rand(1, 2));
		}

		return $drops;
	}

	public function isAffectedBySilkTouch() : bool{
		return true;
	}

	public function getSupportType(int $facing) : SupportType{
		return SupportType::NONE();
	}
}