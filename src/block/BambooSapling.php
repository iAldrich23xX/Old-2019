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

use pocketmine\event\block\StructureGrowEvent;
use pocketmine\item\Bamboo as ItemBamboo;
use pocketmine\item\Fertilizer;
use pocketmine\item\Item;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\world\BlockTransaction;

final class BambooSapling extends Flowable{

	private bool $ready = false;

	public function readStateFromData(int $id, int $stateMeta) : void{
		$this->ready = ($stateMeta & BlockLegacyMetadata::BAMBOO_SAPLING_FLAG_READY) !== 0;
	}

	protected function writeStateToMeta() : int{
		return $this->ready ? BlockLegacyMetadata::BAMBOO_SAPLING_FLAG_READY : 0;
	}

	public function getStateBitmask() : int{ return 0b1; }

	public function asItem() : Item{
		return VanillaBlocks::BAMBOO()->asItem();
	}
}
