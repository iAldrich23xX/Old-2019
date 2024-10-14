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

use pocketmine\block\utils\InvalidBlockStateException;
use pocketmine\block\utils\RailConnectionInfo;
use pocketmine\math\Facing;
use function array_keys;
use function implode;

class Rail extends BaseRail{

	private int $railShape = BlockLegacyMetadata::RAIL_STRAIGHT_NORTH_SOUTH;

	public function readStateFromData(int $id, int $stateMeta) : void{
		if(!isset(RailConnectionInfo::CONNECTIONS[$stateMeta]) && !isset(RailConnectionInfo::CURVE_CONNECTIONS[$stateMeta])){
			throw new InvalidBlockStateException("No rail shape matches metadata $stateMeta");
		}
		$this->railShape = $stateMeta;
	}

	protected function writeStateToMeta() : int{
		//TODO: railShape won't be plain metadata in future
		return $this->railShape;
	}

	public function getStateBitmask() : int{
		return 0b1111;
	}
}
