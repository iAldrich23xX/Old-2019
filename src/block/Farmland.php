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

use pocketmine\block\utils\BlockDataSerializer;
use pocketmine\entity\Entity;
use pocketmine\entity\Living;
use pocketmine\event\entity\EntityTrampleFarmlandEvent;
use pocketmine\item\Item;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Facing;
use function lcg_value;

class Farmland extends Transparent{
	public const MAX_WETNESS = 7;

	protected int $wetness = 0; //"moisture" blockstate property in PC

	protected function writeStateToMeta() : int{
		return $this->wetness;
	}

	public function readStateFromData(int $id, int $stateMeta) : void{
		$this->wetness = BlockDataSerializer::readBoundedInt("wetness", $stateMeta, 0, self::MAX_WETNESS);
	}

	public function getStateBitmask() : int{
		return 0b111;
	}

	public function getWetness() : int{ return $this->wetness; }

	/** @return $this */
	public function setWetness(int $wetness) : self{
		if($wetness < 0 || $wetness > self::MAX_WETNESS){
			throw new \InvalidArgumentException("Wetness must be in range 0 ... " . self::MAX_WETNESS);
		}
		$this->wetness = $wetness;
		return $this;
	}

	public function getDropsForCompatibleTool(Item $item) : array{
		return [
			VanillaBlocks::DIRT()->asItem()
		];
	}
}
